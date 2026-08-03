<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\DeliveryLocation;
use App\Models\JobType;
use App\Support\MasterCodeGenerator;
use App\Support\Pagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MasterController extends Controller
{
    /**
     * Check if user is institution admin
     */
    private function checkInstitutionAdmin()
    {
        $user = request()->user();
        $customer = $user->customerUser;
        
        if (!$customer || $customer->role !== 'institution_admin') {
            abort(403, '医療機関全体管理者のみアクセス可能です。');
        }
    }
    
    // 部署管理
    public function departments(Request $request): View
    {
        $this->checkInstitutionAdmin();
        $user = $request->user();
        $customer = $user->customerUser;
        
        $query = Department::where('medical_institution_id', $customer->medical_institution_id);
        
        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }
        
        $departments = $query->orderBy('code')->paginate(Pagination::perPage($request))->withQueryString();
        
        return view('customer.masters.departments', [
            'departments' => $departments,
        ]);
    }
    
    public function departmentCreate(): View
    {
        $this->checkInstitutionAdmin();
        return view('customer.masters.department-create');
    }
    
    public function departmentStore(Request $request): RedirectResponse
    {
        $this->checkInstitutionAdmin();
        $user = $request->user();
        $customer = $user->customerUser;
        
        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $institutionId = $customer->medical_institution_id;
        $data['medical_institution_id'] = $institutionId;
        $data['code'] = MasterCodeGenerator::next('department', $institutionId);

        Department::create($data);
        
        return redirect()->route('customer.masters.departments')
            ->with('status', '部署を登録しました。');
    }
    
    public function departmentEdit(Department $department): View
    {
        $this->checkInstitutionAdmin();
        $user = request()->user();
        $customer = $user->customerUser;
        
        if ($department->medical_institution_id !== $customer->medical_institution_id) {
            abort(403);
        }
        
        return view('customer.masters.department-edit', [
            'department' => $department,
        ]);
    }
    
    public function departmentUpdate(Request $request, Department $department): RedirectResponse
    {
        $this->checkInstitutionAdmin();
        $user = $request->user();
        $customer = $user->customerUser;
        
        if ($department->medical_institution_id !== $customer->medical_institution_id) {
            abort(403);
        }
        
        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        unset($data['code']);

        $department->update($data);
        
        return redirect()->route('customer.masters.departments')
            ->with('status', '部署を更新しました。');
    }
    
    public function departmentDestroy(Department $department): RedirectResponse
    {
        $this->checkInstitutionAdmin();
        $user = request()->user();
        $customer = $user->customerUser;
        
        if ($department->medical_institution_id !== $customer->medical_institution_id) {
            abort(403);
        }
        
        $department->delete();
        
        return redirect()->route('customer.masters.departments')
            ->with('status', '部署を削除しました。');
    }
    
    // 職種管理
    public function jobTypes(Request $request): View
    {
        $this->checkInstitutionAdmin();
        $user = $request->user();
        $customer = $user->customerUser;
        
        $query = JobType::where('medical_institution_id', $customer->medical_institution_id);
        
        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }
        
        $jobTypes = $query->orderBy('code')->paginate(Pagination::perPage($request))->withQueryString();
        
        return view('customer.masters.job-types', [
            'jobTypes' => $jobTypes,
        ]);
    }
    
    public function jobTypeCreate(): View
    {
        $this->checkInstitutionAdmin();
        return view('customer.masters.job-type-create');
    }
    
    public function jobTypeStore(Request $request): RedirectResponse
    {
        $this->checkInstitutionAdmin();
        $user = $request->user();
        $customer = $user->customerUser;
        
        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $institutionId = $customer->medical_institution_id;
        $data['medical_institution_id'] = $institutionId;
        $data['code'] = MasterCodeGenerator::next('job_type', $institutionId);

        JobType::create($data);
        
        return redirect()->route('customer.masters.job-types')
            ->with('status', '職種を登録しました。');
    }
    
    public function jobTypeEdit(JobType $jobType): View
    {
        $this->checkInstitutionAdmin();
        $user = request()->user();
        $customer = $user->customerUser;
        
        if ($jobType->medical_institution_id !== $customer->medical_institution_id) {
            abort(403);
        }
        
        return view('customer.masters.job-type-edit', [
            'jobType' => $jobType,
        ]);
    }
    
    public function jobTypeUpdate(Request $request, JobType $jobType): RedirectResponse
    {
        $this->checkInstitutionAdmin();
        $user = $request->user();
        $customer = $user->customerUser;
        
        if ($jobType->medical_institution_id !== $customer->medical_institution_id) {
            abort(403);
        }
        
        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        unset($data['code']);

        $jobType->update($data);
        
        return redirect()->route('customer.masters.job-types')
            ->with('status', '職種を更新しました。');
    }
    
    public function jobTypeDestroy(JobType $jobType): RedirectResponse
    {
        $this->checkInstitutionAdmin();
        $user = request()->user();
        $customer = $user->customerUser;
        
        if ($jobType->medical_institution_id !== $customer->medical_institution_id) {
            abort(403);
        }
        
        $jobType->delete();
        
        return redirect()->route('customer.masters.job-types')
            ->with('status', '職種を削除しました。');
    }
    
    // 納品場所管理
    public function deliveryLocations(Request $request): View
    {
        $this->checkInstitutionAdmin();
        $user = $request->user();
        $customer = $user->customerUser;
        
        $query = DeliveryLocation::where('medical_institution_id', $customer->medical_institution_id);
        
        if ($search = $request->string('q')->toString()) {
            $query->where('name', 'like', "%{$search}%");
        }
        
        $locations = $query->orderBy('name')->paginate(Pagination::perPage($request))->withQueryString();
        
        return view('customer.masters.delivery-locations', [
            'locations' => $locations,
        ]);
    }
    
    public function deliveryLocationCreate(): View
    {
        $this->checkInstitutionAdmin();
        return view('customer.masters.delivery-location-create');
    }
    
    public function deliveryLocationStore(Request $request): RedirectResponse
    {
        $this->checkInstitutionAdmin();
        $user = $request->user();
        $customer = $user->customerUser;
        
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        
        $data['medical_institution_id'] = $customer->medical_institution_id;
        
        DeliveryLocation::create($data);
        
        return redirect()->route('customer.masters.delivery-locations')
            ->with('status', '納品場所を登録しました。');
    }
    
    public function deliveryLocationEdit(DeliveryLocation $deliveryLocation): View
    {
        $this->checkInstitutionAdmin();
        $user = request()->user();
        $customer = $user->customerUser;
        
        if ($deliveryLocation->medical_institution_id !== $customer->medical_institution_id) {
            abort(403);
        }
        
        return view('customer.masters.delivery-location-edit', [
            'location' => $deliveryLocation,
        ]);
    }
    
    public function deliveryLocationUpdate(Request $request, DeliveryLocation $deliveryLocation): RedirectResponse
    {
        $this->checkInstitutionAdmin();
        $user = $request->user();
        $customer = $user->customerUser;
        
        if ($deliveryLocation->medical_institution_id !== $customer->medical_institution_id) {
            abort(403);
        }
        
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        
        $deliveryLocation->update($data);
        
        return redirect()->route('customer.masters.delivery-locations')
            ->with('status', '納品場所を更新しました。');
    }
    
    public function deliveryLocationDestroy(DeliveryLocation $deliveryLocation): RedirectResponse
    {
        $this->checkInstitutionAdmin();
        $user = request()->user();
        $customer = $user->customerUser;
        
        if ($deliveryLocation->medical_institution_id !== $customer->medical_institution_id) {
            abort(403);
        }
        
        $deliveryLocation->delete();
        
        return redirect()->route('customer.masters.delivery-locations')
            ->with('status', '納品場所を削除しました。');
    }
}

