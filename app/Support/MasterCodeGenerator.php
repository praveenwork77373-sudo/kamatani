<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MasterCodeGenerator
{
    /**
     * Always generate the next unique code for a master type.
     * Manual codes are not accepted — uniqueness is system-owned.
     *
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
                // Match PRD-0001 and PRD-0001-L (size variant)
                if (preg_match('/^(\d+)/', $suffix, $m)) {
                    $max = max($max, (int) $m[1]);
                }
            }
        }

        do {
            $max++;
            $candidate = $prefix.str_pad((string) $max, $pad, '0', STR_PAD_LEFT);
        } while (
            self::exists($table, $column, $candidate, null, $institutionId)
            || self::existsWithSizeSuffix($table, $column, $candidate)
        );

        return $candidate;
    }

    /**
     * Product codes may be stored as BASE or BASE-SIZE; treat both as taken.
     */
    private static function existsWithSizeSuffix(string $table, string $column, string $baseCode): bool
    {
        if ($table !== 'uniform_products' || $column !== 'product_code') {
            return false;
        }

        return DB::table($table)->where($column, 'like', $baseCode.'-%')->exists();
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
            'uniform_category' => ['table' => 'uniform_categories', 'column' => 'code', 'prefix' => 'CAT-', 'pad' => 4, 'institution_scoped' => false],
            'staff_no' => ['table' => 'employees', 'column' => 'staff_no', 'prefix' => 'EMP-', 'pad' => 4, 'institution_scoped' => true],
            default => null,
        };
    }

    /**
     * Next auto code for type (always). Ignores any user-submitted value.
     */
    public static function next(string $type, ?int $institutionId = null): string
    {
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

    /**
     * Assign code for create (always auto) or keep existing on update.
     * $submitted is ignored — codes are system-owned.
     */
    public static function ensure(?string $submitted, string $type, ?int $institutionId = null, ?string $existing = null): string
    {
        if (is_string($existing) && trim($existing) !== '') {
            return trim($existing);
        }

        return self::next($type, $institutionId);
    }

    private static function tableHasInstitution(string $table): bool
    {
        return in_array($table, ['departments', 'job_types', 'employees', 'delivery_locations'], true);
    }
}
