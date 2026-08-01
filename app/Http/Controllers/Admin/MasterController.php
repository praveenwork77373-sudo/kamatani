<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerUser;
use App\Models\Department;
use App\Models\DeliveryLocation;
use App\Models\JobType;
use App\Models\MedicalInstitution;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

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
            abort(403, 'システム管理者のみアクセス可能です。');
        }
    }
    
    // 医療機関管理
    public function institutions(Request $request): View
    {
        $this->checkSystemAdmin();
        
        $institutions = MedicalInstitution::with('institutionAdmin.user')
            ->orderBy('name')
            ->paginate(20);
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
            'code' => ['required', 'string', 'max:50', 'unique:medical_institutions,code'],
            'billing_model' => ['required', 'in:fixed_per_person,point_based,point_usage'],
            'notification_email' => ['required', 'email'],
            'per_person_loan_limit' => ['nullable', 'integer', 'min:0'],
            'point_unit_price' => ['nullable', 'numeric', 'min:0'],
            'billing_closing_day' => ['nullable', 'integer', 'min:1', 'max:28'],
            'sales_person_name' => ['nullable', 'string', 'max:255'],
            // Overall Manager Account fields
            'manager_name' => ['required', 'string', 'max:255'],
            'manager_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'manager_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Create medical institution
        $institution = MedicalInstitution::create([
            'name' => $data['name'],
            'code' => $data['code'],
            'billing_model' => $data['billing_model'] === 'point_based' ? 'point_usage' : $data['billing_model'],
            'notification_email' => $data['notification_email'],
            'per_person_loan_limit' => $data['per_person_loan_limit'] ?? null,
            'point_unit_price' => $data['point_unit_price'] ?? null,
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
            ->with('status', '医療機関と全体管理者アカウントを登録しました。');
    }

    public function sendWelcomeEmail(Request $request, MedicalInstitution $institution): RedirectResponse
    {
        $this->checkSystemAdmin();

        $institution->load('institutionAdmin.user');
        $manager = $institution->institutionAdmin?->user;

        if (! $manager) {
            return back()->with('error', '全体管理者が登録されていません。');
        }

        try {
            app(\App\Services\WelcomeEmailService::class)->send(
                $institution,
                $manager,
                $request->user()
            );
        } catch (\Throwable $e) {
            return back()->with('error', '利用開始メールの送信に失敗しました: '.$e->getMessage());
        }

        return back()->with('status', '利用開始メールを送信しました（'.$manager->email.'）。仮パスワードで初回ログイン後に変更が必要です。');
    }

    public function welcomeEmailHistory(MedicalInstitution $institution): View
    {
        $this->checkSystemAdmin();

        $histories = \App\Models\WelcomeEmailSendHistory::with(['user', 'sentBy'])
            ->where('medical_institution_id', $institution->id)
            ->orderByDesc('sent_at')
            ->paginate(20);

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
            'code' => ['required', 'string', 'max:50'],
            'billing_model' => ['required', 'in:fixed_per_person,point_based,point_usage'],
            'notification_email' => ['required', 'email'],
            'per_person_loan_limit' => ['nullable', 'integer', 'min:0'],
            'point_unit_price' => ['nullable', 'numeric', 'min:0'],
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

        // Update medical institution
        $institution->update([
            'name' => $data['name'],
            'code' => $data['code'],
            'billing_model' => $data['billing_model'] === 'point_based' ? 'point_usage' : $data['billing_model'],
            'notification_email' => $data['notification_email'],
            'per_person_loan_limit' => $data['per_person_loan_limit'] ?? null,
            'point_unit_price' => $data['point_unit_price'] ?? null,
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
            ->with('status', '医療機関情報を更新しました。');
    }

    public function institutionDestroy(MedicalInstitution $institution): RedirectResponse
    {
        $this->checkSystemAdmin();
        $institution->delete();

        return redirect()->route('admin.masters.institutions')
            ->with('status', '医療機関を削除しました。');
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

        $departments = $query->orderBy('name')->paginate(20)->withQueryString();
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
            'code' => ['required', 'string', 'max:50'],
        ]);

        Department::create($data);

        return redirect()->route('admin.masters.departments')
            ->with('status', '部署を登録しました。');
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

    public function departmentUpdate(Request $request, Department $department): RedirectResponse
    {
        $this->checkSystemAdmin();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
        ]);

        $department->update($data);

        return redirect()->route('admin.masters.departments')
            ->with('status', '部署を更新しました。');
    }

    public function departmentDestroy(Department $department): RedirectResponse
    {
        $this->checkSystemAdmin();
        $department->delete();
        return redirect()->route('admin.masters.departments')
            ->with('status', '部署を削除しました。');
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

        $jobTypes = $query->orderBy('name')->paginate(20)->withQueryString();
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
            'code' => ['required', 'string', 'max:50'],
        ]);

        JobType::create($data);

        return redirect()->route('admin.masters.job-types')
            ->with('status', '職種を登録しました。');
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
            'code' => ['required', 'string', 'max:50'],
        ]);

        $jobType->update($data);

        return redirect()->route('admin.masters.job-types')
            ->with('status', '職種を更新しました。');
    }

    public function jobTypeDestroy(JobType $jobType): RedirectResponse
    {
        $this->checkSystemAdmin();
        $jobType->delete();
        return redirect()->route('admin.masters.job-types')
            ->with('status', '職種を削除しました。');
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

        $locations = $query->orderBy('name')->paginate(20)->withQueryString();
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
            ->with('status', '納品場所を登録しました。');
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
            ->with('status', '納品場所を更新しました。');
    }

    public function deliveryLocationDestroy(DeliveryLocation $deliveryLocation): RedirectResponse
    {
        $this->checkSystemAdmin();
        $deliveryLocation->delete();
        return redirect()->route('admin.masters.delivery-locations')
            ->with('status', '納品場所を削除しました。');
    }
}
