<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminRole extends Model
{
    protected $fillable = [
        'key',
        'name',
        'description',
        'permissions',
        'can_access_admin_panel',
        'is_active',
        'is_system',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'can_access_admin_panel' => 'boolean',
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role', 'key');
    }

    public static function permissionCatalog(): array
    {
        return [
            'Core' => [
                'dashboard.view' => [
                    'label' => 'Dashboard',
                    'description' => 'View admin dashboard and quick stats.',
                ],
                'roles.manage' => [
                    'label' => 'Roles & Permissions',
                    'description' => 'Create roles, edit permissions, and control admin login access.',
                ],
            ],
            'Catalog' => [
                'catalog.manage' => [
                    'label' => 'Catalog Management',
                    'description' => 'Manage products, categories, attributes, and variants.',
                ],
                'media.manage' => [
                    'label' => 'Media Library',
                    'description' => 'Upload, edit, and remove files from media library.',
                ],
            ],
            'Operations' => [
                'orders.manage' => [
                    'label' => 'Orders',
                    'description' => 'View orders, tracking, and update order status.',
                ],
                'payments.view' => [
                    'label' => 'Payments',
                    'description' => 'View payment list and payment details.',
                ],
                'returns.manage' => [
                    'label' => 'Returns & Refunds',
                    'description' => 'Approve/reject returns and process refunds.',
                ],
                'abandoned_carts.manage' => [
                    'label' => 'Abandoned Carts',
                    'description' => 'Handle abandoned cart follow-up workflows.',
                ],
            ],
            'Business' => [
                'marketing.manage' => [
                    'label' => 'Marketing',
                    'description' => 'Manage coupons, reviews, flash sales, and loyalty module.',
                ],
                'analytics.view' => [
                    'label' => 'Analytics & BI',
                    'description' => 'Access analytics reports and business intelligence screens.',
                ],
            ],
            'Administration' => [
                'users.manage' => [
                    'label' => 'Users',
                    'description' => 'Create users, assign roles, and manage active/inactive status.',
                ],
                'settings.manage' => [
                    'label' => 'Settings',
                    'description' => 'Manage site, payment, shipping, and integration settings.',
                ],
            ],
        ];
    }

    public static function availablePermissionKeys(): array
    {
        $keys = [];

        foreach (self::permissionCatalog() as $group) {
            foreach ($group as $permission => $details) {
                $keys[] = $permission;
            }
        }

        return $keys;
    }

    public static function normalizePermissions(array $permissions): array
    {
        $allowed = self::availablePermissionKeys();
        $normalized = [];

        foreach ($permissions as $permission) {
            $permission = trim((string) $permission);

            if ($permission === '*') {
                return ['*'];
            }

            if (in_array($permission, $allowed, true)) {
                $normalized[$permission] = $permission;
            }
        }

        return array_values($normalized);
    }

    public function hasPermission(string $permission): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $permissions = is_array($this->permissions) ? $this->permissions : [];

        if (in_array('*', $permissions, true)) {
            return true;
        }

        return in_array($permission, $permissions, true);
    }
}
