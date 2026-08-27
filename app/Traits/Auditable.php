<?php

namespace App\Traits;

use App\Models\AuditLog;

trait Auditable
{
    /**
     * Fields that must never be written into audit_logs.old_values / new_values,
     * regardless of which lifecycle hook is logging.
     */
    protected static array $auditableSensitiveFields = ['password', 'remember_token'];

    private static function redactSensitiveFields(?array $attributes): ?array
    {
        if ($attributes === null) {
            return null;
        }

        foreach (static::$auditableSensitiveFields as $field) {
            unset($attributes[$field]);
        }

        return $attributes;
    }

    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            if (auth()->check()) {
                AuditLog::log(
                    'created',
                    get_class($model),
                    $model->id,
                    null,
                    self::redactSensitiveFields($model->getAttributes())
                );
            }
        });

        static::updated(function ($model) {
            if (auth()->check()) {
                $changes = $model->getChanges();
                $original = array_intersect_key($model->getOriginal(), $changes);

                // Don't log if nothing meaningful changed
                if (empty($changes)) {
                    return;
                }

                // Remove sensitive fields from audit
                foreach (static::$auditableSensitiveFields as $field) {
                    unset($changes[$field], $original[$field]);
                }

                if (! empty($changes)) {
                    AuditLog::log(
                        'updated',
                        get_class($model),
                        $model->id,
                        $original,
                        $changes
                    );
                }
            }
        });

        static::deleted(function ($model) {
            if (auth()->check()) {
                AuditLog::log(
                    'deleted',
                    get_class($model),
                    $model->id,
                    self::redactSensitiveFields($model->getOriginal()),
                    null
                );
            }
        });
    }
}
