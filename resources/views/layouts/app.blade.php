<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('auth.system_title'))</title>
    {{-- Google Fonts: Noto Sans JP --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/ui-kit.css') }}?v={{ filemtime(public_path('css/ui-kit.css')) }}">
    <script src="{{ asset('js/custom-select.js') }}?v={{ filemtime(public_path('js/custom-select.js')) }}" defer></script>
    <script src="{{ asset('js/master-code.js') }}?v={{ filemtime(public_path('js/master-code.js')) }}" defer></script>
    {{-- Tailwind CSS CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>
    {{-- Lucide Icons - Modern, Beautiful Icons --}}
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    {{-- Phosphor Icons - Fallback for some icons --}}
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
    {{-- Ensure icons load before page renders --}}
    <script>
        // Preload icon libraries
        if (typeof lucide === 'undefined') {
            console.warn('Lucide icons library not loaded');
        }
    </script>

    {{-- Chart.js for Dashboard --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    {{-- ApexCharts for Advanced Visualizations --}}
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.min.js"></script>
    {{-- Tailwind Config --}}
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Noto Sans JP"', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        },
                        gradient: {
                            blue: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                            purple: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                            green: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
                            orange: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
                        },
                    },
                },
            },
        }
    </script>
    <style>
        body {
            font-family: 'Noto Sans JP', sans-serif;
            background-image: url('https://images.unsplash.com/photo-1583912267550-1153b9a9e277?auto=format&fit=crop&w=1600&q=80');
            background-size: cover;
            background-attachment: fixed;
            background-position: center right;
        }
        /* Layered backdrop for app shell */
        .app-shell {
            backdrop-filter: blur(14px);
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.95) 100%);
            box-shadow: inset 0 0 100px rgba(0, 0, 0, 0.02);
        }
        
        /* Fix icon click issues - icons inside buttons/links should not capture clicks */
        button [data-lucide],
        a [data-lucide],
        button i[data-lucide],
        a i[data-lucide],
        .btn [data-lucide],
        .button [data-lucide] {
            pointer-events: none;
        }
        
        /* Ensure form buttons with icons work correctly */
        form button [data-lucide],
        form a [data-lucide] {
            pointer-events: none;
        }
        
        /* Beautiful gradient cards */
        .gradient-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .gradient-card-green {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            box-shadow: 0 10px 30px rgba(17, 153, 142, 0.3);
        }
        
        .gradient-card-orange {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            box-shadow: 0 10px 30px rgba(245, 87, 108, 0.3);
        }
        
        .gradient-card-blue {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            box-shadow: 0 10px 30px rgba(79, 172, 254, 0.3);
        }
        
        /* Icon wrapper for Lucide */
        .icon-wrapper {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        /* Smooth scroll */
        html { scroll-behavior: smooth; }
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        /* Mobile menu overlay */
        .mobile-menu-overlay {
            transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out;
        }
        .mobile-menu-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        /* Mobile sidebar */
        .mobile-sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s ease-in-out;
        }
        @media (min-width: 768px) {
            .mobile-sidebar {
                transform: translateX(0) !important;
            }
        }
        .mobile-sidebar.active {
            transform: translateX(0);
        }
        
        /* Responsive table styles */
        @media (max-width: 768px) {
            table {
                font-size: 12px;
            }
            table th,
            table td {
                padding: 0.5rem 0.75rem;
                white-space: nowrap;
            }
            /* Ensure tables scroll horizontally on mobile */
            .overflow-x-auto {
                -webkit-overflow-scrolling: touch;
                scrollbar-width: thin;
            }
            /* Make table containers more mobile-friendly */
            .overflow-x-auto table {
                min-width: 600px; /* Minimum width to ensure horizontal scroll */
            }
        }
        
        /* Ensure sidebar scrolls on mobile */
        @media (max-width: 767px) {
            #sidebar {
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }
        }
        
        /* Menu Section Styles */
        .menu-section {
            margin-bottom: 0.25rem;
        }
        
        .menu-section-header {
            cursor: pointer;
        }
        
        .menu-section-content {
            max-height: 1000px;
            overflow: hidden;
            transition: max-height 0.3s ease-in-out;
        }
        
        .menu-section.collapsed .menu-section-content {
            max-height: 0;
        }
        
        .menu-section.expanded .menu-chevron {
            transform: rotate(0deg);
        }
        
        .menu-section.collapsed .menu-chevron {
            transform: rotate(-90deg);
        }
        
        /* Prevent flicker - ensure smooth transitions */
        .menu-section[data-initialized] .menu-section-content {
            transition: max-height 0.3s ease-in-out;
        }
    </style>
