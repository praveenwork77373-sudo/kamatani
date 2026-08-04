<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('auth.system_title'))</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" href="{{ asset('images/brand/favicon-32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/apple-touch-icon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/brand-tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('css/ui-kit.css') }}?v={{ filemtime(public_path('css/ui-kit.css')) }}">
    <script src="{{ asset('js/custom-select.js') }}?v={{ filemtime(public_path('js/custom-select.js')) }}" defer></script>
    <script src="https://cdn.tailwindcss.com"></script>
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
            background-color: var(--brand, #0F6B6B);
        }
        .guest-card {
            background: var(--surface, #fff);
            border-radius: var(--radius-lg, 14px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.18);
        }
        .guest-input {
            display: block;
            width: 100%;
            min-height: 2.75rem;
            padding: 0.75rem 1rem;
            font-size: 14px;
            color: #0f172a;
            background: #fff;
            border: 1px solid var(--border, #D8DEE3);
            border-radius: var(--radius-md, 10px);
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .guest-input:hover {
            border-color: #94a3b8;
        }
        .guest-input:focus {
            outline: none;
            border-color: var(--brand, #0F6B6B);
            box-shadow: 0 0 0 3px rgba(15, 107, 107, 0.18);
        }
        .guest-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 2.75rem;
            background: var(--brand, #0F6B6B);
            border-radius: var(--radius-md, 10px);
            color: #fff;
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
        }
        .guest-btn:hover {
            background: var(--brand-hover, #0B5555);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-md">
        <div class="mb-4 flex justify-end gap-2">
            <a href="{{ route('language.switch', 'ja') }}"
               class="text-[12px] px-2.5 py-1 rounded-md {{ app()->getLocale() === 'ja' ? 'bg-white text-brand font-semibold' : 'text-white/85 hover:bg-white/10' }}">
                {{ __('common.japanese') }}
            </a>
            <a href="{{ route('language.switch', 'en') }}"
               class="text-[12px] px-2.5 py-1 rounded-md {{ app()->getLocale() === 'en' ? 'bg-white text-brand font-semibold' : 'text-white/85 hover:bg-white/10' }}">
                {{ __('common.english') }}
            </a>
        </div>
        @yield('content')
        <p class="mt-8 text-center text-[11px] text-white/70">
            © {{ date('Y') }} KAMATANI All Rights Reserved.
        </p>
    </div>
</body>
</html>
