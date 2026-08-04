<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\JobType;
use App\Models\MedicalInstitution;
use App\Models\UniformProduct;
use App\Models\UniformRecommendation;
use App\Support\Pagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UniformRecommendationController extends Controller
{
    public function index(Request $request): View
    {
        $query = UniformRecommendation::with(['medicalInstitution', 'department', 'jobType', 'uniformProduct.maker'])
            ->orderByDesc('id');

        if ($institutionId = $request->integer('medical_institution_id')) {
            $query->where('medical_institution_id', $institutionId);
        }

        $recommendations = $query->paginate(Pagination::perPage($request))->withQueryString();
        $institutions = MedicalInstitution::orderBy('name')->get();

        return view('admin.uniform-recommendations.index', compact('recommendations', 'institutions'));
    }

    public function create(): View
    {
        return view('admin.uniform-recommendations.create', [
            'institutions' => MedicalInstitution::orderBy('name')->get(),
            'products' => UniformProduct::with('maker')->orderBy('product_code')->get(),
            'departments' => Department::orderBy('name')->get(),
            'jobTypes' => JobType::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        UniformRecommendation::create($data);

        return redirect()->route('admin.uniform-recommendations.index')
            ->with('status', __('pages.flash_85ec2ff438'));
    }

    public function edit(UniformRecommendation $uniformRecommendation): View
    {
        return view('admin.uniform-recommendations.edit', [
            'recommendation' => $uniformRecommendation,
            'institutions' => MedicalInstitution::orderBy('name')->get(),
            'products' => UniformProduct::with('maker')->orderBy('product_code')->get(),
            'departments' => Department::orderBy('name')->get(),
            'jobTypes' => JobType::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, UniformRecommendation $uniformRecommendation): RedirectResponse
    {
        $uniformRecommendation->update($this->validated($request));

        return redirect()->route('admin.uniform-recommendations.index')
            ->with('status', __('pages.flash_321f42e413'));
    }

    public function destroy(UniformRecommendation $uniformRecommendation): RedirectResponse
    {
        $uniformRecommendation->delete();

        return redirect()->route('admin.uniform-recommendations.index')
            ->with('status', __('pages.flash_72b494b5b3'));
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'medical_institution_id' => ['required', 'exists:medical_institutions,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'job_type_id' => ['nullable', 'exists:job_types,id'],
            'uniform_product_id' => ['required', 'exists:uniform_products,id'],
            'is_required' => ['nullable', 'boolean'],
            'is_recommended' => ['nullable', 'boolean'],
        ]);

        $data['is_required'] = $request->boolean('is_required');
        $data['is_recommended'] = $request->boolean('is_recommended', true);
        if (! $data['is_required'] && ! $data['is_recommended']) {
            $data['is_recommended'] = true;
        }

        return $data;
    }
}
