<?php

namespace App\Traits;

use App\Models\AuditLog;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            if (auth()->check()) {
                AuditLog::log(
                    'created',
                    get_class($model),
                    $model->id,
                    null,
                    $model->getAttributes()
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
                $sensitiveFields = ['password', 'remember_token'];
                foreach ($sensitiveFields as $field) {
                    unset($changes[$field], $original[$field]);
                }

                if (!empty($changes)) {
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
                    $model->getOriginal(),
                    null
                );
            }
        });
    }
}
