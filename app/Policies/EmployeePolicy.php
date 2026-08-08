<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) ($user->customerUser || $this->activeAdmin($user));
    }

    public function view(User $user, Employee $employee): bool
    {
        if ($this->activeAdmin($user)) {
            return true;
        }

        $customer = $user->customerUser;
        if (!$customer) {
            return false;
        }

        if ((int) $employee->medical_institution_id !== (int) $customer->medical_institution_id) {
            return false;
        }

        if ($customer->role === 'department_admin') {
            if (! $customer->department_id) {
                return false;
            }

            return $employee->isAffiliatedWithDepartment((int) $customer->department_id);
        }

        return true;
    }

    public function create(User $user): bool
    {
        $customer = $user->customerUser;
        if ($customer && in_array($customer->role, ['institution_admin', 'department_admin'], true)) {
            return true;
        }

        $admin = $this->activeAdmin($user);
        return $admin && in_array($admin->role, ['system_admin', 'operation', 'sales', 'accounting'], true);
    }

    public function update(User $user, Employee $employee): bool
    {
        return $this->view($user, $employee) && (bool) $user->customerUser;
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $this->update($user, $employee);
    }

    private function activeAdmin(User $user)
    {
        $admin = $user->adminUser()->withTrashed()->first();
        if (!$admin || $admin->trashed() || $admin->isBlocked()) {
            return null;
        }
        return $admin;
    }
}
