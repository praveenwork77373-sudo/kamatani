@php
    $user = auth()->user();
    $customer = $user->customerUser;
    $admin = $user->adminUser;
    $adminRole = $admin->role ?? null;
@endphp

{{-- Dashboard --}}
<a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'nav-link is-active' : 'nav-link' }}">
    <i data-lucide="layout-dashboard" class="nav-ico"></i>
    <span>{{ __('menu.dashboard') }}</span>
</a>

@if($customer)
    <p class="nav-section-label">{{ __('menu.menu_applications') }}</p>
    <a href="{{ route('applications.index') }}" class="{{ request()->is('applications*') ? 'nav-link is-active' : 'nav-link' }}">
        <i data-lucide="clipboard-check" class="nav-ico"></i>
        <span>{{ __('menu.applications') }}</span>
        @php
            $pendingCount = \App\Models\Application::where('status', 'pending')
                ->where('medical_institution_id', $customer->medical_institution_id)
                ->when($customer->role === 'department_admin' && $customer->department_id, function ($q) use ($customer) {
                    $q->where('department_id', $customer->department_id);
                })
                ->count();
        @endphp
        @if($pendingCount > 0)
            <span class="nav-badge">{{ $pendingCount > 9 ? '9+' : $pendingCount }}</span>
        @endif
    </a>

    <p class="nav-section-label">{{ __('menu.menu_employees') }}</p>
    <a href="{{ route('employees.index') }}" class="{{ request()->is('employees*') ? 'nav-link is-active' : 'nav-link' }}">
        <i data-lucide="users" class="nav-ico"></i>
        <span>{{ __('menu.employees') }}</span>
    </a>

    @if($customer->role === 'institution_admin')
        <p class="nav-section-label">{{ __('menu.menu_master_data') }}</p>
        <a href="{{ route('customer.masters.departments') }}" class="{{ request()->is('customer/masters/departments*') ? 'nav-link is-active' : 'nav-link' }}">
            <i data-lucide="briefcase" class="nav-ico"></i>
            <span>{{ __('menu.departments') }}</span>
        </a>
        <a href="{{ route('customer.masters.job-types') }}" class="{{ request()->is('customer/masters/job-types*') ? 'nav-link is-active' : 'nav-link' }}">
            <i data-lucide="user-check" class="nav-ico"></i>
            <span>{{ __('menu.job_types') }}</span>
        </a>
        <a href="{{ route('customer.masters.delivery-locations') }}" class="{{ request()->is('customer/masters/delivery-locations*') ? 'nav-link is-active' : 'nav-link' }}">
            <i data-lucide="map-pin" class="nav-ico"></i>
            <span>{{ __('menu.delivery_locations') }}</span>
        </a>
        <a href="{{ route('customer.masters.department-admins') }}" class="{{ request()->is('customer/masters/department-admins*') ? 'nav-link is-active' : 'nav-link' }}">
            <i data-lucide="user-cog" class="nav-ico"></i>
            <span>{{ __('menu.department_admins') }}</span>
        </a>

        <p class="nav-section-label">{{ __('menu.menu_contract_billing') }}</p>
        <a href="{{ route('customer.contract-plans.index') }}" class="{{ request()->is('customer/contract-plans*') ? 'nav-link is-active' : 'nav-link' }}">
            <i data-lucide="file-signature" class="nav-ico"></i>
            <span>{{ __('menu.contract_info') }}</span>
        </a>
    @endif
@endif

