<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('auth.system_title'))</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" href="{{ asset('images/brand/favicon-32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/apple-touch-icon.png') }}">
    {{-- Google Fonts: Noto Sans JP --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/brand-tokens.css') }}?v={{ filemtime(public_path('css/brand-tokens.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/brand-shell.css') }}?v={{ filemtime(public_path('css/brand-shell.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/ui-kit.css') }}?v={{ filemtime(public_path('css/ui-kit.css')) }}">
    <script src="{{ asset('js/custom-select.js') }}?v={{ filemtime(public_path('js/custom-select.js')) }}" defer></script>
    <script src="{{ asset('js/master-code.js') }}?v={{ filemtime(public_path('js/master-code.js')) }}" defer></script>
    <script src="{{ asset('js/confirm-modal.js') }}?v={{ filemtime(public_path('js/confirm-modal.js')) }}" defer></script>
    {{-- Tailwind CSS CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>
    {{-- Lucide Icons --}}
    <script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.js"></script>
    {{-- Phosphor Icons - Fallback for some icons --}}
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <script>
        if (typeof lucide === 'undefined') {
            console.warn('Lucide icons library not loaded');
        }
    </script>

    {{-- Chart.js for Dashboard --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    {{-- ApexCharts for Advanced Visualizations --}}
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.min.js"></script>
    {{-- Tailwind Config — brand teal primary (public/images/brand) --}}
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Noto Sans JP"', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            DEFAULT: '#0F6B6B',
                            hover: '#0B5555',
                            soft: '#E6F3F3',
                            muted: '#3D8A8A',
                        },
                        primary: {
                            50: '#E6F3F3',
                            100: '#C5E4E4',
                            200: '#9AD0D0',
                            300: '#6BB8B8',
                            400: '#3D8A8A',
                            500: '#1A7A7A',
                            600: '#0F6B6B',
                            700: '#0B5555',
                            800: '#084444',
                            900: '#053333',
                        },
                        slatebrand: {
                            DEFAULT: '#5C6B7A',
                        },
                    },
                },
            },
        }
    </script>
    <style>
        body {
            font-family: var(--font-sans, 'Noto Sans JP', sans-serif);
        }
        .app-shell {
            backdrop-filter: blur(14px);
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(244, 246, 247, 0.96) 100%);
        }

        button [data-lucide],
        a [data-lucide],
        button i[data-lucide],
        a i[data-lucide],
        .btn [data-lucide],
        .button [data-lucide],
        form button [data-lucide],
        form a [data-lucide] {
            pointer-events: none;
        }

        .icon-wrapper {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        html { scroll-behavior: smooth; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        .mobile-menu-overlay {
            transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out;
        }
        .mobile-menu-overlay.active {
            opacity: 1;
            visibility: visible;
        }

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

        @media (max-width: 768px) {
            table { font-size: 12px; }
            table th,
            table td {
                padding: 0.5rem 0.75rem;
                white-space: nowrap;
            }
            .overflow-x-auto {
                -webkit-overflow-scrolling: touch;
                scrollbar-width: thin;
            }
            .overflow-x-auto table {
                min-width: 600px;
            }
        }

        @media (max-width: 767px) {
            #sidebar {
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }
        }

        .menu-section { margin-bottom: 0.25rem; }
        .menu-section-header { cursor: pointer; }
        .menu-section-content {
            max-height: 1000px;
            overflow: hidden;
            transition: max-height 0.3s ease-in-out;
        }
        .menu-section.collapsed .menu-section-content { max-height: 0; }
        .menu-section.expanded .menu-chevron { transform: rotate(0deg); }
        .menu-section.collapsed .menu-chevron { transform: rotate(-90deg); }
        .menu-section[data-initialized] .menu-section-content {
            transition: max-height 0.3s ease-in-out;
        }
    </style>
</head>
@php
    $shellUser = auth()->user();
    $isKamataniShell = (bool) ($shellUser?->adminUser);
    $shellCustomer = $shellUser?->customerUser;
    $hospitalName = $shellCustomer?->medicalInstitution?->name;
    $hospitalCode = $shellCustomer?->medicalInstitution?->code;
@endphp
<body class="app-brand min-h-screen text-[15px] antialiased {{ $isKamataniShell ? 'shell-kamatani' : 'shell-hospital' }}">
    <div class="min-h-screen flex">
        <!-- Mobile Menu Overlay -->
        <div id="mobile-menu-overlay" class="mobile-menu-overlay fixed inset-0 bg-black/50 z-40 opacity-0 invisible md:hidden" onclick="toggleMobileMenu()"></div>
        
        <!-- サイドバー -->
        <aside id="sidebar" class="mobile-sidebar fixed inset-y-0 left-0 z-50 flex flex-col w-64 border-r shadow-sm md:translate-x-0 h-screen {{ $isKamataniShell ? '' : 'app-shell bg-white border-slate-200' }}">
            {{-- Brand header --}}
            <div class="sidebar-brand px-4 py-4 flex items-center justify-between gap-2">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="brand-mark">
                        <img src="{{ asset('images/brand/logo-mark-64.png') }}" alt="{{ __('auth.system_title') }}" width="40" height="40">
                    </div>
                    <div class="min-w-0">
                        <div class="brand-title truncate">{{ __('auth.system_title') }}</div>
                        @if($isKamataniShell)
                            <div class="brand-kamatani"><span>KAMATANI</span></div>
                        @else
                            <div class="brand-hospital-name" title="{{ $hospitalName }}{{ $hospitalCode ? ' ('.$hospitalCode.')' : '' }}">
                                {{ $hospitalName ?: __('pages.hospital_fallback') }}
                            </div>
                            @if($hospitalCode)
                                <div class="text-[11px] text-slate-500 font-mono truncate mt-0.5" title="{{ __('pages.customer_code_label') }}">{{ $hospitalCode }}</div>
                            @endif
                        @endif
                    </div>
                </div>
                <button onclick="toggleMobileMenu()" class="mobile-close-btn md:hidden p-2 hover:bg-slate-100 rounded-lg transition-all" aria-label="Close menu" type="button">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto">
                @include('layouts.partials.sidebar-nav')
            </nav>

            {{-- User Profile Section --}}
            <div class="sidebar-footer mt-auto flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="user-avatar">
                        {{ mb_substr(auth()->user()->name ?? 'U', 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="user-name truncate">{{ auth()->user()->name ?? '' }}</p>
                        <p class="user-role truncate">
                            @php
                                $customer = auth()->user()->customerUser;
                            @endphp
                            @if(auth()->user()->adminUser)
                                {{ __('roles.' . auth()->user()->adminUser->role) }}
                            @elseif($customer)
                                {{ $customer->role === 'institution_admin' ? __('roles.institution_admin') : __('roles.department_admin') }}
                            @endif
                        </p>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="nav-link w-full text-left" type="submit">
                        <i data-lucide="log-out" class="nav-ico"></i>
                        <span>{{ __('menu.logout') }}</span>
                    </button>
                </form>
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
                        @unless($isKamataniShell)
                            <a href="{{ route('applications.create') }}"
                               class="hidden sm:inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[13px] font-semibold shadow-sm transition-all">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                                <span>{{ __('pages.new_application_cta') }}</span>
                            </a>
                        @endunless
                        <a href="{{ route('notifications.index') }}" class="relative p-2.5 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition-all" title="{{ __('menu.notifications') }}" data-notify-bell>
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
                    <div class="ui-flash ui-flash--success mb-4" role="status" data-ui-flash>
                        <div class="ui-flash__body">
                            <i data-lucide="check-circle-2" class="ui-flash__icon"></i>
                            <span>{{ session('status') }}</span>
                        </div>
                        <button type="button" class="ui-flash__close" aria-label="Close" onclick="this.closest('[data-ui-flash]')?.remove()">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="ui-flash ui-flash--error mb-4" role="alert" data-ui-flash>
                        <div class="ui-flash__body">
                            <i data-lucide="alert-circle" class="ui-flash__icon"></i>
                            <span>{{ session('error') }}</span>
                        </div>
                        <button type="button" class="ui-flash__close" aria-label="Close" onclick="this.closest('[data-ui-flash]')?.remove()">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                @endif

                @yield('content')
            </main>

            @unless($isKamataniShell)
                @php
                    $bottomHome = request()->routeIs('dashboard');
                    $bottomApps = request()->is('applications*');
                    $bottomCreate = request()->routeIs('applications.create');
                    $bottomStaff = request()->is('employees*');
                @endphp
                <nav class="hospital-bottom-nav md:hidden" aria-label="{{ __('pages.home') }}">
                    <a href="{{ route('dashboard') }}" class="{{ $bottomHome ? 'is-active' : '' }}">
                        <i data-lucide="home"></i>
                        <span>{{ __('pages.home') }}</span>
                    </a>
                    <a href="{{ route('applications.index') }}" class="{{ $bottomApps && ! $bottomCreate ? 'is-active' : '' }}">
                        <i data-lucide="clipboard-list"></i>
                        <span>{{ __('common.applications') }}</span>
                    </a>
                    <a href="{{ route('applications.create') }}" class="{{ $bottomCreate ? 'is-active' : '' }}">
                        <i data-lucide="plus-circle"></i>
                        <span>{{ __('pages.new_application_short') }}</span>
                    </a>
                    <a href="{{ route('employees.index') }}" class="{{ $bottomStaff ? 'is-active' : '' }}">
                        <i data-lucide="users"></i>
                        <span>{{ __('common.employees') }}</span>
                    </a>
                </nav>
            @endunless
        </div>
    </div>

    <x-confirm-modal />
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
        if (typeof window.refreshLucideIcons === 'function') {
            window.refreshLucideIcons();
        } else if (typeof lucide !== 'undefined') {
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

// Initialize Lucide Icons (debounced; observer paused during createIcons to avoid re-render loops)
document.addEventListener('DOMContentLoaded', () => {
    let iconObserver = null;
    let iconInitTimer = null;
    let iconsReady = typeof lucide !== 'undefined';

    function fixIconClicks(root = document) {
        root.querySelectorAll('button [data-lucide], a [data-lucide], button svg.lucide, a svg.lucide').forEach((icon) => {
            icon.style.pointerEvents = 'none';
        });
    }

    /** Only unconverted placeholders: <i data-lucide> / <span data-lucide>, not already-rendered SVGs */
    function hasPendingLucideIcons(root = document) {
        return !!root.querySelector('i[data-lucide], span[data-lucide], [data-lucide]:not(svg)');
    }

    function initLucideIcons() {
        if (typeof lucide === 'undefined') {
            if (!iconsReady) {
                setTimeout(initLucideIcons, 100);
            }
            return;
        }
        iconsReady = true;
        if (!hasPendingLucideIcons()) {
            fixIconClicks();
            return;
        }
        try {
            if (iconObserver) {
                iconObserver.disconnect();
            }
            lucide.createIcons();
            fixIconClicks();
        } catch (e) {
            console.error('Error initializing Lucide icons:', e);
        } finally {
            if (iconObserver) {
                iconObserver.observe(document.body, { childList: true, subtree: true });
            }
        }
    }

    window.refreshLucideIcons = function () {
        clearTimeout(iconInitTimer);
        iconInitTimer = setTimeout(initLucideIcons, 40);
    };

    initLucideIcons();
    setTimeout(initLucideIcons, 200);

    iconObserver = new MutationObserver(function (mutations) {
        let shouldReinit = false;
        for (const mutation of mutations) {
            for (const node of mutation.addedNodes) {
                if (node.nodeType !== 1) continue;
                // Ignore already-rendered Lucide SVGs (createIcons output) — that caused submit re-render loops
                if (node.matches?.('svg.lucide, svg[data-lucide]')) continue;
                if (node.matches?.('i[data-lucide], span[data-lucide]')) {
                    shouldReinit = true;
                    break;
                }
                if (node.querySelector?.('i[data-lucide], span[data-lucide], [data-lucide]:not(svg)')) {
                    shouldReinit = true;
                    break;
                }
            }
            if (shouldReinit) break;
        }
        if (shouldReinit) {
            window.refreshLucideIcons();
        }
    });

    iconObserver.observe(document.body, { childList: true, subtree: true });

    // Auto-dismiss success flashes after a few seconds (errors stay until closed)
    document.querySelectorAll('.ui-flash--success[data-ui-flash]').forEach(function (el) {
        setTimeout(function () {
            if (el.isConnected) el.remove();
        }, 8000);
    });

    function updateNotificationCount() {
        fetch('{{ route("api.notifications.unread_count") }}')
            .then(response => response.json())
            .then(data => {
                const badge = document.getElementById('notification-badge');
                if (data.count > 0) {
                    if (!badge) {
                        const bell = document.querySelector('a[data-notify-bell]');
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
            .catch(error => console.error(@json(__('pages.notification_count_error')), error));
    }

    updateNotificationCount();
    setInterval(updateNotificationCount, 30000);

    window.showToast = function(message, type = 'info', duration = 5000) {
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.className = 'fixed top-4 right-4 z-[9999] space-y-3';
            document.body.appendChild(toastContainer);
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
            <span class="flex-1 text-[14px] font-medium"></span>
            <button type="button" class="toast-close flex-shrink-0 hover:opacity-80 transition-opacity">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        `;
        toast.querySelector('span').textContent = message;
        toast.querySelector('.toast-close').addEventListener('click', () => toast.remove());
        toastContainer.appendChild(toast);
        window.refreshLucideIcons();

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
