<?php

namespace App\Services;

use App\Models\BillingSnapshot;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlanBillingExportService
{
    public function download(BillingSnapshot $snapshot): StreamedResponse
    {
        $data = $snapshot->data ?? [];
        $institutionName = $data['institution']['name']
            ?? $snapshot->medicalInstitution?->name
            ?? 'institution';
        $ref = $data['reference_date'] ?? optional($snapshot->target_date)->format('Y-m-d') ?? now()->toDateString();

        $spreadsheet = $this->buildSpreadsheet($data, $ref, $institutionName);

        $safeName = preg_replace('/[\\\\\/\:\*\?\"\<\>\|]+/u', '_', $institutionName) ?: 'billing';
        $filename = sprintf('請求集計_%s_%s.xlsx', $safeName, $ref);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function buildSpreadsheet(array $data, string $ref, string $institutionName): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();

        // Sheet 1: 請求明細（プラン集計）
        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('請求明細');
        $this->fillPlanSummarySheet($summarySheet, $data, $ref, $institutionName);

        // Sheet 2: 個人明細
        $staffSheet = $spreadsheet->createSheet();
        $staffSheet->setTitle('個人明細');
        $this->fillStaffSheet($staffSheet, $data, $ref, $institutionName);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private function fillPlanSummarySheet($sheet, array $data, string $ref, string $institutionName): void
    {
        $sheet->setCellValue('A1', '請求集計（公式：契約プラン×割当人数×単価）');
        $sheet->setCellValue('A2', '医療機関');
        $sheet->setCellValue('B2', $institutionName);
        $sheet->setCellValue('A3', '基準日');
        $sheet->setCellValue('B3', $ref);
        $sheet->setCellValue('C3', '締め日');
        $sheet->setCellValue('D3', $data['closing_day'] ?? '');
        $sheet->setCellValue('A4', '請求金額の根拠');
        $sheet->setCellValue('B4', $data['summary']['billing_basis'] ?? '対象人数（割当）×単価');

        $headers = [
            'A5' => '契約名',
            'B5' => 'プランコード',
            'C5' => '職種',
            'D5' => '貸与セット枚数',
            'E5' => '勤務体制',
            'F5' => '契約口数',
            'G5' => '対象人数（割当）',
            'H5' => '月額単価（税抜）',
            'I5' => '請求金額（割当×単価）',
            'J5' => '契約口数×単価（参考）',
        ];
        foreach ($headers as $cell => $label) {
            $sheet->setCellValue($cell, $label);
        }
        $this->styleHeaderRow($sheet, 'A5:J5');

        $row = 6;
        foreach ($data['plan_summary'] ?? [] as $plan) {
            $sheet->setCellValue("A{$row}", $plan['plan_name'] ?? '');
            $sheet->setCellValue("B{$row}", $plan['plan_code'] ?? '');
            $sheet->setCellValue("C{$row}", $plan['job_type_label'] ?? '');
            $sheet->setCellValue("D{$row}", $plan['rentable_points'] ?? '');
            $sheet->setCellValue("E{$row}", $plan['work_style'] ?? '');
            $sheet->setCellValue("F{$row}", $plan['contracted_slots'] ?? 0);
            $sheet->setCellValue("G{$row}", $plan['assigned_count'] ?? 0);
            $sheet->setCellValue("H{$row}", $plan['unit_price_ex_tax'] ?? 0);
            $sheet->setCellValue("I{$row}", $plan['amount_ex_tax'] ?? 0);
            $sheet->setCellValue("J{$row}", $plan['amount_by_contract_slots'] ?? 0);
            $row++;
        }

        $sheet->setCellValue("A{$row}", '合計');
        $sheet->setCellValue("F{$row}", $data['summary']['total_contract_slots'] ?? 0);
        $sheet->setCellValue("G{$row}", $data['summary']['total_targets'] ?? 0);
        $sheet->setCellValue("I{$row}", $data['summary']['billing_amount'] ?? 0);
        $sheet->setCellValue("J{$row}", $data['summary']['contract_capacity_amount'] ?? 0);
        $sheet->getStyle("A{$row}:J{$row}")->getFont()->setBold(true);

        $noteRow = $row + 2;
        $sheet->setCellValue("A{$noteRow}", '【公式】請求金額＝基準日時点の在籍割当人数×単価。契約口数×単価は参考（契約容量）。');

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function fillStaffSheet($sheet, array $data, string $ref, string $institutionName): void
    {
        $sheet->setCellValue('A1', '個人明細（契約プラン割当）');
        $sheet->setCellValue('A2', '医療機関');
        $sheet->setCellValue('B2', $institutionName);
        $sheet->setCellValue('A3', '基準日');
        $sheet->setCellValue('B3', $ref);

        $headers = [
            'A5' => '基準日',
            'B5' => '医療機関名',
            'C5' => '職員番号',
            'D5' => '氏名',
            'E5' => '部署',
            'F5' => '入職日',
            'G5' => '退職日',
            'H5' => 'プラン名',
            'I5' => 'プランコード',
            'J5' => '契約口数（行）',
            'K5' => '単価（税抜）',
            'L5' => '金額（税抜）',
        ];
        foreach ($headers as $cell => $label) {
            $sheet->setCellValue($cell, $label);
        }
        $this->styleHeaderRow($sheet, 'A5:L5');

        $row = 6;
        foreach ($data['staff_rows'] ?? [] as $staff) {
            $sheet->setCellValue("A{$row}", $staff['reference_date'] ?? $ref);
            $sheet->setCellValue("B{$row}", $staff['hospital_name'] ?? $institutionName);
            $sheet->setCellValue("C{$row}", $staff['staff_no'] ?? '');
            $sheet->setCellValue("D{$row}", $staff['person_name'] ?? '');
            $sheet->setCellValue("E{$row}", $staff['department_name'] ?? '');
            $sheet->setCellValue("F{$row}", $staff['join_date'] ?? '');
            $sheet->setCellValue("G{$row}", $staff['retire_date'] ?? '');
            $sheet->setCellValue("H{$row}", $staff['plan_name'] ?? '');
            $sheet->setCellValue("I{$row}", $staff['plan_code'] ?? '');
            $sheet->setCellValue("J{$row}", $staff['contract_count'] ?? 1);
            $sheet->setCellValue("K{$row}", $staff['unit_price_ex_tax'] ?? 0);
            $sheet->setCellValue("L{$row}", $staff['amount_ex_tax'] ?? 0);
            $row++;
        }

        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function styleHeaderRow($sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2E8F0'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);
    }
}
