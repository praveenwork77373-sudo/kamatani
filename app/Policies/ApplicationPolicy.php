<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) ($user->customerUser || $this->activeAdmin($user));
    }

    public function view(User $user, Application $application): bool
    {
        if ($this->activeAdmin($user)) {
            return true;
        }

        $customer = $user->customerUser;
        if (!$customer) {
            return false;
        }

        if ((int) $application->medical_institution_id !== (int) $customer->medical_institution_id) {
            return false;
        }

        if ($customer->role === 'department_admin' && $customer->department_id) {
            return (int) $application->department_id === (int) $customer->department_id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return (bool) $user->customerUser;
    }

    public function cancel(User $user, Application $application): bool
    {
        return $this->view($user, $application) && (bool) $user->customerUser;
    }

    public function approve(User $user, Application $application): bool
    {
        $admin = $this->activeAdmin($user);
        return $admin && in_array($admin->role, ['system_admin', 'operation'], true);
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
