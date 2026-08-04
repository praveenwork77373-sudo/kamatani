<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ApplicationPdfService
{
    public function formTypes(): array
    {
        return ApplicationDocument::TYPES;
    }

    public function formLabel(string $type): string
    {
        return (new ApplicationDocument(['form_type' => $type]))->label();
    }

    /**
     * Issue all PDF forms for an accepted application. Returns created documents.
     *
     * @return Collection<int, ApplicationDocument>
     */
    public function issueAll(Application $application, ?User $issuer = null): Collection
    {
        $docs = collect();
        foreach ($this->formTypes() as $type) {
            $docs->push($this->issue($application, $type, $issuer));
        }

        return $docs;
    }

    public function issue(Application $application, string $formType, ?User $issuer = null): ApplicationDocument
    {
        if (! in_array($formType, $this->formTypes(), true)) {
            throw new \InvalidArgumentException("Unknown form type: {$formType}");
        }

        $application->loadMissing([
            'medicalInstitution',
            'department',
            'employee.contractPlanAssignments.customerContractPlan.contractPlan',
            'deliveryLocation',
            'items.uniformProduct.maker',
            'requestedBy',
        ]);

        $latest = ApplicationDocument::query()
            ->where('application_id', $application->id)
            ->where('form_type', $formType)
            ->where('is_latest', true)
            ->first();

        $version = $latest ? ($latest->version + 1) : 1;

        if ($latest) {
            $latest->update(['is_latest' => false]);
        }

        $payload = $this->buildPayload($application, $formType);
        $pdf = Pdf::loadView('pdfs.application-form', $payload)
            ->setPaper('a4', 'portrait')
            ->setOption('defaultFont', 'ipaexg')
            ->setOption('isRemoteEnabled', false)
            ->setOption('isHtml5ParserEnabled', true);

        $dir = 'application-pdfs/'.$application->id;
        $filename = sprintf(
            '%s_v%d_%s.pdf',
            $formType,
            $version,
            now()->format('YmdHis')
        );
        $path = $dir.'/'.$filename;

        Storage::disk('local')->put($path, $pdf->output());

        return ApplicationDocument::create([
            'application_id' => $application->id,
            'form_type' => $formType,
            'version' => $version,
            'is_latest' => true,
            'file_path' => $path,
            'original_name' => sprintf(
                'APP-%04d_%s_v%d.pdf',
                $application->id,
                Str::slug($this->formLabel($formType), '_'),
                $version
            ),
            'issued_by_user_id' => $issuer?->id,
            'issued_at' => now(),
        ]);
    }

    public function absolutePath(ApplicationDocument $document): string
    {
        return Storage::disk('local')->path($document->file_path);
    }

    private function buildPayload(Application $application, string $formType): array
    {
        $employee = $application->employee;
        $plans = collect();
        if ($employee) {
            $plans = app(EmployeePlanEligibilityService::class)
                ->activeAssignments($employee)
                ->map(fn ($a) => $a->customerContractPlan?->contractPlan)
                ->filter()
                ->unique('id')
                ->values();
        }

        $planNames = $plans->pluck('name')->filter()->implode(' / ') ?: '-';
        $planCodes = $plans->pluck('code')->filter()->implode(' / ') ?: '-';

        return [
            'formType' => $formType,
            'formTitle' => $this->formLabel($formType),
            'fontPath' => resource_path('fonts/ipaexg.ttf'),
            'application' => $application,
            'applicationNo' => sprintf('APP-%04d', $application->id),
            'orderDate' => optional($application->accepted_at ?? $application->approved_at ?? $application->created_at)->format('Y-m-d'),
            'dueDate' => optional($application->start_date)->format('Y-m-d'),
            'institution' => $application->medicalInstitution,
            'department' => $application->department,
            'employee' => $employee,
            'deliveryLocation' => $application->deliveryLocation,
            'salesPerson' => $application->medicalInstitution?->sales_person_name ?: '-',
            'planNames' => $planNames,
            'planCodes' => $planCodes,
            'applicationTypeLabel' => $application->typeLabel(),
            'memo1' => $application->memo1
                ?: ($application->special_approval_reason ? __('pages.special_approval_prefix').$application->special_approval_reason : ''),
            'memo2' => $application->memo2 ?: '',
            'items' => $application->items,
            'issuedAt' => now()->format('Y-m-d H:i'),
            'audienceNote' => match ($formType) {
                ApplicationDocument::TYPE_SHIPPING_SALES => __('pages.pdf_note_sales'),
                ApplicationDocument::TYPE_SHIPPING_WAREHOUSE => __('pages.pdf_note_warehouse'),
                ApplicationDocument::TYPE_DELIVERY_NOTICE => __('pages.pdf_note_delivery'),
                ApplicationDocument::TYPE_WORK_SLIP => __('pages.pdf_note_work'),
                default => '※受注（準備）用',
            },
        ];
    }
}
