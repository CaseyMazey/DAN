<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Dark mode anti-flash -->
        <script>(function(){if(localStorage.getItem('dan_dark')==='1')document.documentElement.setAttribute('data-theme','dark');})();</script>
        <style>
            :root { --p-bg: #F4F7FB; --p-surface: #fff; --p-border: #E2E8F0; }
            html[data-theme="dark"] { --p-bg: #0D1117; --p-surface: #161B22; --p-border: #30363D; }
            html, body, .dan-wrap { background: var(--p-bg) !important; }
            html[data-theme="dark"] header.site-header { background: var(--p-surface) !important; border-bottom: 1px solid var(--p-border) !important; box-shadow: none !important; }
        </style>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="dan-wrap min-h-screen">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="site-header bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
