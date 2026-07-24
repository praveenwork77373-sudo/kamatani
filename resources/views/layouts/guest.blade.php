<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'ユニフォーム管理システム')</title>
    {{-- Google Fonts: Noto Sans JP --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    {{-- Tailwind CSS CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>
    {{-- Phosphor Icons --}}
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
    {{-- Lucide Icons - Modern, Beautiful Icons --}}
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
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
                    },
                },
            },
        }
    </script>
    <style>
        body {
            font-family: 'Noto Sans JP', sans-serif;
            background-image: url('https://images.unsplash.com/photo-1535916707207-35f97e715e1b?auto=format&fit=crop&w=1600&q=80');
            background-size: cover;
            background-position: center;
        }
        /* Smooth scroll */
        html { scroll-behavior: smooth; }
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md px-4">
        <div class="mb-6 text-center">
            <p class="text-[11px] tracking-widest text-sky-700 font-semibold mb-1">KAMATANI UNIFORM PORTAL</p>
            <p class="text-xs text-slate-500">医療機関向けユニフォーム管理システム</p>
        </div>
        @yield('content')
        <p class="mt-6 text-[10px] text-center text-slate-400">
            © {{ date('Y') }} KAMATANI Co., Ltd. All Rights Reserved.
        </p>
    </div>
</body>
</html>


