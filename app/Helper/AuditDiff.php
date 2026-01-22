<?php

namespace App\Helper;

class AuditDiff
{
    protected static array $ignored = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
        'remember_token',
        // 'empresa_activa_id',
    ];

    public static function diff(?string $before, ?string $after): array
    {
        $before = json_decode($before ?? '{}', true) ?? [];
        $after  = json_decode($after ?? '{}', true) ?? [];

        $changes = [];

        foreach ($after as $key => $newValue) {

            if (in_array($key, self::$ignored)) {
                continue;
            }

            $oldValue = $before[$key] ?? null;

            if ($oldValue != $newValue) {
                $changes[$key] = [
                    'before' => $oldValue,
                    'after'  => $newValue,
                ];
            }
        }

        return $changes;
    }
}
