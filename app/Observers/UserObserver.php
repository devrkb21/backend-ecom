<?php

namespace App\Observers;

use App\Models\User;
use App\Services\OrderCustomerSyncService;

class UserObserver
{
    public function __construct(
        protected OrderCustomerSyncService $orderCustomerSyncService
    ) {}

    public function created(User $user): void
    {
        $this->orderCustomerSyncService->syncGuestOrdersForUser($user);
    }

    public function updated(User $user): void
    {
        if (!$user->wasChanged(['email', 'phone', 'role'])) {
            return;
        }

        $this->orderCustomerSyncService->syncGuestOrdersForUser($user);
    }

    public function restored(User $user): void
    {
        $this->orderCustomerSyncService->syncGuestOrdersForUser($user);
    }
}