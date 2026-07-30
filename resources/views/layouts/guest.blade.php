<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'ユニフォーム管理システム')</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" href="{{ asset('images/brand/favicon-32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/apple-touch-icon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/brand-tokens.css') }}">
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
            border: 1px solid var(--border, #D8DEE3);
            border-radius: var(--radius-md, 10px);
        }
        .guest-input:focus {
            outline: none;
            border-color: var(--brand, #0F6B6B);
            box-shadow: 0 0 0 3px rgba(15, 107, 107, 0.18);
        }
        .guest-btn {
            background: var(--brand, #0F6B6B);
            border-radius: var(--radius-md, 10px);
        }
        .guest-btn:hover {
            background: var(--brand-hover, #0B5555);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-md">
        @yield('content')
        <p class="mt-8 text-center text-[11px] text-white/70">
            © {{ date('Y') }} KAMATANI All Rights Reserved.
        </p>
    </div>
</body>
</html>
