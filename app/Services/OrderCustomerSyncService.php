<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class OrderCustomerSyncService
{
    public function syncGuestOrdersForUser(User $user): array
    {
        $guestEmail = strtolower(trim((string) config('shop.guest_checkout_user_email', 'guest.checkout@innercollection.local')));

        if (!$this->isEligibleCustomer($user, $guestEmail)) {
            return [
                'matched_orders' => 0,
                'updated_orders' => 0,
                'address_created' => false,
                'address_updated' => false,
            ];
        }

        $normalizedUserEmail = strtolower(trim((string) $user->email));
        $normalizedUserPhone = $this->normalizePhone($user->phone);

        $hasUsableEmail = $normalizedUserEmail !== ''
            && $normalizedUserEmail !== 'customer@local.invalid'
            && $normalizedUserEmail !== 'guest@local.invalid'
            && filter_var($normalizedUserEmail, FILTER_VALIDATE_EMAIL);

        $hasUsablePhone = $normalizedUserPhone !== '';

        if (!$hasUsableEmail && !$hasUsablePhone) {
            return [
                'matched_orders' => 0,
                'updated_orders' => 0,
                'address_created' => false,
                'address_updated' => false,
            ];
        }

        $guestUserId = User::withTrashed()
            ->whereRaw('LOWER(email) = ?', [$guestEmail])
            ->value('id');

        $matchQuery = $this->buildGuestOrderMatchQuery(
            $guestUserId,
            $guestEmail,
            $hasUsableEmail,
            $normalizedUserEmail,
            $hasUsablePhone,
            $normalizedUserPhone
        );

        $matchedOrderIds = (clone $matchQuery)->pluck('id');
        $matchedOrdersCount = $matchedOrderIds->count();

        if ($matchedOrdersCount === 0) {
            return [
                'matched_orders' => 0,
                'updated_orders' => 0,
                'address_created' => false,
                'address_updated' => false,
            ];
        }

        $updatedOrdersCount = Order::withTrashed()
            ->whereIn('id', $matchedOrderIds->all())
            ->update([
                'user_id' => $user->id,
                // Reassigned orders are no longer guest-owned.
                'guest_access_token_hash' => null,
                'updated_at' => now(),
            ]);

        /** @var Order|null $latestMatchedOrder */
        $latestMatchedOrder = Order::withTrashed()
            ->whereIn('id', $matchedOrderIds->all())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        $addressSync = [
            'created' => false,
            'updated' => false,
        ];

        if ($latestMatchedOrder instanceof Order) {
            $addressSync = $this->syncDefaultShippingAddressFromOrder($user, $latestMatchedOrder);
        }

        return [
            'matched_orders' => $matchedOrdersCount,
            'updated_orders' => $updatedOrdersCount,
            'address_created' => $addressSync['created'],
            'address_updated' => $addressSync['updated'],
        ];
    }

    protected function buildGuestOrderMatchQuery(
        ?int $guestUserId,
        string $guestEmail,
        bool $hasUsableEmail,
        string $normalizedUserEmail,
        bool $hasUsablePhone,
        string $normalizedUserPhone
    ): Builder {
        $normalizedShippingEmailSql = "LOWER(TRIM(COALESCE(shipping_email, '')))";
        $normalizedShippingPhoneSql = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(shipping_phone, ''), ' ', ''), '-', ''), '(', ''), ')', ''), '+', '')";

        $query = Order::withTrashed()
            ->where(function (Builder $candidateQuery) use ($guestUserId) {
                $candidateQuery->whereNull('user_id');

                if ($guestUserId !== null) {
                    $candidateQuery->orWhere('user_id', $guestUserId);
                }
            });

        $query->where(function (Builder $matchQuery) use (
            $hasUsableEmail,
            $normalizedUserEmail,
            $hasUsablePhone,
            $normalizedUserPhone,
            $normalizedShippingEmailSql,
            $normalizedShippingPhoneSql,
            $guestEmail
        ) {
            if ($hasUsableEmail) {
                $matchQuery->whereRaw($normalizedShippingEmailSql . ' = ?', [$normalizedUserEmail]);
            }

            if ($hasUsablePhone) {
                $phoneMatchScope = function (Builder $phoneMatchQuery) use (
                    $normalizedShippingPhoneSql,
                    $normalizedUserPhone,
                    $normalizedShippingEmailSql,
                    $guestEmail,
                    $hasUsableEmail,
                    $normalizedUserEmail
                ) {
                    $phoneMatchQuery
                        ->whereRaw($normalizedShippingPhoneSql . ' = ?', [$normalizedUserPhone])
                        ->where(function (Builder $phoneEmailFallbackQuery) use (
                            $normalizedShippingEmailSql,
                            $guestEmail,
                            $hasUsableEmail,
                            $normalizedUserEmail
                        ) {
                            // Phone matching is restricted to orders with blank/placeholder emails
                            // to avoid stealing orders that clearly belong to a different email identity.
                            $phoneEmailFallbackQuery
                                ->whereRaw($normalizedShippingEmailSql . " = ''")
                                ->orWhereRaw($normalizedShippingEmailSql . ' = ?', ['customer@local.invalid'])
                                ->orWhereRaw($normalizedShippingEmailSql . ' = ?', ['guest@local.invalid'])
                                ->orWhereRaw($normalizedShippingEmailSql . ' = ?', [$guestEmail]);

                            if ($hasUsableEmail) {
                                $phoneEmailFallbackQuery->orWhereRaw($normalizedShippingEmailSql . ' = ?', [$normalizedUserEmail]);
                            }
                        });
                };

                if ($hasUsableEmail) {
                    $matchQuery->orWhere($phoneMatchScope);
                } else {
                    $matchQuery->where($phoneMatchScope);
                }
            }
        });

        return $query;
    }

    protected function syncDefaultShippingAddressFromOrder(User $user, Order $order): array
    {
        $checkoutFields = is_array($order->checkout_fields_payload) ? $order->checkout_fields_payload : [];
        $shippingArea = $this->toNullableString($checkoutFields['shipping_area'] ?? null);

        $payload = [
            'label' => 'Imported Checkout Address',
            'name' => $this->toNullableString($order->shipping_name) ?? $user->name,
            'phone' => $this->toNullableString($order->shipping_phone) ?? ($this->toNullableString($user->phone) ?? 'N/A'),
            'email' => $this->toNullableString($order->shipping_email) ?? $user->email,
            'address_line_1' => $this->toNullableString($order->shipping_address) ?? 'Address not provided',
            'address_line_2' => $shippingArea,
            'division_id' => $order->shipping_division_id,
            'district_id' => $order->shipping_district_id,
            'upazila_id' => $order->shipping_upazila_id,
            'union_id' => $order->shipping_union_id,
            'area' => $shippingArea,
            'city' => $this->toNullableString($order->shipping_city) ?? 'N/A',
            'state' => $this->toNullableString($order->shipping_state),
            'postal_code' => $this->toNullableString($order->shipping_zip),
            'country' => $this->toNullableString($order->shipping_country) ?? 'Bangladesh',
            'instructions' => $this->toNullableString($order->notes),
        ];

        /** @var Address|null $existingAddress */
        $existingAddress = Address::query()
            ->where('user_id', $user->id)
            ->whereIn('type', ['shipping', 'both'])
            ->orderByDesc('is_default')
            ->orderByDesc('updated_at')
            ->first();

        if (!$existingAddress) {
            $address = Address::query()->create(array_merge($payload, [
                'user_id' => $user->id,
                'type' => 'shipping',
                'is_default' => true,
            ]));

            $address->setAsDefault();

            return [
                'created' => true,
                'updated' => false,
            ];
        }

        $updates = [];
        foreach ($payload as $key => $value) {
            if ($this->isEffectivelyEmpty($value)) {
                continue;
            }

            if ($this->isEffectivelyEmpty($existingAddress->{$key})) {
                $updates[$key] = $value;
            }
        }

        if (!$existingAddress->is_default) {
            $updates['is_default'] = true;
        }

        if (!empty($updates)) {
            $existingAddress->fill($updates);
            $existingAddress->save();

            if (($updates['is_default'] ?? false) === true) {
                $existingAddress->setAsDefault();
            }

            return [
                'created' => false,
                'updated' => true,
            ];
        }

        return [
            'created' => false,
            'updated' => false,
        ];
    }

    protected function isEligibleCustomer(User $user, string $guestEmail): bool
    {
        if ($user->trashed()) {
            return false;
        }

        if ((string) $user->role !== User::ROLE_CUSTOMER) {
            return false;
        }

        $userEmail = strtolower(trim((string) $user->email));
        if ($userEmail === '' || $userEmail === $guestEmail) {
            return false;
        }

        return true;
    }

    protected function normalizePhone(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) ($phone ?? '')) ?? '';
    }

    protected function toNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    protected function isEffectivelyEmpty(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        return false;
    }
}