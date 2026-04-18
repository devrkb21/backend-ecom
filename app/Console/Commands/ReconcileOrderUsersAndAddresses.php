<?php

namespace App\Console\Commands;

use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use Illuminate\Console\Command;

class ReconcileOrderUsersAndAddresses extends Command
{
    protected $signature = 'orders:reconcile-users-addresses
        {--chunk=200 : Number of records to process per chunk}
        {--dry-run : Preview counts without writing changes}';

    protected $description = 'Link guest-like orders to existing users by email/phone and backfill missing customer shipping addresses from order history';

    public function handle(): int
    {
        $chunkSize = max(50, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');

        $guestEmail = strtolower(trim((string) config('shop.guest_checkout_user_email', 'guest.checkout@innercollection.local')));
        $guestUserId = User::withTrashed()
            ->whereRaw('LOWER(email) = ?', [$guestEmail])
            ->value('id');

        $this->info('Reconciling existing orders and customer addresses...');
        $this->line('Mode: ' . ($dryRun ? 'dry-run (no writes)' : 'write'));

        $inspectedOrders = 0;
        $matchedOrders = 0;
        $updatedOrders = 0;
        $addressesCreatedFromMatchedOrders = 0;

        $candidateOrdersQuery = Order::withTrashed()
            ->where(function ($query) use ($guestUserId) {
                $query->whereNull('user_id');

                if ($guestUserId !== null) {
                    $query->orWhere('user_id', $guestUserId);
                }
            })
            ->orderBy('id');

        $candidateOrders = (clone $candidateOrdersQuery)->count();
        $this->line("Candidate orders for user reconciliation: {$candidateOrders}");

        $candidateOrdersQuery->chunkById($chunkSize, function ($orders) use (
            $guestEmail,
            $dryRun,
            &$inspectedOrders,
            &$matchedOrders,
            &$updatedOrders,
            &$addressesCreatedFromMatchedOrders
        ) {
            foreach ($orders as $order) {
                /** @var Order $order */
                $inspectedOrders++;

                $email = strtolower(trim((string) $order->shipping_email));
                $phone = (string) $order->shipping_phone;

                $matchedUser = $this->findExistingUserByContact($email, $phone, $guestEmail);
                if (!$matchedUser) {
                    continue;
                }

                $matchedOrders++;

                if ($dryRun) {
                    continue;
                }

                $order->forceFill(['user_id' => $matchedUser->id])->save();
                $updatedOrders++;

                if ($this->createDefaultShippingAddressIfMissing($matchedUser, $order)) {
                    $addressesCreatedFromMatchedOrders++;
                }
            }
        });

        $inspectedUsers = 0;
        $usersWithBackfilledAddresses = 0;

        $usersWithoutAddressQuery = User::query()
            ->where('role', User::ROLE_CUSTOMER)
            ->whereRaw('LOWER(email) != ?', [$guestEmail])
            ->whereDoesntHave('addresses')
            ->whereHas('orders')
            ->orderBy('id');

        $usersWithoutAddressCount = (clone $usersWithoutAddressQuery)->count();
        $this->line("Existing users without saved addresses: {$usersWithoutAddressCount}");

        $usersWithoutAddressQuery->chunkById($chunkSize, function ($users) use (
            $dryRun,
            &$inspectedUsers,
            &$usersWithBackfilledAddresses
        ) {
            foreach ($users as $user) {
                /** @var User $user */
                $inspectedUsers++;

                /** @var Order|null $latestOrder */
                $latestOrder = $user->orders()
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->first();

                if (!$latestOrder) {
                    continue;
                }

                if ($dryRun) {
                    $usersWithBackfilledAddresses++;

                    continue;
                }

                if ($this->createDefaultShippingAddressIfMissing($user, $latestOrder)) {
                    $usersWithBackfilledAddresses++;
                }
            }
        });

        $this->newLine();
        $this->info('Reconciliation summary');
        $this->line("Orders inspected: {$inspectedOrders}");
        $this->line("Orders matched to existing users: {$matchedOrders}");
        $this->line("Orders updated: {$updatedOrders}");
        $this->line("Addresses created from matched orders: {$addressesCreatedFromMatchedOrders}");
        $this->line("Users inspected for address backfill: {$inspectedUsers}");
        $this->line("Users with backfilled addresses: {$usersWithBackfilledAddresses}");

        return self::SUCCESS;
    }

    protected function findExistingUserByContact(string $email, string $phone, string $guestEmail): ?User
    {
        if (
            $email !== ''
            && $email !== 'customer@local.invalid'
            && $email !== $guestEmail
            && filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            $matchedByEmail = User::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();

            if ($matchedByEmail) {
                return $matchedByEmail;
            }
        }

        $normalizedPhone = $this->normalizePhone($phone);
        if ($normalizedPhone === '') {
            return null;
        }

        $normalizedPhoneSql = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', ''), '+', '')";

        $matchedCustomerByPhone = User::query()
            ->where('role', User::ROLE_CUSTOMER)
            ->whereRaw($normalizedPhoneSql . ' = ?', [$normalizedPhone])
            ->orderByDesc('id')
            ->first();

        if ($matchedCustomerByPhone) {
            return $matchedCustomerByPhone;
        }

        return User::query()
            ->whereRaw($normalizedPhoneSql . ' = ?', [$normalizedPhone])
            ->orderByDesc('id')
            ->first();
    }

    protected function createDefaultShippingAddressIfMissing(User $user, Order $order): bool
    {
        $hasShippingAddress = Address::query()
            ->where('user_id', $user->id)
            ->whereIn('type', ['shipping', 'both'])
            ->exists();

        if ($hasShippingAddress) {
            return false;
        }

        $checkoutFields = is_array($order->checkout_fields_payload) ? $order->checkout_fields_payload : [];
        $shippingArea = $this->toNullableString($checkoutFields['shipping_area'] ?? null);
        $addressLineOne = $this->toNullableString($order->shipping_address) ?? 'Address not provided';

        $address = Address::query()->create([
            'user_id' => $user->id,
            'label' => 'Imported Checkout Address',
            'type' => 'shipping',
            'is_default' => true,
            'name' => $this->toNullableString($order->shipping_name) ?? $user->name,
            'phone' => $this->toNullableString($order->shipping_phone) ?? ($this->toNullableString($user->phone) ?? 'N/A'),
            'email' => $this->toNullableString($order->shipping_email) ?? $user->email,
            'address_line_1' => $addressLineOne,
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
        ]);

        $address->setAsDefault();

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
}
