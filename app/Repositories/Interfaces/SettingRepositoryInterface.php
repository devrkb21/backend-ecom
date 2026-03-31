<?php

namespace App\Repositories\Interfaces;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Collection;

interface SettingRepositoryInterface extends BaseRepositoryInterface
{
    public function getByGroup(string $group, bool $publicOnly = true): Collection;

    public function getByGroupAndKey(string $group, string $key): ?Setting;

    public function getAllGroups(): array;

    public function updateOrCreateSetting(string $group, string $key, array $data): Setting;

    public function bulkUpdate(array $settings): void;

    public function deleteByGroup(string $group): void;
}
