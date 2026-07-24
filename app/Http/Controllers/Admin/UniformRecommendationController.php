<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\JobType;
use App\Models\MedicalInstitution;
use App\Models\UniformProduct;
use App\Models\UniformRecommendation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UniformRecommendationController extends Controller
{
    public function index(Request $request): View
    {
        $institutionId = $request->query('medical_institution_id');

        $institutions = MedicalInstitution::orderBy('name')->get();

        $query = UniformRecommendation::with(['medicalInstitution', 'department', 'jobType', 'uniformProduct'])
            ->orderBy('medical_institution_id')
            ->orderBy('department_id')
            ->orderBy('job_type_id')
            ->orderByDesc('is_required')
            ->orderBy('uniform_product_id');

        if ($institutionId) {
            $query->where('medical_institution_id', $institutionId);
        }

        $recommendations = $query->paginate(30)->withQueryString();

        return view('admin.uniform-recommendations.index', [
            'recommendations' => $recommendations,
            'institutions' => $institutions,
            'selectedInstitutionId' => $institutionId,
        ]);
    }

    public function create(): View
    {
        $institutions = MedicalInstitution::orderBy('name')->get();
        $institutionId = old('medical_institution_id');
        $products = UniformProduct::orderBy('product_code')->get();

        return view('admin.uniform-recommendations.create', [
            'institutions' => $institutions,
            'departments' => $this->departmentsForInstitution($institutionId),
            'jobTypes' => $this->jobTypesForInstitution($institutionId),
            'products' => $products,
            'selectedDepartmentId' => old('department_id'),
            'selectedJobTypeId' => old('job_type_id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'medical_institution_id' => ['required', 'exists:medical_institutions,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'job_type_id' => ['nullable', 'exists:job_types,id'],
            'uniform_product_id' => ['required', 'exists:uniform_products,id'],
            'is_required' => ['sometimes', 'boolean'],
            'is_recommended' => ['sometimes', 'boolean'],
        ]);

        $data['is_required'] = $request->boolean('is_required');
        $data['is_recommended'] = $request->boolean('is_recommended', true);

        UniformRecommendation::create($data);

        return redirect()
            ->route('admin.uniform-recommendations.index')
            ->with('status', '推奨・必須ユニフォーム設定を登録しました。');
    }

    public function edit(UniformRecommendation $uniformRecommendation): View
    {
        $institutions = MedicalInstitution::orderBy('name')->get();
        $institutionId = old('medical_institution_id', $uniformRecommendation->medical_institution_id);
        $products = UniformProduct::orderBy('product_code')->get();

        return view('admin.uniform-recommendations.edit', [
            'recommendation' => $uniformRecommendation,
            'institutions' => $institutions,
            'departments' => $this->departmentsForInstitution($institutionId),
            'jobTypes' => $this->jobTypesForInstitution($institutionId),
            'products' => $products,
            'selectedDepartmentId' => old('department_id', $uniformRecommendation->department_id),
            'selectedJobTypeId' => old('job_type_id', $uniformRecommendation->job_type_id),
        ]);
    }

    public function update(Request $request, UniformRecommendation $uniformRecommendation): RedirectResponse
    {
        $data = $request->validate([
            'medical_institution_id' => ['required', 'exists:medical_institutions,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'job_type_id' => ['nullable', 'exists:job_types,id'],
            'uniform_product_id' => ['required', 'exists:uniform_products,id'],
            'is_required' => ['sometimes', 'boolean'],
            'is_recommended' => ['sometimes', 'boolean'],
        ]);

        $data['is_required'] = $request->boolean('is_required');
        $data['is_recommended'] = $request->boolean('is_recommended', true);

        $uniformRecommendation->update($data);

        return redirect()
            ->route('admin.uniform-recommendations.index')
            ->with('status', '推奨・必須ユニフォーム設定を更新しました。');
    }

    public function destroy(UniformRecommendation $uniformRecommendation): RedirectResponse
    {
        $uniformRecommendation->delete();

        return redirect()
            ->route('admin.uniform-recommendations.index')
            ->with('status', '推奨・必須ユニフォーム設定を削除しました。');
    }

    private function departmentsForInstitution(mixed $institutionId)
    {
        if (!$institutionId) {
            return collect();
        }

        return Department::where('medical_institution_id', $institutionId)->orderBy('name')->get();
    }

    private function jobTypesForInstitution(mixed $institutionId)
    {
        if (!$institutionId) {
            return collect();
        }

        return JobType::where('medical_institution_id', $institutionId)->orderBy('name')->get();
    }
}




