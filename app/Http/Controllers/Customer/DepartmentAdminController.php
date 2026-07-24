<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CustomerUser;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class DepartmentAdminController extends Controller
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

    public function index(Request $request): View
    {
        $this->checkInstitutionAdmin();
        
        $user = $request->user();
        $customer = $user->customerUser;
        
        $query = CustomerUser::with(['user', 'department'])
            ->where('medical_institution_id', $customer->medical_institution_id)
            ->where('role', 'department_admin');
        
        if ($search = $request->string('q')->toString()) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        if ($departmentId = $request->input('department_id')) {
            $query->where('department_id', $departmentId);
        }
        
        $departmentAdmins = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();
        $departments = Department::where('medical_institution_id', $customer->medical_institution_id)
            ->orderBy('name')
            ->get();
        
        return view('customer.masters.department-admins', [
            'departmentAdmins' => $departmentAdmins,
            'departments' => $departments,
            'selectedDepartmentId' => $departmentId,
            'searchQuery' => $search,
        ]);
    }

    public function create(): View
    {
        $this->checkInstitutionAdmin();
        
        $user = request()->user();
        $customer = $user->customerUser;
        
        $departments = Department::where('medical_institution_id', $customer->medical_institution_id)
            ->orderBy('name')
            ->get();
        
        return view('customer.masters.department-admin-create', [
            'departments' => $departments,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->checkInstitutionAdmin();
        
        $user = request()->user();
        $customer = $user->customerUser;
        
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'department_id' => ['required', 'exists:departments,id'],
        ]);
        
        // Verify department belongs to the institution
        $department = Department::findOrFail($data['department_id']);
        if ($department->medical_institution_id !== $customer->medical_institution_id) {
            return back()->withErrors(['department_id' => '選択された部署は無効です。'])->withInput();
        }
        
        $newUser = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'type' => 'customer_admin',
        ]);
        
        CustomerUser::create([
            'user_id' => $newUser->id,
            'medical_institution_id' => $customer->medical_institution_id,
            'role' => 'department_admin',
            'department_id' => $data['department_id'],
        ]);
        
        return redirect()->route('customer.masters.department-admins')
            ->with('status', '部署管理者を登録しました。');
    }

    public function edit(CustomerUser $customerUser): View
    {
        $this->checkInstitutionAdmin();
        
        $user = request()->user();
        $customer = $user->customerUser;
        
        // Verify the customer user belongs to the same institution
        if ($customerUser->medical_institution_id !== $customer->medical_institution_id || $customerUser->role !== 'department_admin') {
            abort(403, 'アクセス権限がありません。');
        }
        
        $departments = Department::where('medical_institution_id', $customer->medical_institution_id)
            ->orderBy('name')
            ->get();
        
        return view('customer.masters.department-admin-edit', [
            'customerUser' => $customerUser->load('user', 'department'),
            'departments' => $departments,
        ]);
    }

    public function update(Request $request, CustomerUser $customerUser): RedirectResponse
    {
        $this->checkInstitutionAdmin();
        
        $user = request()->user();
        $customer = $user->customerUser;
        
        // Verify the customer user belongs to the same institution
        if ($customerUser->medical_institution_id !== $customer->medical_institution_id || $customerUser->role !== 'department_admin') {
            abort(403, 'アクセス権限がありません。');
        }
        
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $customerUser->user_id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'department_id' => ['required', 'exists:departments,id'],
        ]);
        
        // Verify department belongs to the institution
        $department = Department::findOrFail($data['department_id']);
        if ($department->medical_institution_id !== $customer->medical_institution_id) {
            return back()->withErrors(['department_id' => '選択された部署は無効です。'])->withInput();
        }
        
        $customerUser->user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);
        
        if (!empty($data['password'])) {
            $customerUser->user->update([
                'password' => Hash::make($data['password']),
            ]);
        }
        
        $customerUser->update([
            'department_id' => $data['department_id'],
        ]);
        
        return redirect()->route('customer.masters.department-admins')
            ->with('status', '部署管理者情報を更新しました。');
    }

    public function destroy(CustomerUser $customerUser): RedirectResponse
    {
        $this->checkInstitutionAdmin();
        
        $user = request()->user();
        $customer = $user->customerUser;
        
        // Verify the customer user belongs to the same institution
        if ($customerUser->medical_institution_id !== $customer->medical_institution_id || $customerUser->role !== 'department_admin') {
            abort(403, 'アクセス権限がありません。');
        }
        
        // Don't allow deleting yourself
        if ($customerUser->user_id === $user->id) {
            return back()->withErrors(['error' => '自分自身を削除することはできません。']);
        }
        
        $customerUser->user->delete(); // This will cascade delete the customer_user
        
        return redirect()->route('customer.masters.department-admins')
            ->with('status', '部署管理者を削除しました。');
    }
}



