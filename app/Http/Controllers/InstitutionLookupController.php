<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\JobType;
use App\Models\MedicalInstitution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstitutionLookupController extends Controller
{
    public function departmentsJobTypes(Request $request, MedicalInstitution $institution): JsonResponse
    {
        $this->authorizeInstitutionAccess($request, (int) $institution->id);

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

    public function departments(Request $request): JsonResponse
    {
        $institutionId = (int) $request->input('medical_institution_id');
        if ($institutionId <= 0) {
            return response()->json([]);
        }

        $this->authorizeInstitutionAccess($request, $institutionId);

        $departments = Department::query()
            ->where('medical_institution_id', $institutionId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($departments);
    }

    private function authorizeInstitutionAccess(Request $request, int $institutionId): void
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        if ($user->adminUser) {
            return;
        }

        $customer = $user->customerUser;
        if (! $customer || (int) $customer->medical_institution_id !== $institutionId) {
            abort(403);
        }
    }
}
