<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerUser;
use App\Models\Department;
use App\Models\DeliveryLocation;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\JobType;
use App\Models\MedicalInstitution;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use App\Support\MasterCodeGenerator;
use App\Support\Pagination;
use Illuminate\Validation\Rule;

class MasterController extends Controller
{
    /**
     * Check if user is system admin
     */
    private function checkSystemAdmin()
    {
        $user = request()->user();
        $admin = $user->adminUser;
        
        if (!$admin || $admin->role !== 'system_admin') {
            abort(403, __('pages.flash_2fb39bebc0'));
        }
    }
    
    // 医療機関管理
    public function institutions(Request $request): View
    {
        $this->checkSystemAdmin();
        
        $institutions = MedicalInstitution::with('institutionAdmin.user')
            ->orderBy('name')
            ->paginate(Pagination::perPage($request))->withQueryString();
        return view('admin.masters.institutions', ['institutions' => $institutions]);
    }

    public function institutionCreate(): View
    {
        $this->checkSystemAdmin();
        return view('admin.masters.institution-create');
    }

    public function institutionStore(Request $request): RedirectResponse
    {
        $this->checkSystemAdmin();
        
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'unique:medical_institutions,code'],
            'notification_email' => ['required', 'email'],
            'billing_closing_day' => ['nullable', 'integer', 'min:1', 'max:28'],
            'sales_person_name' => ['nullable', 'string', 'max:255'],
            // Overall Manager Account fields
            'manager_name' => ['required', 'string', 'max:255'],
            'manager_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'manager_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $data['code'] = MasterCodeGenerator::next('institution');

        // Create medical institution
        $institution = MedicalInstitution::create([
            'name' => $data['name'],
            'code' => $data['code'],
            'notification_email' => $data['notification_email'],
            'billing_closing_day' => $data['billing_closing_day'] ?? 1,
            'sales_person_name' => $data['sales_person_name'] ?? null,
        ]);

        // Create overall manager user account
        $managerUser = User::create([
            'name' => $data['manager_name'],
            'email' => $data['manager_email'],
            'password' => Hash::make($data['manager_password']),
            'type' => 'customer_admin',
        ]);

        // Create customer user record for institution admin
        CustomerUser::create([
            'user_id' => $managerUser->id,
            'medical_institution_id' => $institution->id,
            'role' => 'institution_admin',
            'department_id' => null,
        ]);

