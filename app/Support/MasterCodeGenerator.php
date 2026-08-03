<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MasterCodeGenerator
{
    /**
     * @param  array{table: string, column?: string, prefix: string, pad?: int, institution_id?: int|null}  $config
     */
    public static function generate(array $config): string
    {
        $table = $config['table'];
        $column = $config['column'] ?? 'code';
        $prefix = $config['prefix'];
        $pad = $config['pad'] ?? 4;
        $institutionId = $config['institution_id'] ?? null;

        $query = DB::table($table);
        if ($institutionId !== null && self::tableHasInstitution($table)) {
            $query->where('medical_institution_id', $institutionId);
        }

        $max = 0;
        foreach ($query->pluck($column) as $existing) {
            if (! is_string($existing) && ! is_numeric($existing)) {
                continue;
            }
            $existing = (string) $existing;
            if (Str::startsWith($existing, $prefix)) {
                $suffix = substr($existing, strlen($prefix));
                if (ctype_digit($suffix)) {
                    $max = max($max, (int) $suffix);
                }
            }
        }

        do {
            $max++;
            $candidate = $prefix.str_pad((string) $max, $pad, '0', STR_PAD_LEFT);
        } while (self::exists($table, $column, $candidate, null, $institutionId));

        return $candidate;
    }

    public static function exists(
        string $table,
        string $column,
        string $code,
        int|string|null $excludeId = null,
        ?int $institutionId = null,
    ): bool {
        $query = DB::table($table)->where($column, $code);
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }
        if ($institutionId !== null && self::tableHasInstitution($table)) {
            $query->where('medical_institution_id', $institutionId);
        }

        return $query->exists();
    }

    /**
     * Resolve config for known master types.
     *
     * @return array{table: string, column: string, prefix: string, pad: int, institution_scoped: bool}|null
     */
    public static function configFor(string $type): ?array
    {
        return match ($type) {
            'institution' => ['table' => 'medical_institutions', 'column' => 'code', 'prefix' => 'MI-', 'pad' => 4, 'institution_scoped' => false],
            'department' => ['table' => 'departments', 'column' => 'code', 'prefix' => 'DEP-', 'pad' => 3, 'institution_scoped' => true],
            'job_type' => ['table' => 'job_types', 'column' => 'code', 'prefix' => 'JOB-', 'pad' => 3, 'institution_scoped' => true],
            'maker' => ['table' => 'makers', 'column' => 'code', 'prefix' => 'SUP-', 'pad' => 4, 'institution_scoped' => false],
            'contract_plan' => ['table' => 'contract_plans', 'column' => 'code', 'prefix' => 'PLN-', 'pad' => 4, 'institution_scoped' => false],
            'product' => ['table' => 'uniform_products', 'column' => 'product_code', 'prefix' => 'PRD-', 'pad' => 4, 'institution_scoped' => false],
            'staff_no' => ['table' => 'employees', 'column' => 'staff_no', 'prefix' => 'EMP-', 'pad' => 4, 'institution_scoped' => true],
            default => null,
        };
    }

    public static function ensure(?string $code, string $type, ?int $institutionId = null): string
    {
        $trimmed = is_string($code) ? trim($code) : '';
        if ($trimmed !== '') {
            return $trimmed;
        }

        $config = self::configFor($type);
        if (! $config) {
            throw new \InvalidArgumentException("Unknown master code type: {$type}");
        }

        return self::generate([
            'table' => $config['table'],
            'column' => $config['column'],
            'prefix' => $config['prefix'],
            'pad' => $config['pad'],
            'institution_id' => $config['institution_scoped'] ? $institutionId : null,
        ]);
    }

    private static function tableHasInstitution(string $table): bool
    {
        return in_array($table, ['departments', 'job_types', 'employees', 'delivery_locations'], true);
    }
}
