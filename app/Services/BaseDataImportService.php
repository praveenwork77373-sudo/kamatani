<?php

namespace App\Services;

use App\Models\ContractPlan;
use App\Models\CustomerContractPlan;
use App\Models\Maker;
use App\Models\MedicalInstitution;
use App\Models\UniformProduct;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Best-fit import of client ベースデータ Excel into the current schema.
 *
 * Mapping notes (intentionally lossy — see plan):
 * - Per-category / その他2 / その他3 point caps are summed into one rentable_points total.
 * - Male/female rows for the same plan code are merged (union products, max total points).
 * - 対帳合先 unit price is ignored (column is empty in the source file).
 * - Plan codes are stored as {customer_code}-{plan_code} to avoid cross-hospital collisions.
 */
class BaseDataImportService
{
    /** @var array<string, int> */
    private array $stats = [
        'makers' => 0,
        'products' => 0,
        'institutions' => 0,
        'plans' => 0,
        'plan_links' => 0,
        'stub_products' => 0,
        'warnings' => 0,
    ];

    /** @var list<string> */
    private array $warnings = [];

    /**
     * @return array{stats: array<string, int>, warnings: list<string>}
     */
    public function import(string $path, bool $dryRun = false): array
    {
        if (! is_file($path)) {
            throw new \InvalidArgumentException("Base data file not found: {$path}");
        }

        $spreadsheet = IOFactory::load($path);
        $makers = $this->readSheet($spreadsheet, '仕入先マスタ');
        $products = $this->readSheet($spreadsheet, '商品マスタ');
        $leases = $this->readSheet($spreadsheet, 'リース契約マスタ');
        $plans = $this->readSheet($spreadsheet, '契約プランマスタ');

        $runner = function () use ($makers, $products, $leases, $plans) {
            $makerMap = $this->importMakers($makers);
            $this->importProducts($products, $makerMap);
            $leaseMap = $this->importInstitutions($leases);
            $this->importPlans($plans, $leaseMap);
        };

        if ($dryRun) {
            DB::beginTransaction();
            try {
                $runner();
            } finally {
                DB::rollBack();
            }
        } else {
            DB::transaction($runner);
        }

        return [
            'stats' => $this->stats,
            'warnings' => $this->warnings,
        ];
    }

    /**
     * @return list<array<int, mixed>>
     */
    private function readSheet($spreadsheet, string $title): array
    {
        $sheet = $spreadsheet->getSheetByName($title);
        if (! $sheet) {
            throw new \RuntimeException("Sheet not found: {$title}");
        }

        return $sheet->toArray(null, true, true, false);
    }