        return redirect()->route('admin.masters.institutions')
            ->with('status', __('pages.flash_7a1c720190'));
    }

    public function sendWelcomeEmail(Request $request, MedicalInstitution $institution): RedirectResponse
    {
        $this->checkSystemAdmin();

        $institution->load('institutionAdmin.user');
        $manager = $institution->institutionAdmin?->user;

        if (! $manager) {
            return back()->with('error', __('pages.no_institution_admin'));
        }

        try {
            app(\App\Services\WelcomeEmailService::class)->send(
                $institution,
                $manager,
                $request->user()
            );
        } catch (\Throwable $e) {
            return back()->with('error', __('pages.welcome_email_failed', ['error' => $e->getMessage()]));
        }

        return back()->with('status', __('pages.welcome_email_sent', ['email' => $manager->email]));
    }

    public function welcomeEmailHistory(Request $request, MedicalInstitution $institution): View
    {
        $this->checkSystemAdmin();

        $histories = \App\Models\WelcomeEmailSendHistory::with(['user', 'sentBy'])
            ->where('medical_institution_id', $institution->id)
            ->orderByDesc('sent_at')
            ->paginate(Pagination::perPage($request))
            ->withQueryString();

        return view('admin.masters.welcome-email-history', [
            'institution' => $institution,
            'histories' => $histories,
        ]);
    }

    public function institutionEdit(MedicalInstitution $institution): View
    {
        $this->checkSystemAdmin();
        $institution->load('institutionAdmin.user');
        return view('admin.masters.institution-edit', ['institution' => $institution]);
    }

    public function institutionUpdate(Request $request, MedicalInstitution $institution): RedirectResponse
    {
        $this->checkSystemAdmin();
        
        $validationRules = [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('medical_institutions', 'code')->ignore($institution->id)],
            'notification_email' => ['required', 'email'],
            'billing_closing_day' => ['nullable', 'integer', 'min:1', 'max:28'],
            'sales_person_name' => ['nullable', 'string', 'max:255'],
            // Manager account update fields (optional)
            'manager_name' => ['nullable', 'string', 'max:255'],
            'manager_email' => ['nullable', 'email', 'max:255'],
            'manager_password' => ['nullable', 'string', 'min:8'],
            'manager_password_confirmation' => ['nullable', 'string'],
        ];

        // If password is provided, require confirmation
        if ($request->filled('manager_password')) {
            $validationRules['manager_password'][] = 'confirmed';
        }

        $data = $request->validate($validationRules);

        // Update medical institution (code is immutable / auto-assigned)
        unset($data['code']);

        $institution->update([
            'name' => $data['name'],
            'notification_email' => $data['notification_email'],
            'billing_closing_day' => $data['billing_closing_day'] ?? 1,
            'sales_person_name' => $data['sales_person_name'] ?? null,
        ]);

        // Update manager account if provided
        $institution->load('institutionAdmin.user');
        if ($institution->institutionAdmin && $institution->institutionAdmin->user) {
            $managerUser = $institution->institutionAdmin->user;
            
            $updateData = [];
            if (!empty($data['manager_name'])) {
                $updateData['name'] = $data['manager_name'];
            }
            if (!empty($data['manager_email'])) {
                // Check if email is unique (excluding current user)
                $emailExists = User::where('email', $data['manager_email'])
                    ->where('id', '!=', $managerUser->id)
                    ->exists();
                if ($emailExists) {
                    return back()->withErrors(['manager_email' => 'このメールアドレスは既に使用されています。'])->withInput();
                }
                $updateData['email'] = $data['manager_email'];
            }
            if (!empty($data['manager_password'])) {
                $updateData['password'] = Hash::make($data['manager_password']);
            }
            
            if (!empty($updateData)) {
                $managerUser->update($updateData);
            }
        }

        return redirect()->route('admin.masters.institutions')
            ->with('status', __('pages.flash_b55a31f43a'));
    }

    public function institutionDestroy(MedicalInstitution $institution): RedirectResponse
    {
        $this->checkSystemAdmin();

        $employeeCount = Employee::where('medical_institution_id', $institution->id)->count();
        $activeLoanQty = (int) EmployeeLoan::query()
            ->whereHas('employee', fn ($q) => $q->where('medical_institution_id', $institution->id))
            ->where('status', 'in_use')
            ->where('quantity', '>', 0)
            ->sum('quantity');

        if ($activeLoanQty > 0) {
            return redirect()->route('admin.masters.institutions')
                ->with('error', __('pages.institution_delete_blocked_active_loans', ['count' => $activeLoanQty]));
        }

        if ($employeeCount > 0) {
            return redirect()->route('admin.masters.institutions')
                ->with('error', __('pages.institution_delete_blocked_employees', ['count' => $employeeCount]));
        }

        $institution->delete();

        return redirect()->route('admin.masters.institutions')
            ->with('status', __('pages.flash_bba3f3dfcf'));
    }

    // 部署管理
    public function departments(Request $request): View
    {
        $this->checkSystemAdmin();
        $institutionId = $request->input('institution_id');
        $query = Department::with('medicalInstitution');

        if ($institutionId) {
            $query->where('medical_institution_id', $institutionId);
        }

        $departments = $query->orderBy('name')->paginate(Pagination::perPage($request))->withQueryString();
        $institutions = MedicalInstitution::orderBy('name')->get();

        return view('admin.masters.departments', [
            'departments' => $departments,
            'institutions' => $institutions,
        ]);
    }

    public function departmentCreate(): View
    {
        $this->checkSystemAdmin();
        $institutions = MedicalInstitution::orderBy('name')->get();
        return view('admin.masters.department-create', ['institutions' => $institutions]);
    }

    public function departmentStore(Request $request): RedirectResponse
    {
        $this->checkSystemAdmin();
        $data = $request->validate([
            'medical_institution_id' => ['required', 'exists:medical_institutions,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
        ]);

        $data['code'] = MasterCodeGenerator::next('department', (int) $data['medical_institution_id']);

        Department::create($data);

        return redirect()->route('admin.masters.departments')
            ->with('status', __('pages.flash_2ea71ecf52'));
    }

    public function departmentEdit(Department $department): View
    {
        $this->checkSystemAdmin();
        $institutions = MedicalInstitution::orderBy('name')->get();
        return view('admin.masters.department-edit', [
            'department' => $department,
            'institutions' => $institutions,
        ]);
    }

    public function departmentShow(Department $department): View
    {
        $user = request()->user();
        $admin = $user->adminUser;
        if (! $admin || ! in_array($admin->role, ['system_admin', 'operation'], true)) {
            abort(403, __('pages.flash_2fb39bebc0'));
        }

        $department->load('medicalInstitution');
        $inventory = app(\App\Services\LoanInventoryService::class)->departmentInventory($department);

        return view('shared.department-loan-inventory', [
            'department' => $department,
            'inventory' => $inventory,
            'backUrl' => route('admin.loan-status.index', array_filter([
                'medical_institution_id' => $department->medical_institution_id,
                'department_id' => $department->id,
            ])),
            'employeeShowRoute' => fn ($employee) => route('admin.employees.show', $employee),
        ]);
    }

    public function departmentUpdate(Request $request, Department $department): RedirectResponse
    {
        $this->checkSystemAdmin();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
        ]);

        unset($data['code']);

        $department->update($data);

        return redirect()->route('admin.masters.departments')
            ->with('status', __('pages.flash_1dc4495bb8'));
    }

    public function departmentDestroy(Department $department): RedirectResponse
    {
        $this->checkSystemAdmin();
        $department->delete();
        return redirect()->route('admin.masters.departments')
            ->with('status', __('pages.flash_af38158b22'));
    }

    // 職種管理
    public function jobTypes(Request $request): View
    {
        $this->checkSystemAdmin();
        $institutionId = $request->input('institution_id');
        $query = JobType::with('medicalInstitution');

        if ($institutionId) {
            $query->where('medical_institution_id', $institutionId);
        }

        $jobTypes = $query->orderBy('name')->paginate(Pagination::perPage($request))->withQueryString();
        $institutions = MedicalInstitution::orderBy('name')->get();

        return view('admin.masters.job-types', [
            'jobTypes' => $jobTypes,
            'institutions' => $institutions,
        ]);
    }

    public function jobTypeCreate(): View
    {
        $this->checkSystemAdmin();
        $institutions = MedicalInstitution::orderBy('name')->get();
        return view('admin.masters.job-type-create', ['institutions' => $institutions]);
    }

    public function jobTypeStore(Request $request): RedirectResponse
    {
        $this->checkSystemAdmin();
        $data = $request->validate([
            'medical_institution_id' => ['required', 'exists:medical_institutions,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
        ]);

        $data['code'] = MasterCodeGenerator::next('job_type', (int) $data['medical_institution_id']);

        JobType::create($data);

        return redirect()->route('admin.masters.job-types')
            ->with('status', __('pages.flash_0d3d2e43d0'));
    }

    public function jobTypeEdit(JobType $jobType): View
    {
        $this->checkSystemAdmin();
        $institutions = MedicalInstitution::orderBy('name')->get();
        return view('admin.masters.job-type-edit', [
            'jobType' => $jobType,
            'institutions' => $institutions,
        ]);
    }

    public function jobTypeUpdate(Request $request, JobType $jobType): RedirectResponse
    {
        $this->checkSystemAdmin();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
        ]);

        unset($data['code']);

        $jobType->update($data);

        return redirect()->route('admin.masters.job-types')
            ->with('status', __('pages.flash_0c4bf5aad8'));
    }

    public function jobTypeDestroy(JobType $jobType): RedirectResponse
    {
        $this->checkSystemAdmin();
        $jobType->delete();
        return redirect()->route('admin.masters.job-types')
            ->with('status', __('pages.flash_a0a027191e'));
    }

    // 納品場所管理
    public function deliveryLocations(Request $request): View
    {
        $this->checkSystemAdmin();
        $institutionId = $request->input('institution_id');
        $query = DeliveryLocation::with('medicalInstitution');

        if ($institutionId) {
            $query->where('medical_institution_id', $institutionId);
        }

        $locations = $query->orderBy('name')->paginate(Pagination::perPage($request))->withQueryString();
        $institutions = MedicalInstitution::orderBy('name')->get();

        return view('admin.masters.delivery-locations', [
            'locations' => $locations,
            'institutions' => $institutions,
        ]);
    }

    public function deliveryLocationCreate(): View
    {
        $this->checkSystemAdmin();
        $institutions = MedicalInstitution::orderBy('name')->get();
        return view('admin.masters.delivery-location-create', ['institutions' => $institutions]);
    }

    public function deliveryLocationStore(Request $request): RedirectResponse
    {
        $this->checkSystemAdmin();
        $data = $request->validate([
            'medical_institution_id' => ['required', 'exists:medical_institutions,id'],
            'name' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        DeliveryLocation::create($data);

        return redirect()->route('admin.masters.delivery-locations')
            ->with('status', __('pages.flash_ee4ee84877'));
    }

    public function deliveryLocationEdit(DeliveryLocation $deliveryLocation): View
    {
        $this->checkSystemAdmin();
        $institutions = MedicalInstitution::orderBy('name')->get();
        return view('admin.masters.delivery-location-edit', [
            'location' => $deliveryLocation,
            'institutions' => $institutions,
        ]);
    }

    public function deliveryLocationUpdate(Request $request, DeliveryLocation $deliveryLocation): RedirectResponse
    {
        $this->checkSystemAdmin();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $deliveryLocation->update($data);

        return redirect()->route('admin.masters.delivery-locations')
            ->with('status', __('pages.flash_7069dee0cc'));
    }

    public function deliveryLocationDestroy(DeliveryLocation $deliveryLocation): RedirectResponse
    {
        $this->checkSystemAdmin();
        $deliveryLocation->delete();
        return redirect()->route('admin.masters.delivery-locations')
            ->with('status', __('pages.flash_22fcddcd54'));
    }
}