@if($admin)
    @if(in_array($adminRole, ['system_admin', 'operation'], true))
        <p class="nav-section-label">{{ __('menu.menu_applications') }}</p>
        <a href="{{ route('admin.applications.index') }}" class="{{ request()->is('admin/applications*') && ! request()->is('admin/orders*') ? 'nav-link is-active' : 'nav-link' }}">
            <i data-lucide="clipboard-check" class="nav-ico"></i>
            <span>{{ __('menu.applications') }}</span>
            @php
                $pendingCount = \App\Models\Application::whereIn('status', ['pending', 'waiting_special_approval'])->count();
            @endphp
            @if($pendingCount > 0)
                <span class="nav-badge">{{ $pendingCount > 9 ? '9+' : $pendingCount }}</span>
            @endif
        </a>
        <a href="{{ route('admin.orders.index') }}" class="{{ request()->is('admin/orders*') ? 'nav-link is-active' : 'nav-link' }}">
            <i data-lucide="package-check" class="nav-ico"></i>
            <span>{{ __('menu.orders') }}</span>
        </a>

        <p class="nav-section-label">{{ __('menu.menu_products_inventory') }}</p>
        <a href="{{ route('admin.uniform-products.index') }}" class="{{ request()->is('admin/uniform-products*') ? 'nav-link is-active' : 'nav-link' }}">
            <i data-lucide="shirt" class="nav-ico"></i>
            <span>{{ __('menu.uniform_products') }}</span>
        </a>
        <a href="{{ route('admin.uniform-recommendations.index') }}" class="{{ request()->is('admin/uniform-recommendations*') ? 'nav-link is-active' : 'nav-link' }}">
            <i data-lucide="sparkles" class="nav-ico"></i>
            <span>{{ __('menu.uniform_recommendations') }}</span>
        </a>
    @endif

    @if($adminRole === 'system_admin')
        <p class="nav-section-label">{{ __('menu.menu_master_data') }}</p>
        <a href="{{ route('admin.masters.institutions') }}" class="{{ request()->is('admin/masters/institutions*') ? 'nav-link is-active' : 'nav-link' }}">
            <i data-lucide="building" class="nav-ico"></i>
            <span>{{ __('menu.institutions') }}</span>
        </a>
        <a href="{{ route('admin.masters.departments') }}" class="{{ request()->is('admin/masters/departments*') ? 'nav-link is-active' : 'nav-link' }}">
            <i data-lucide="briefcase" class="nav-ico"></i>
            <span>{{ __('menu.departments') }}</span>
        </a>
        <a href="{{ route('admin.masters.job-types') }}" class="{{ request()->is('admin/masters/job-types*') ? 'nav-link is-active' : 'nav-link' }}">
            <i data-lucide="user-check" class="nav-ico"></i>
            <span>{{ __('menu.job_types') }}</span>
        </a>
        <a href="{{ route('admin.masters.delivery-locations') }}" class="{{ request()->is('admin/masters/delivery-locations*') ? 'nav-link is-active' : 'nav-link' }}">
            <i data-lucide="map-pin" class="nav-ico"></i>
            <span>{{ __('menu.delivery_locations') }}</span>
        </a>
        <a href="{{ route('admin.employees.index') }}" class="{{ request()->is('admin/employees*') ? 'nav-link is-active' : 'nav-link' }}">
            <i data-lucide="users" class="nav-ico"></i>
            <span>{{ __('menu.all_employees') }}</span>
        </a>
    @endif

    @if(in_array($adminRole, ['system_admin', 'contract_plan_manager', 'sales'], true))
        <p class="nav-section-label">{{ __('menu.menu_contract_billing') }}</p>
        @if(in_array($adminRole, ['system_admin', 'contract_plan_manager'], true))
            <a href="{{ route('admin.makers.index') }}" class="{{ request()->is('admin/makers*') ? 'nav-link is-active' : 'nav-link' }}">
                <i data-lucide="factory" class="nav-ico"></i>
                <span>{{ __('menu.makers') }}</span>
            </a>
            <a href="{{ route('admin.uniform-categories.index') }}" class="{{ request()->is('admin/uniform-categories*') ? 'nav-link is-active' : 'nav-link' }}">
                <i data-lucide="tags" class="nav-ico"></i>
                <span>{{ __('menu.uniform_categories') }}</span>
            </a>
            <a href="{{ route('admin.contract-plans.index') }}" class="{{ request()->is('admin/contract-plans*') ? 'nav-link is-active' : 'nav-link' }}">
                <i data-lucide="clipboard-list" class="nav-ico"></i>
                <span>{{ __('menu.contract_plans') }}</span>
            </a>
            <a href="{{ route('admin.customer-contract-plans.index') }}" class="{{ request()->is('admin/customer-contract-plans*') ? 'nav-link is-active' : 'nav-link' }}">
                <i data-lucide="file-signature" class="nav-ico"></i>
                <span>{{ __('menu.contracts') }}</span>
            </a>
        @elseif($adminRole === 'sales')
            <a href="{{ route('admin.customer-contract-plans.index') }}" class="{{ request()->is('admin/customer-contract-plans*') ? 'nav-link is-active' : 'nav-link' }}">
                <i data-lucide="file-signature" class="nav-ico"></i>
                <span>{{ __('menu.contracts') }}</span>
            </a>
        @endif
        @if(in_array($adminRole, ['system_admin', 'operation', 'accounting', 'sales'], true))
            <a href="{{ route('admin.billing.index') }}" class="{{ request()->is('admin/billing*') ? 'nav-link is-active' : 'nav-link' }}">
                <i data-lucide="receipt-text" class="nav-ico"></i>
                <span>{{ __('menu.billing') }}</span>
            </a>
        @endif
    @elseif(in_array($adminRole, ['operation', 'accounting'], true))
        <p class="nav-section-label">{{ __('menu.menu_contract_billing') }}</p>
        <a href="{{ route('admin.billing.index') }}" class="{{ request()->is('admin/billing*') ? 'nav-link is-active' : 'nav-link' }}">
            <i data-lucide="receipt-text" class="nav-ico"></i>
            <span>{{ __('menu.billing') }}</span>
        </a>
    @endif

    @if($adminRole === 'system_admin')
        <p class="nav-section-label">{{ __('menu.menu_management') }}</p>
        <a href="{{ route('admin.admin-users.index') }}" class="{{ request()->is('admin/admin-users*') ? 'nav-link is-active' : 'nav-link' }}">
            <i data-lucide="user-cog" class="nav-ico"></i>
            <span>{{ __('menu.admin_users_management') }}</span>
        </a>
        <a href="{{ route('admin.account-locks.index') }}" class="{{ request()->is('admin/account-locks*') ? 'nav-link is-active' : 'nav-link' }}">
            <i data-lucide="lock" class="nav-ico"></i>
            <span>{{ __('menu.account_locks') }}</span>
        </a>
        <a href="{{ route('admin.database-backups.index') }}" class="{{ request()->is('admin/database-backups*') ? 'nav-link is-active' : 'nav-link' }}">
            <i data-lucide="database" class="nav-ico"></i>
            <span>{{ __('menu.database_management') }}</span>
        </a>
    @endif
@endif
