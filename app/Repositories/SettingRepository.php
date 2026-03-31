<?php

namespace App\Repositories;

use App\Models\Setting;
use App\Repositories\Interfaces\SettingRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SettingRepository extends BaseRepository implements SettingRepositoryInterface
{
    public function __construct(Setting $model)
    {
        parent::__construct($model);
    }

    public function getByGroup(string $group, bool $publicOnly = true): Collection
    {
        $query = $this->model->where('group', $group)->orderBy('sort_order');

        if ($publicOnly) {
            $query->where('is_public', true);
        }

        return $query->get();
    }

    public function getByGroupAndKey(string $group, string $key): ?Setting
    {
        return $this->model->where('group', $group)->where('key', $key)->first();
    }

    public function getAllGroups(): array
    {
        return $this->model->distinct()->pluck('group')->toArray();
    }

    public function updateOrCreateSetting(string $group, string $key, array $data): Setting
    {
        return $this->model->updateOrCreate(
            ['group' => $group, 'key' => $key],
            $data
        );
    }

    public function bulkUpdate(array $settings): void
    {
        foreach ($settings as $setting) {
            $this->updateOrCreateSetting(
                $setting['group'],
                $setting['key'],
                $setting
            );
        }
    }

    public function deleteByGroup(string $group): void
    {
        $this->model->where('group', $group)->delete();
    }
}