</head>
<body class="min-h-screen bg-slate-50 text-[15px] antialiased">
    <div class="min-h-screen flex">
        <!-- Mobile Menu Overlay -->
        <div id="mobile-menu-overlay" class="mobile-menu-overlay fixed inset-0 bg-black/50 z-40 opacity-0 invisible md:hidden" onclick="toggleMobileMenu()"></div>
        
        <!-- サイドバー - Modern Redesign -->
        <aside id="sidebar" class="mobile-sidebar fixed inset-y-0 left-0 z-50 flex flex-col w-64 bg-white border-r border-slate-200 shadow-sm app-shell md:translate-x-0 h-screen">
            {{-- Logo Section with Gradient --}}
            <div class="px-4 py-5 border-b border-slate-200 bg-gradient-to-br from-slate-50 to-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-primary-500 via-primary-600 to-blue-600 flex items-center justify-center text-white text-base font-bold shadow-md ring-2 ring-primary-100">
                        <i data-lucide="briefcase" class="w-5 h-5"></i>
                    </div>
                    <div class="text-[12px] leading-tight">
                        <div class="font-bold text-slate-900 text-[13px]">Kamatani</div>
                        <div class="text-[10px] text-slate-500">Uniform System</div>
                    </div>
                </div>
                <!-- Mobile Close Button -->
                <button onclick="toggleMobileMenu()" class="md:hidden p-2 text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-all" aria-label="Close menu" type="button">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <nav class="flex-1 px-2 py-4 text-[13px] space-y-1 overflow-y-auto">
                @php
                    $user = auth()->user();
                    $customer = $user->customerUser;
                    $admin = $user->adminUser;
                @endphp

                <div class="space-y-1">
                    {{-- Overview Section (Single Item - Direct Link) --}}
                    <a href="{{ route('dashboard') }}"
                       class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-primary-50 to-blue-50 text-primary-700 shadow-sm border border-primary-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <div class="h-8 w-8 rounded-lg {{ request()->routeIs('dashboard') ? 'bg-gradient-to-br from-primary-500 to-primary-600 text-white' : 'bg-slate-100 group-hover:bg-slate-200 text-slate-600' }} flex items-center justify-center transition-all">
                            <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                        </div>
                        <span class="font-medium">{{ __('menu.dashboard') }}</span>
                    </a>

                    {{-- 医療機関側ユーザーのみ 申請 / 職員 メニューを表示 --}}
                    @if($customer)
                        {{-- Applications Section (Single Item - Direct Link) --}}
                        <a href="{{ route('applications.index') }}"
                           class="group flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->is('applications*') ? 'bg-gradient-to-r from-primary-50 to-blue-50 text-primary-700 shadow-sm border border-primary-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                            <div class="flex items-center gap-3">
                                <div class="h-8 w-8 rounded-lg {{ request()->is('applications*') ? 'bg-gradient-to-br from-primary-500 to-primary-600 text-white' : 'bg-slate-100 group-hover:bg-slate-200 text-slate-600' }} flex items-center justify-center transition-all">
                                    <i data-lucide="file-text" class="w-4 h-4"></i>
                                </div>
                                <span class="font-medium">{{ __('menu.applications') }}</span>
                            </div>
                            @php
                                $pendingCount = \App\Models\Application::where('status', 'pending')
                                    ->where('medical_institution_id', $customer->medical_institution_id)
                                    ->when($customer->role === 'department_admin' && $customer->department_id, function($q) use ($customer) {
                                        $q->where('department_id', $customer->department_id);
                                    })
                                    ->count();
                            @endphp
                            @if($pendingCount > 0)
                                <span class="inline-flex items-center justify-center min-w-[1.5rem] h-5 px-1.5 rounded-full bg-gradient-to-r from-red-500 to-red-600 text-white text-[10px] font-bold shadow-md">
                                    {{ $pendingCount > 9 ? '9+' : $pendingCount }}
                                </span>
                            @endif
                        </a>
                        
                        {{-- Employees Section (Single Item - Direct Link) --}}
                        <a href="{{ route('employees.index') }}"
                           class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->is('employees*') ? 'bg-gradient-to-r from-primary-50 to-blue-50 text-primary-700 shadow-sm border border-primary-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                            <div class="h-8 w-8 rounded-lg {{ request()->is('employees*') ? 'bg-gradient-to-br from-primary-500 to-primary-600 text-white' : 'bg-slate-100 group-hover:bg-slate-200 text-slate-600' }} flex items-center justify-center transition-all">
                                <i data-lucide="users" class="w-4 h-4"></i>
                            </div>
                            <span class="font-medium">{{ __('menu.employees') }}</span>
                        </a>
                        
                        @if($customer->role === 'institution_admin')
                            {{-- Master Data Section --}}
                            @php
                                $isMasterDataActive = request()->is('customer/masters/departments*') || 
                                                     request()->is('customer/masters/job-types*') || 
                                                     request()->is('customer/masters/delivery-locations*') || 
                                                     request()->is('customer/masters/department-admins*');
                            @endphp
                            <div class="menu-section {{ $isMasterDataActive ? 'expanded' : 'collapsed' }}" data-initialized="true">
                                <button type="button" class="menu-section-header w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-slate-700 hover:bg-slate-50 transition-all" onclick="toggleMenuSection(this)">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="database" class="w-4 h-4 text-slate-500"></i>
                                        <span class="text-[13px] font-semibold">{{ __('menu.menu_master_data') }}</span>
                                    </div>
                                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 menu-chevron transition-transform duration-200"></i>
                                </button>
                                <div class="menu-section-content pl-4 mt-1 space-y-1">
                                    <a href="{{ route('customer.masters.departments') }}"
                                       class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->is('customer/masters/departments*') ? 'bg-gradient-to-r from-primary-50 to-blue-50 text-primary-700 shadow-sm border border-primary-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                                        <div class="h-8 w-8 rounded-lg {{ request()->is('customer/masters/departments*') ? 'bg-gradient-to-br from-primary-500 to-primary-600 text-white' : 'bg-slate-100 group-hover:bg-slate-200 text-slate-600' }} flex items-center justify-center transition-all">
                                            <i data-lucide="briefcase" class="w-4 h-4"></i>
                                        </div>
                                        <span class="font-medium">{{ __('menu.departments') }}</span>
                                    </a>
                                    <a href="{{ route('customer.masters.job-types') }}"
                                       class="group mt-1 flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->is('customer/masters/job-types*') ? 'bg-gradient-to-r from-primary-50 to-blue-50 text-primary-700 shadow-sm border border-primary-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                                        <div class="h-8 w-8 rounded-lg {{ request()->is('customer/masters/job-types*') ? 'bg-gradient-to-br from-primary-500 to-primary-600 text-white' : 'bg-slate-100 group-hover:bg-slate-200 text-slate-600' }} flex items-center justify-center transition-all">
                                            <i data-lucide="user-check" class="w-4 h-4"></i>
                                        </div>
                                        <span class="font-medium">{{ __('menu.job_types') }}</span>
                                    </a>
                                    <a href="{{ route('customer.masters.delivery-locations') }}"
                                       class="group mt-1 flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->is('customer/masters/delivery-locations*') ? 'bg-gradient-to-r from-primary-50 to-blue-50 text-primary-700 shadow-sm border border-primary-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                                        <div class="h-8 w-8 rounded-lg {{ request()->is('customer/masters/delivery-locations*') ? 'bg-gradient-to-br from-primary-500 to-primary-600 text-white' : 'bg-slate-100 group-hover:bg-slate-200 text-slate-600' }} flex items-center justify-center transition-all">
                                            <i data-lucide="map-pin" class="w-4 h-4"></i>
                                        </div>
                                        <span class="font-medium">{{ __('menu.delivery_locations') }}</span>
                                    </a>
                                    <a href="{{ route('customer.masters.department-admins') }}"
                                       class="group mt-1 flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->is('customer/masters/department-admins*') ? 'bg-gradient-to-r from-primary-50 to-blue-50 text-primary-700 shadow-sm border border-primary-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                                        <div class="h-8 w-8 rounded-lg {{ request()->is('customer/masters/department-admins*') ? 'bg-gradient-to-br from-primary-500 to-primary-600 text-white' : 'bg-slate-100 group-hover:bg-slate-200 text-slate-600' }} flex items-center justify-center transition-all">
                                            <i data-lucide="user-cog" class="w-4 h-4"></i>
                                        </div>
                                        <span class="font-medium">{{ __('menu.department_admins') }}</span>
                                    </a>
                                </div>
                            </div>
                            
                            {{-- Contract plans / limits (hospital read-only) --}}
                            <a href="{{ route('customer.contract-plans.index') }}"
                               class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->is('customer/contract-plans*') ? 'bg-gradient-to-r from-primary-50 to-blue-50 text-primary-700 shadow-sm border border-primary-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                                <div class="h-8 w-8 rounded-lg {{ request()->is('customer/contract-plans*') ? 'bg-gradient-to-br from-primary-500 to-primary-600 text-white' : 'bg-slate-100 group-hover:bg-slate-200 text-slate-600' }} flex items-center justify-center transition-all">
                                    <i data-lucide="file-signature" class="w-4 h-4"></i>
                                </div>
                                <span class="font-medium">{{ __('menu.contract_info') }}</span>
                            </a>
                        @endif
                    @endif
                </div>

                @if($admin)
                    @php
                        $adminRole = $admin->role;
                    @endphp
                    
                    {{-- System Admin & Operations: Full Management Access --}}
                    @if(in_array($adminRole, ['system_admin', 'operation']))
                        {{-- Applications Section (Single Item - Direct Link) --}}
                        <a href="{{ route('admin.applications.index') }}"
                           class="group flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->is('admin/applications*') && !request()->is('admin/orders*') ? 'bg-gradient-to-r from-primary-50 to-blue-50 text-primary-700 shadow-sm border border-primary-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                            <div class="flex items-center gap-3">
                                <div class="h-8 w-8 rounded-lg {{ request()->is('admin/applications*') ? 'bg-gradient-to-br from-primary-500 to-primary-600 text-white' : 'bg-slate-100 group-hover:bg-slate-200 text-slate-600' }} flex items-center justify-center transition-all">
                                    <i data-lucide="clipboard-check" class="w-4 h-4"></i>
                                </div>
                                <span class="font-medium">{{ __('menu.applications') }}</span>
                            </div>
                            @php
                                $pendingCount = \App\Models\Application::whereIn('status', ['pending', 'waiting_special_approval'])->count();
                            @endphp
                            @if($pendingCount > 0)
                                <span class="inline-flex items-center justify-center min-w-[1.5rem] h-5 px-1.5 rounded-full bg-gradient-to-r from-red-500 to-red-600 text-white text-[10px] font-bold shadow-md">
                                    {{ $pendingCount > 9 ? '9+' : $pendingCount }}
                                </span>
                            @endif
                        </a>
                        <a href="{{ route('admin.orders.index') }}"
                           class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->is('admin/orders*') ? 'bg-gradient-to-r from-primary-50 to-blue-50 text-primary-700 shadow-sm border border-primary-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                            <div class="h-8 w-8 rounded-lg {{ request()->is('admin/orders*') ? 'bg-gradient-to-br from-primary-500 to-primary-600 text-white' : 'bg-slate-100 group-hover:bg-slate-200 text-slate-600' }} flex items-center justify-center transition-all">
                                <i data-lucide="package-check" class="w-4 h-4"></i>
                            </div>
                            <span class="font-medium">受注一覧</span>
                        </a>
                        
                        {{-- Products Section (Single Item - Direct Link) --}}
                        <a href="{{ route('admin.uniform-products.index') }}"
                           class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->is('admin/uniform-products*') ? 'bg-gradient-to-r from-primary-50 to-blue-50 text-primary-700 shadow-sm border border-primary-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                            <div class="h-8 w-8 rounded-lg {{ request()->is('admin/uniform-products*') ? 'bg-gradient-to-br from-primary-500 to-primary-600 text-white' : 'bg-slate-100 group-hover:bg-slate-200 text-slate-600' }} flex items-center justify-center transition-all">
                                <i data-lucide="shirt" class="w-4 h-4"></i>
                            </div>
                            <span class="font-medium">{{ __('menu.uniform_products') }}</span>
                        </a>
                        <a href="{{ route('admin.uniform-recommendations.index') }}"
                           class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->is('admin/uniform-recommendations*') ? 'bg-gradient-to-r from-primary-50 to-blue-50 text-primary-700 shadow-sm border border-primary-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                            <div class="h-8 w-8 rounded-lg {{ request()->is('admin/uniform-recommendations*') ? 'bg-gradient-to-br from-primary-500 to-primary-600 text-white' : 'bg-slate-100 group-hover:bg-slate-200 text-slate-600' }} flex items-center justify-center transition-all">
                                <i data-lucide="sparkles" class="w-4 h-4"></i>
                            </div>
                            <span class="font-medium">推奨／指定ユニフォーム</span>
                        </a>
                    @endif
                    
                    {{-- System Admin Only: Master Data Management --}}
                    @if($adminRole === 'system_admin')
                        @php
                            $isAdminMasterDataActive = request()->is('admin/masters/institutions*') || 
                                                       request()->is('admin/masters/departments*') || 
                                                       request()->is('admin/masters/job-types*') || 
                                                       request()->is('admin/masters/delivery-locations*');
                        @endphp
                        {{-- Master Data Section --}}
                        <div class="menu-section {{ $isAdminMasterDataActive ? 'expanded' : 'collapsed' }}" data-initialized="true">
                            <button type="button" class="menu-section-header w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-slate-700 hover:bg-slate-50 transition-all" onclick="toggleMenuSection(this)">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="database" class="w-4 h-4 text-slate-500"></i>
                                    <span class="text-[13px] font-semibold">{{ __('menu.menu_master_data') }}</span>
                                </div>
                                <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 menu-chevron transition-transform duration-200"></i>
                            </button>
                            <div class="menu-section-content pl-4 mt-1 space-y-1">
                                <a href="{{ route('admin.masters.institutions') }}"
                                   class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->is('admin/masters/institutions*') ? 'bg-gradient-to-r from-primary-50 to-blue-50 text-primary-700 shadow-sm border border-primary-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                                    <div class="h-8 w-8 rounded-lg {{ request()->is('admin/masters/institutions*') ? 'bg-gradient-to-br from-primary-500 to-primary-600 text-white' : 'bg-slate-100 group-hover:bg-slate-200 text-slate-600' }} flex items-center justify-center transition-all">
                                        <i data-lucide="building" class="w-4 h-4"></i>
                                    </div>
                                    <span class="font-medium">{{ __('menu.institutions') }}</span>
                                </a>
                                <a href="{{ route('admin.masters.departments') }}"
                                   class="group mt-1 flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->is('admin/masters/departments*') ? 'bg-gradient-to-r from-primary-50 to-blue-50 text-primary-700 shadow-sm border border-primary-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                                    <div class="h-8 w-8 rounded-lg {{ request()->is('admin/masters/departments*') ? 'bg-gradient-to-br from-primary-500 to-primary-600 text-white' : 'bg-slate-100 group-hover:bg-slate-200 text-slate-600' }} flex items-center justify-center transition-all">
                                        <i data-lucide="briefcase" class="w-4 h-4"></i>
                                    </div>
                                    <span class="font-medium">{{ __('menu.departments') }}</span>
                                </a>
                                <a href="{{ route('admin.masters.job-types') }}"
                                   class="group mt-1 flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->is('admin/masters/job-types*') ? 'bg-gradient-to-r from-primary-50 to-blue-50 text-primary-700 shadow-sm border border-primary-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                                    <div class="h-8 w-8 rounded-lg {{ request()->is('admin/masters/job-types*') ? 'bg-gradient-to-br from-primary-500 to-primary-600 text-white' : 'bg-slate-100 group-hover:bg-slate-200 text-slate-600' }} flex items-center justify-center transition-all">
                                        <i data-lucide="user-check" class="w-4 h-4"></i>
                                    </div>
                                    <span class="font-medium">{{ __('menu.job_types') }}</span>
                                </a>
                                <a href="{{ route('admin.masters.delivery-locations') }}"
                                   class="group mt-1 flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->is('admin/masters/delivery-locations*') ? 'bg-gradient-to-r from-primary-50 to-blue-50 text-primary-700 shadow-sm border border-primary-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                                    <div class="h-8 w-8 rounded-lg {{ request()->is('admin/masters/delivery-locations*') ? 'bg-gradient-to-br from-primary-500 to-primary-600 text-white' : 'bg-slate-100 group-hover:bg-slate-200 text-slate-600' }} flex items-center justify-center transition-all">
                                        <i data-lucide="map-pin" class="w-4 h-4"></i>
                                    </div>
                                    <span class="font-medium">{{ __('menu.delivery_locations') }}</span>
                                </a>
                            </div>
                        </div>
                        
                        {{-- Admin Users Section (Single Item - Direct Link) --}}
                        <a href="{{ route('admin.admin-users.index') }}"
                           class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->is('admin/admin-users*') ? 'bg-gradient-to-r from-primary-50 to-blue-50 text-primary-700 shadow-sm border border-primary-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                            <div class="h-8 w-8 rounded-lg {{ request()->is('admin/admin-users*') ? 'bg-gradient-to-br from-primary-500 to-primary-600 text-white' : 'bg-slate-100 group-hover:bg-slate-200 text-slate-600' }} flex items-center justify-center transition-all">
                                <i data-lucide="user-cog" class="w-4 h-4"></i>
                            </div>
                            <span class="font-medium">{{ __('menu.admin_users_management') }}</span>
                        </a>

                        <a href="{{ route('admin.account-locks.index') }}"
                           class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->is('admin/account-locks*') ? 'bg-gradient-to-r from-primary-50 to-blue-50 text-primary-700 shadow-sm border border-primary-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                            <div class="h-8 w-8 rounded-lg {{ request()->is('admin/account-locks*') ? 'bg-gradient-to-br from-primary-500 to-primary-600 text-white' : 'bg-slate-100 group-hover:bg-slate-200 text-slate-600' }} flex items-center justify-center transition-all">
                                <i data-lucide="lock-keyhole" class="w-4 h-4"></i>
                            </div>
                            <span class="font-medium">{{ __('menu.account_locks') }}</span>
                        </a>

                        {{-- Database Management --}}
                        <a href="{{ route('admin.database-backups.index') }}"
                           class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->is('admin/database-backups*') ? 'bg-gradient-to-r from-primary-50 to-blue-50 text-primary-700 shadow-sm border border-primary-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                            <div class="h-8 w-8 rounded-lg {{ request()->is('admin/database-backups*') ? 'bg-gradient-to-br from-primary-500 to-primary-600 text-white' : 'bg-slate-100 group-hover:bg-slate-200 text-slate-600' }} flex items-center justify-center transition-all">
                                <i data-lucide="database-backup" class="w-4 h-4"></i>
                            </div>
                            <span class="font-medium">{{ __('menu.database_management') }}</span>
                        </a>
                        
                        {{-- Employees Section (Single Item - Direct Link) --}}
                        <a href="{{ route('admin.employees.index') }}"
                           class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->is('admin/employees*') ? 'bg-gradient-to-r from-primary-50 to-blue-50 text-primary-700 shadow-sm border border-primary-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                            <div class="h-8 w-8 rounded-lg {{ request()->is('admin/employees*') ? 'bg-gradient-to-br from-primary-500 to-primary-600 text-white' : 'bg-slate-100 group-hover:bg-slate-200 text-slate-600' }} flex items-center justify-center transition-all">
                                <i data-lucide="users" class="w-4 h-4"></i>
                            </div>
                            <span class="font-medium">{{ __('menu.all_employees') }}</span>
                        </a>
                    @endif
                    
                    {{-- Sales: Contract Info Only (Single Item - Direct Link) --}}
                    @if($adminRole === 'sales')
                        <a href="{{ route('admin.customer-contract-plans.index') }}"
                           class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->is('admin/customer-contract-plans*') ? 'bg-gradient-to-r from-primary-50 to-blue-50 text-primary-700 shadow-sm border border-primary-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                            <div class="h-8 w-8 rounded-lg {{ request()->is('admin/customer-contract-plans*') ? 'bg-gradient-to-br from-primary-500 to-primary-600 text-white' : 'bg-slate-100 group-hover:bg-slate-200 text-slate-600' }} flex items-center justify-center transition-all">
                                <i data-lucide="file-signature" class="w-4 h-4"></i>
                            </div>
                            <span class="font-medium">{{ __('menu.customer_contract_plans') }}</span>
                        </a>
                    @endif

                    {{-- Contract Plan masters (write roles only in nav; sales has CCP link above) --}}
                    @if(in_array($adminRole, ['system_admin', 'contract_plan_manager']))
                        <a href="{{ route('admin.makers.index') }}"
                           class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->is('admin/makers*') ? 'bg-gradient-to-r from-primary-50 to-blue-50 text-primary-700 shadow-sm border border-primary-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                            <div class="h-8 w-8 rounded-lg {{ request()->is('admin/makers*') ? 'bg-gradient-to-br from-primary-500 to-primary-600 text-white' : 'bg-slate-100 group-hover:bg-slate-200 text-slate-600' }} flex items-center justify-center transition-all">
                                <i data-lucide="factory" class="w-4 h-4"></i>
                            </div>
                            <span class="font-medium">{{ __('menu.makers') }}</span>
                        </a>
                        <a href="{{ route('admin.uniform-categories.index') }}"
                           class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->is('admin/uniform-categories*') ? 'bg-gradient-to-r from-primary-50 to-blue-50 text-primary-700 shadow-sm border border-primary-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                            <div class="h-8 w-8 rounded-lg {{ request()->is('admin/uniform-categories*') ? 'bg-gradient-to-br from-primary-500 to-primary-600 text-white' : 'bg-slate-100 group-hover:bg-slate-200 text-slate-600' }} flex items-center justify-center transition-all">
                                <i data-lucide="tags" class="w-4 h-4"></i>
                            </div>
                            <span class="font-medium">{{ __('menu.uniform_categories') }}</span>
                        </a>
                        <a href="{{ route('admin.product-unit-prices.index') }}"
                           class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->is('admin/product-unit-prices*') ? 'bg-gradient-to-r from-primary-50 to-blue-50 text-primary-700 shadow-sm border border-primary-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                            <div class="h-8 w-8 rounded-lg {{ request()->is('admin/product-unit-prices*') ? 'bg-gradient-to-br from-primary-500 to-primary-600 text-white' : 'bg-slate-100 group-hover:bg-slate-200 text-slate-600' }} flex items-center justify-center transition-all">
                                <i data-lucide="tag" class="w-4 h-4"></i>
                            </div>
                            <span class="font-medium">{{ __('menu.product_unit_prices') }}</span>
                        </a>
                        <a href="{{ route('admin.contract-plans.index') }}"
                           class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->is('admin/contract-plans*') ? 'bg-gradient-to-r from-primary-50 to-blue-50 text-primary-700 shadow-sm border border-primary-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                            <div class="h-8 w-8 rounded-lg {{ request()->is('admin/contract-plans*') ? 'bg-gradient-to-br from-primary-500 to-primary-600 text-white' : 'bg-slate-100 group-hover:bg-slate-200 text-slate-600' }} flex items-center justify-center transition-all">
                                <i data-lucide="clipboard-list" class="w-4 h-4"></i>
                            </div>
                            <span class="font-medium">{{ __('menu.contract_plans') }}</span>
                        </a>
                        <a href="{{ route('admin.customer-contract-plans.index') }}"
                           class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->is('admin/customer-contract-plans*') ? 'bg-gradient-to-r from-primary-50 to-blue-50 text-primary-700 shadow-sm border border-primary-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                            <div class="h-8 w-8 rounded-lg {{ request()->is('admin/customer-contract-plans*') ? 'bg-gradient-to-br from-primary-500 to-primary-600 text-white' : 'bg-slate-100 group-hover:bg-slate-200 text-slate-600' }} flex items-center justify-center transition-all">
                                <i data-lucide="building-2" class="w-4 h-4"></i>
                            </div>
                            <span class="font-medium">{{ __('menu.customer_contract_plans') }}</span>
                        </a>
                    @endif
                    
                    {{-- Billing (plan-based) — System Admin, Operations (view), Accounting --}}
                    @if(in_array($adminRole, ['system_admin', 'operation', 'accounting', 'sales']))
                        <a href="{{ route('admin.billing.index') }}"
                           class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->is('admin/billing*') ? 'bg-gradient-to-r from-primary-50 to-blue-50 text-primary-700 shadow-sm border border-primary-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                            <div class="h-8 w-8 rounded-lg {{ request()->is('admin/billing*') ? 'bg-gradient-to-br from-primary-500 to-primary-600 text-white' : 'bg-slate-100 group-hover:bg-slate-200 text-slate-600' }} flex items-center justify-center transition-all">
                                <i data-lucide="receipt-text" class="w-4 h-4"></i>
                            </div>
                            <span class="font-medium">{{ __('menu.billing') }}</span>
                        </a>
                    @endif
                @endif
            </nav>

            {{-- User Profile Section --}}
            <div class="px-4 py-4 border-t border-slate-200 bg-gradient-to-br from-slate-50 to-white mt-auto flex-shrink-0">
                <div class="flex items-center gap-3 mb-3">
                    <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-primary-500 via-primary-600 to-blue-600 flex items-center justify-center text-white text-sm font-bold shadow-md ring-2 ring-primary-100">
                        {{ mb_substr(auth()->user()->name ?? 'U', 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[13px] font-semibold text-slate-900 truncate">{{ auth()->user()->name ?? '' }}</p>
                        <p class="text-[11px] text-slate-500 truncate">
                            @php
                                $customer = auth()->user()->customerUser;
                            @endphp
                            @if(auth()->user()->adminUser)
                                @php
                                    $adminRole = auth()->user()->adminUser->role;
                                @endphp
                                {{ __('roles.' . $adminRole) }}
                            @elseif($customer)
                                {{ $customer->role === 'institution_admin' ? __('roles.institution_admin') : __('roles.department_admin') }}
                            @endif
                        </p>
                    </div>
                </div>

               

                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button class="w-full flex items-center gap-2 px-3 py-2 rounded-xl text-[12px] text-slate-600 hover:bg-red-50 hover:text-red-600 hover:border-red-200 border border-slate-200 transition-all duration-200" type="submit">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                        <span class="font-medium">{{ __('menu.logout') }}</span>
                    </button>
                </form>
                <p class="text-[9px] text-slate-400 mt-3 text-center">© {{ date('Y') }} KAMATANI</p>
            </div>
        </aside>

        <!-- メインコンテンツ -->
        <div class="w-full flex flex-col min-w-0 md:ml-64">
            <header class="bg-white/95 z-[100] backdrop-blur-sm border-b border-slate-200 shadow-sm sticky top-0">
                <div class="px-3 py-3 sm:px-4 md:px-6 md:py-3 lg:py-4 flex items-center justify-between gap-2 sm:gap-3">
                    <div class="flex items-center gap-2 sm:gap-3 min-w-0 flex-1">
                        <!-- Mobile Menu Button -->
                        <button id="mobile-menu-btn" onclick="toggleMobileMenu()" class="md:hidden shrink-0 p-2.5 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-all" aria-label="Toggle menu" type="button">
                            <i data-lucide="menu" class="w-6 h-6"></i>
                        </button>
                        <div class="min-w-0">
                            <p class="text-[10px] sm:text-[11px] text-slate-400 uppercase tracking-wide font-semibold truncate">@yield('header_category', __('menu.applications'))</p>
                            <p class="text-base sm:text-xl font-bold text-slate-900 mt-0.5 truncate flex items-center gap-2">
                                @yield('page_title', __('menu.applications'))
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 sm:gap-3 shrink-0">
                        <a href="{{ route('notifications.index') }}" class="relative p-2.5 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition-all" title="{{ __('menu.notifications') }}">
                            <i data-lucide="bell" class="w-5 h-5"></i>
                            @php
                                $notificationCount = \App\Models\Notification::where('user_id', auth()->id())
                                    ->where('is_read', false)
                                    ->count();
                            @endphp
                            @if($notificationCount > 0)
                                <span id="notification-badge" class="absolute top-0 right-0 h-4 w-4 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center">
                                    {{ $notificationCount > 9 ? '9+' : $notificationCount }}
                                </span>
                            @endif
                        </a>
                        <div class="relative group">
                            <button class="p-2.5 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition-all" title="{{ __('common.settings') }}">
                                <i data-lucide="globe" class="w-5 h-5"></i>
                            </button>
                            <div class="absolute right-0 top-full mt-2 w-32 bg-white border border-slate-200 rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50">
                                <a href="{{ route('language.switch', 'ja') }}" class="block px-4 py-2.5 text-[14px] text-slate-700 hover:bg-slate-50 {{ app()->getLocale() === 'ja' ? 'bg-primary-50 text-primary-700 font-semibold' : '' }}">
                                    <i class="ph ph-flag text-base mr-2"></i>
                                    {{ __('common.japanese') }}
                                </a>
                                <a href="{{ route('language.switch', 'en') }}" class="block px-4 py-2.5 text-[14px] text-slate-700 hover:bg-slate-50 {{ app()->getLocale() === 'en' ? 'bg-primary-50 text-primary-700 font-semibold' : '' }}">
                                    <i class="ph ph-flag text-base mr-2"></i>
                                    {{ __('common.english') }}
                                </a>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="h-9 w-9 rounded-full bg-gradient-to-br from-primary-500 via-primary-600 to-blue-600 text-white text-xs font-bold flex items-center justify-center shadow-lg ring-2 ring-primary-200">
                                {{ mb_substr(auth()->user()->name ?? 'U', 0, 1) }}
                            </div>
                            <div class="hidden md:block text-left">
                                <p class="text-[13px] font-semibold text-slate-900">{{ auth()->user()->name ?? '' }}</p>
                                <p class="text-[11px] text-slate-500">
                                    @if(auth()->user()->adminUser)
                                        @php
                                            $adminRole = auth()->user()->adminUser->role;
                                        @endphp
                                        {{ __('roles.' . $adminRole) }}
                                    @elseif(auth()->user()->customerUser)
                                        {{ __('roles.' . auth()->user()->customerUser->role) }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 min-w-0 px-3 py-4 sm:px-4 md:px-6 md:py-5 max-w-[1600px] w-full mx-auto overflow-x-hidden">
                @if (session('status'))
                    <div class="mb-6 text-sm text-emerald-800 bg-emerald-50 border-l-4 border-emerald-500 rounded px-4 py-3 shadow-sm">
                        <div class="flex items-center gap-2">
                            <i class="ph ph-check-circle text-lg"></i>
                            <span>{{ session('status') }}</span>
                        </div>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-6 text-sm text-red-800 bg-red-50 border-l-4 border-red-500 rounded px-4 py-3 shadow-sm">
                        <div class="flex items-center gap-2">
                            <i class="ph ph-warning text-lg"></i>
                            <span>{{ session('error') }}</span>
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
@stack('scripts')
<script>
// Mobile Menu Toggle Function
function toggleMobileMenu() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobile-menu-overlay');
    const menuBtn = document.getElementById('mobile-menu-btn');
    
    if (sidebar && overlay) {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
        
        // Prevent body scroll when menu is open
        if (sidebar.classList.contains('active')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
        
        // Reinitialize icons after toggle
        if (typeof lucide !== 'undefined') {
            setTimeout(() => lucide.createIcons(), 100);
        }
    }
}

// Menu Section Toggle Function
function toggleMenuSection(button) {
    const menuSection = button.closest('.menu-section');
    if (menuSection) {
        if (menuSection.classList.contains('expanded')) {
            menuSection.classList.remove('expanded');
            menuSection.classList.add('collapsed');
        } else {
            menuSection.classList.remove('collapsed');
            menuSection.classList.add('expanded');
        }
        // Reinitialize icons after toggle
        if (typeof lucide !== 'undefined') {
            setTimeout(() => lucide.createIcons(), 50);
        }
    }
}

// Close mobile menu when clicking outside or on a link
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobile-menu-overlay');
    
    // Close menu when clicking on sidebar links (mobile only)
    if (sidebar) {
        const sidebarLinks = sidebar.querySelectorAll('a');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 768) {
                    toggleMobileMenu();
                }
            });
        });
    }
    
    // Close menu on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar && sidebar.classList.contains('active')) {
            toggleMobileMenu();
        }
    });
    
    // Auto-expand menu sections that contain active routes, collapse others
    // Only update sections that don't have data-initialized attribute (for backward compatibility)
    const menuSections = document.querySelectorAll('.menu-section:not([data-initialized])');
    menuSections.forEach(section => {
        const activeLink = section.querySelector('a.bg-gradient-to-r, a.bg-primary-50');
        if (activeLink) {
            section.classList.add('expanded');
            section.classList.remove('collapsed');
        } else {
            section.classList.add('collapsed');
            section.classList.remove('expanded');
        }
    });
    
    // Mark all sections as initialized to prevent flicker
    document.querySelectorAll('.menu-section').forEach(section => {
        if (!section.hasAttribute('data-initialized')) {
            section.setAttribute('data-initialized', 'true');
        }
    });
});

