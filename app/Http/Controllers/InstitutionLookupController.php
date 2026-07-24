<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\JobType;
use App\Models\MedicalInstitution;
use Illuminate\Http\JsonResponse;

class InstitutionLookupController extends Controller
{
    public function departmentsJobTypes(MedicalInstitution $institution): JsonResponse
    {
        $departments = Department::query()
            ->where('medical_institution_id', $institution->id)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $jobTypes = JobType::query()
            ->where('medical_institution_id', $institution->id)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json([
            'departments' => $departments,
            'job_types' => $jobTypes,
        ]);
    }
}