    /**
     * @param  list<array<int, mixed>>  $rows
     * @return array<string, Maker>
     */
    private function importMakers(array $rows): array
    {
        $map = [];
        foreach (array_slice($rows, 1) as $row) {
            $code = $this->str($row[0] ?? null);
            $name = $this->str($row[1] ?? null);
            if ($code === '' || $name === '') {
                continue;
            }

            $maker = Maker::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'tel' => $this->str($row[2] ?? null) ?: null,
                    'fax' => $this->str($row[3] ?? null) ?: null,
                    'notes' => $this->str($row[4] ?? null) ?: null,
                    'is_active' => true,
                ]
            );
            $map[$code] = $maker;
            $this->stats['makers']++;
        }

        return $map;
    }

    /**
     * @param  list<array<int, mixed>>  $rows
     * @param  array<string, Maker>  $makerMap
     */
    private function importProducts(array $rows, array $makerMap): void
    {
        $seenBase = [];

        foreach (array_slice($rows, 1) as $row) {
            $baseCode = $this->str($row[0] ?? null);
            if ($baseCode === '') {
                continue;
            }

            // Prefer the first (usually more complete) row when Excel has duplicate codes.
            if (isset($seenBase[$baseCode])) {
                continue;
            }
            $seenBase[$baseCode] = true;

            $name = $this->str($row[1] ?? null) ?: $baseCode;
            $supplierCode = $this->str($row[2] ?? null);
            $makerProductCode = $this->str($row[3] ?? null) ?: null;
            $color = $this->str($row[4] ?? null) ?: null;
            $sizes = [];
            for ($i = 5; $i <= 15; $i++) {
                $size = $this->str($row[$i] ?? null);
                if ($size !== '') {
                    $sizes[] = $size;
                }
            }

            $makerId = ($supplierCode !== '' && isset($makerMap[$supplierCode]))
                ? $makerMap[$supplierCode]->id
                : null;

            $category = $this->inferCategory($name);
            $gender = $this->inferGender($name);

            if ($sizes === []) {
                $this->upsertProductRow(
                    productCode: $baseCode,
                    name: $name,
                    makerId: $makerId,
                    makerProductCode: $makerProductCode,
                    colorName: $color,
                    category: $category,
                    gender: $gender,
                    size: '-',
                );
                continue;
            }

            foreach ($sizes as $size) {
                $this->upsertProductRow(
                    productCode: $baseCode.'-'.$size,
                    name: $name,
                    makerId: $makerId,
                    makerProductCode: $makerProductCode,
                    colorName: $color,
                    category: $category,
                    gender: $gender,
                    size: $size,
                );
            }
        }
    }

    private function upsertProductRow(
        string $productCode,
        string $name,
        ?int $makerId,
        ?string $makerProductCode,
        ?string $colorName,
        string $category,
        string $gender,
        string $size,
    ): void {
        UniformProduct::updateOrCreate(
            ['product_code' => $productCode],
            [
                'name' => $name,
                'maker_id' => $makerId,
                'maker_product_code' => $makerProductCode,
                'color_name' => $colorName,
                'category' => $category,
                'gender' => $gender,
                'size' => $size,
                'stock_quantity' => 0,
            ]
        );
        $this->stats['products']++;
    }

    /**
     * @param  list<array<int, mixed>>  $rows
     * @return array<string, array{institution: MedicalInstitution, start: ?Carbon, end: ?Carbon}>
     */
    private function importInstitutions(array $rows): array
    {
        $map = [];
        // Row 0 = header, row 1 = format hint ("8桁数字")
        foreach (array_slice($rows, 2) as $row) {
            $code = $this->str($row[0] ?? null);
            if ($code === '' || ! ctype_digit($code)) {
                // Skip format hint / empty
                if ($code !== '' && $code !== '8桁数字') {
                    $this->warn("Skipped lease row with non-numeric customer code: {$code}");
                }
                continue;
            }

            $onoe = $this->str($row[1] ?? null) ?: null;
            $name = $this->str($row[2] ?? null);
            if ($name === '') {
                $this->warn("Skipped lease row without institution name: {$code}");
                continue;
            }

            $closingRaw = $this->str($row[3] ?? null);
            $closingDay = 1;
            if (preg_match('/(\d+)/', $closingRaw, $m)) {
                $closingDay = max(1, min(28, (int) $m[1]));
            }

            $sales = $this->str($row[4] ?? null) ?: null;
            $start = $this->parseExcelDate($row[5] ?? null);
            $end = $this->parseExcelDate($row[6] ?? null);

            $institution = MedicalInstitution::firstOrNew(['code' => $code]);
            $institution->fill([
                'name' => $name,
                'onoe_code' => $onoe,
                'billing_closing_day' => $closingDay,
                'sales_person_name' => $sales,
            ]);
            if (! $institution->exists || blank($institution->notification_email)) {
                $institution->notification_email = "import+{$code}@example.invalid";
            }
            $institution->save();

            $map[$code] = [
                'institution' => $institution,
                'start' => $start,
                'end' => $end,
            ];
            $this->stats['institutions']++;
        }

        return $map;
    }

    /**
     * @param  list<array<int, mixed>>  $rows
     * @param  array<string, array{institution: MedicalInstitution, start: ?Carbon, end: ?Carbon}>  $leaseMap
     */
    private function importPlans(array $rows, array $leaseMap): void
    {
        /** @var array<string, array{name: string, customer: string, plan_code: string, points: int, price: float, product_refs: array<string, true>}> $grouped */
        $grouped = [];

        foreach (array_slice($rows, 1) as $row) {
            $customer = $this->str($row[1] ?? null);
            $planCode = $this->str($row[2] ?? null);
            $planName = $this->str($row[3] ?? null);
            if ($customer === '' || $planCode === '' || $planName === '') {
                continue;
            }

            if (! isset($leaseMap[$customer])) {
                $this->warn("Plan {$planCode} references unknown customer {$customer}");
                continue;
            }

            $key = $customer.'|'.$planCode;
            $points = $this->sumPointCaps($row);
            $price = (float) preg_replace('/[^\d.]/', '', (string) ($row[26] ?? '0'));

            $refs = $this->collectProductRefs($row);

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'name' => $planName,
                    'customer' => $customer,
                    'plan_code' => $planCode,
                    'points' => $points,
                    'price' => $price,
                    'product_refs' => $refs,
                ];
            } else {
                // Merge gender variants: keep max points/price, union products
                $grouped[$key]['points'] = max($grouped[$key]['points'], $points);
                $grouped[$key]['price'] = max($grouped[$key]['price'], $price);
                $grouped[$key]['product_refs'] = $grouped[$key]['product_refs'] + $refs;
            }
        }

        foreach ($grouped as $group) {
            $uniqueCode = $group['customer'].'-'.$group['plan_code'];
            $productIds = $this->resolveProductIds(array_keys($group['product_refs']));

            $plan = ContractPlan::updateOrCreate(
                ['code' => $uniqueCode],
                [
                    'name' => $group['name'],
                    'rentable_points' => $group['points'],
                    'unit_price_ex_tax' => $group['price'],
                    'job_type_label' => $group['name'],
                    'notes' => 'Imported from 260805 base data (best-fit). Source plan '.$group['plan_code'].'. Category slot caps flattened to total points.',
                    'is_active' => true,
                ]
            );
            $plan->uniformProducts()->sync($productIds);
            $this->stats['plans']++;

            $lease = $leaseMap[$group['customer']];
            CustomerContractPlan::updateOrCreate(
                [
                    'medical_institution_id' => $lease['institution']->id,
                    'contract_plan_id' => $plan->id,
                ],
                [
                    'contract_count' => 0,
                    'start_date' => $lease['start']?->toDateString(),
                    'end_date' => $lease['end']?->toDateString(),
                    'status' => 'confirmed',
                    'confirmed_at' => now(),
                    'notes' => 'Imported from 260805 base data',
                ]
            );
            $this->stats['plan_links']++;
        }
    }

    /**
     * Sum 上衣/下衣/その他/その他2/その他3 caps into one rentable_points value.
     * Deliberately does NOT enforce per-slot limits at runtime (deferred).
     *
     * @param  array<int, mixed>  $row
     */
    private function sumPointCaps(array $row): int
    {
        $total = 0;
        foreach ([8, 13, 17, 21, 25] as $idx) {
            $raw = $this->str($row[$idx] ?? null);
            if ($raw !== '' && is_numeric($raw)) {
                $total += (int) $raw;
            }
        }

        return $total;
    }

    /**
     * @param  array<int, mixed>  $row
     * @return array<string, true>
     */
    private function collectProductRefs(array $row): array
    {
        $refs = [];
        // Top 1-3, bottom 1-4, other 1-3, other2 1-3, other3 1-3
        foreach ([5, 6, 7, 9, 10, 11, 12, 14, 15, 16, 18, 19, 20, 22, 23, 24] as $idx) {
            $ref = $this->normalizeProductRef($this->str($row[$idx] ?? null));
            if ($ref !== '') {
                $refs[$ref] = true;
            }
        }

        return $refs;
    }

    /**
     * @param  list<string>  $refs
     * @return list<int>
     */
    private function resolveProductIds(array $refs): array
    {
        $ids = [];

        foreach ($refs as $ref) {
            $matched = UniformProduct::query()
                ->where(function ($q) use ($ref) {
                    $q->where('product_code', $ref)
                        ->orWhere('product_code', 'like', $ref.'-%');
                })
                ->pluck('id')
                ->all();

            if ($matched === []) {
                // Create incomplete stub so plan links are not dropped
                $stub = UniformProduct::create([
                    'product_code' => $ref,
                    'name' => $ref.' (import stub)',
                    'maker_id' => null,
                    'maker_product_code' => null,
                    'color_name' => null,
                    'category' => 'other',
                    'gender' => 'unisex',
                    'size' => '-',
                    'stock_quantity' => 0,
                ]);
                $matched = [$stub->id];
                $this->stats['stub_products']++;
                $this->warn("Created stub product for plan ref missing from 商品マスタ: {$ref}");
            }

            foreach ($matched as $id) {
                $ids[$id] = true;
            }
        }

        return array_map('intval', array_keys($ids));
    }

    private function normalizeProductRef(string $raw): string
    {
        if ($raw === '') {
            return '';
        }

        // Strip full-width / half-width parenthetical color suffixes: AY5522（ネイビー）
        $cleaned = preg_replace('/\s*[（(].*?[）)]\s*/u', '', $raw) ?? $raw;

        return trim($cleaned);
    }

    private function inferCategory(string $name): string
    {
        if (preg_match('/パンツ|ズボン|スラックス/u', $name)) {
            return 'bottom';
        }
        if (preg_match('/靴|シューズ|サンダル/u', $name)) {
            return 'shoe';
        }
        if (preg_match('/スクラブ|上衣|ジャケット|コート|ワンピース|チュニック|ポロ|シャツ|診察衣/u', $name)) {
            return 'top';
        }

        return 'other';
    }

    private function inferGender(string $name): string
    {
        $hasMale = (bool) preg_match('/男性|男子|メンズ/u', $name);
        $hasFemale = (bool) preg_match('/女性|女子|レディース/u', $name);

        if ($hasMale && ! $hasFemale) {
            return 'male';
        }
        if ($hasFemale && ! $hasMale) {
            return 'female';
        }

        return 'unisex';
    }

    private function parseExcelDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value));
            } catch (\Throwable) {
                return null;
            }
        }

        $str = $this->str($value);
        if ($str === '') {
            return null;
        }

        try {
            return Carbon::parse($str);
        } catch (\Throwable) {
            $this->warn("Could not parse date: {$str}");

            return null;
        }
    }

    private function str(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }

    private function warn(string $message): void
    {
        $this->warnings[] = $message;
        $this->stats['warnings']++;
    }
}