// Initialize Lucide Icons - Enhanced with retry and MutationObserver
document.addEventListener('DOMContentLoaded', () => {
    // Function to fix icon click issues - ensure icons inside buttons/links don't capture clicks
    function fixIconClicks() {
        // Find all icons inside buttons, links, and forms
        const clickableSelectors = ['button', 'a', 'form button', 'form a', '.btn', '.button'];
        clickableSelectors.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(element => {
                const icons = element.querySelectorAll('[data-lucide], i[data-lucide]');
                icons.forEach(icon => {
                    icon.style.pointerEvents = 'none';
                });
            });
        });
    }
    
    // Function to initialize Lucide icons
    function initLucideIcons() {
        if (typeof lucide !== 'undefined') {
            try {
                lucide.createIcons();
                // Also try with specific container
                const containers = [document.body, document.querySelector('main'), document.querySelector('.content')];
                containers.forEach(container => {
                    if (container) {
                        try {
                            lucide.createIcons({ container: container });
                        } catch (e) {
                            // Ignore errors for specific containers
                        }
                    }
                });
                // Fix icon clicks after icons are created
                fixIconClicks();
            } catch (e) {
                console.error('Error initializing Lucide icons:', e);
            }
        } else {
            console.warn('Lucide library not loaded yet, retrying...');
            // Retry after a short delay
            setTimeout(initLucideIcons, 100);
        }
    }
    
    // Initial icon creation
    initLucideIcons();
    
    // Retry initialization after delays to catch late-loading content
    setTimeout(initLucideIcons, 100);
    setTimeout(initLucideIcons, 300);
    setTimeout(initLucideIcons, 500);
    setTimeout(initLucideIcons, 1000);
    
    // Watch for dynamically added content and re-initialize icons
    const observer = new MutationObserver(function(mutations) {
        let shouldReinit = false;
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length > 0) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        if (node.hasAttribute && node.hasAttribute('data-lucide')) {
                            shouldReinit = true;
                        } else if (node.querySelector && node.querySelector('[data-lucide]')) {
                            shouldReinit = true;
                        }
                        // Also check if new buttons/links with icons were added
                        if (node.matches && (node.matches('button, a') || node.querySelector('button, a'))) {
                            shouldReinit = true;
                        }
                    }
                });
            }
        });
        if (shouldReinit) {
            setTimeout(() => {
                initLucideIcons();
                fixIconClicks();
            }, 50);
        }
    });
    
    // Observe the entire document for changes
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
// 通知数を定期的に更新
    function updateNotificationCount() {
        fetch('{{ route("api.notifications.unread_count") }}')
            .then(response => response.json())
            .then(data => {
                const badge = document.getElementById('notification-badge');
                if (data.count > 0) {
                    if (!badge) {
                        const bell = document.querySelector('a[title="通知"]');
                        if (bell) {
                            const span = document.createElement('span');
                            span.id = 'notification-badge';
                            span.className = 'absolute top-0 right-0 h-4 w-4 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center';
                            span.textContent = data.count > 9 ? '9+' : data.count;
                            bell.appendChild(span);
                        }
                    } else {
                        badge.textContent = data.count > 9 ? '9+' : data.count;
                    }
                } else {
                    if (badge) badge.remove();
                }
            })
            .catch(error => console.error('通知数取得エラー:', error));
    }
    
    // 初回読み込み時と30秒ごとに更新
    updateNotificationCount();
    setInterval(updateNotificationCount, 30000);
    
    // Toast Notification System
    window.showToast = function(message, type = 'info', duration = 5000) {
        const toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            const container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'fixed top-4 right-4 z-[9999] space-y-3';
            document.body.appendChild(container);
        }
        
        const toast = document.createElement('div');
        const bgColors = {
            success: 'bg-emerald-500',
            error: 'bg-red-500',
            warning: 'bg-amber-500',
            info: 'bg-blue-500'
        };
        const icons = {
            success: 'check-circle',
            error: 'x-circle',
            warning: 'alert-triangle',
            info: 'info'
        };
        
        toast.className = `flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg ${bgColors[type] || bgColors.info} text-white min-w-[300px] max-w-[400px] animate-slide-in-right`;
        toast.innerHTML = `
            <i data-lucide="${icons[type] || icons.info}" class="w-5 h-5 flex-shrink-0"></i>
            <span class="flex-1 text-[14px] font-medium">${message}</span>
            <button onclick="this.parentElement.remove()" class="flex-shrink-0 hover:opacity-80 transition-opacity">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        `;
        
        document.getElementById('toast-container').appendChild(toast);
        lucide.createIcons();
        
        setTimeout(() => {
            toast.style.animation = 'slide-out-right 0.3s ease-out';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    };
});
</script>
<style>
@keyframes slide-in-right {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
@keyframes slide-out-right {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}
.animate-slide-in-right {
    animation: slide-in-right 0.3s ease-out;
}
</style>
</html>
