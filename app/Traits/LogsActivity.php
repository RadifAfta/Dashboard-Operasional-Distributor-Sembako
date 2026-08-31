<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait LogsActivity
{
    /**
     * Boot the trait to listen for Eloquent model lifecycle events.
     */
    public static function bootLogsActivity(): void
    {
        static::created(function (Model $model) {
            $model->recordActivityLog('created', $model->resolveActivityDescription('created'), [
                'attributes' => $model->sanitizeActivityAttributes($model->getAttributes()),
            ]);
        });

        static::updated(function (Model $model) {
            $dirty = $model->getDirty();

            // Ignore if only updated_at was modified
            unset($dirty['updated_at']);
            if (empty($dirty)) {
                return;
            }

            $old = [];
            $new = [];
            foreach (array_keys($dirty) as $key) {
                $old[$key] = $model->getOriginal($key);
                $new[$key] = $model->getAttribute($key);
            }

            $model->recordActivityLog('updated', $model->resolveActivityDescription('updated'), [
                'old' => $model->sanitizeActivityAttributes($old),
                'new' => $model->sanitizeActivityAttributes($new),
            ]);
        });

        static::deleted(function (Model $model) {
            $model->recordActivityLog('deleted', $model->resolveActivityDescription('deleted'), [
                'old' => $model->sanitizeActivityAttributes($model->getOriginal()),
            ]);
        });
    }

    /**
     * Record the activity log entry for this model.
     */
    protected function recordActivityLog(string $event, string $description, ?array $properties = null): void
    {
        try {
            AuditLog::record(
                event: $event,
                description: $description,
                subject: $this,
                properties: $properties,
            );
        } catch (\Throwable $e) {
            // Failsafe: Do not break primary application operations if log recording encounters an error
            report($e);
        }
    }

    /**
     * Resolve a human-friendly description for the log entry.
     */
    protected function resolveActivityDescription(string $event): string
    {
        $classBasename = class_basename($this);
        $name = $this->name ?? $this->title ?? "#{$this->getKey()}";

        return match ($event) {
            'created' => "Menambahkan {$classBasename} baru: {$name}",
            'updated' => "Memperbarui data {$classBasename}: {$name}",
            'deleted' => "Menghapus {$classBasename}: {$name}",
            default => Str::title($event)." {$classBasename}: {$name}",
        };
    }

    /**
     * Strip out sensitive keys before storing in audit log properties.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function sanitizeActivityAttributes(array $attributes): array
    {
        $sensitiveKeys = [
            'password',
            'remember_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
            'api_token',
        ];

        // Also merge hidden fields defined on the model
        $hidden = method_exists($this, 'getHidden') ? $this->getHidden() : [];
        $keysToFilter = array_unique(array_merge($sensitiveKeys, $hidden));

        foreach ($keysToFilter as $key) {
            if (array_key_exists($key, $attributes)) {
                $attributes[$key] = '********';
            }
        }

        return $attributes;
    }
}
