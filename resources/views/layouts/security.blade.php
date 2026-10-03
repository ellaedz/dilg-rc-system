<!DOCTYPE html>
<html lang="en" data-theme="dilg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b2c78">
    <title>@yield('title', 'Secure Account - CIVICLEAR')</title>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-5 py-4">
            <div class="flex items-center gap-3">
                <img class="h-11 w-11" src="{{ asset('images/civiclear-logo.svg') }}" alt="CIVICLEAR logo">
                <div><strong class="block text-sm text-[#0b2c78]">CIVICLEAR</strong><span class="text-xs text-slate-500">Secure account setup</span></div>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-ghost btn-sm"><i class="fas fa-right-from-bracket" aria-hidden="true"></i> Log out</button>
            </form>
        </div>
    </header>
    <main class="mx-auto max-w-5xl px-5 py-10">
        @if(session('info'))<div class="alert alert-info mb-5" role="status"><i class="fas fa-circle-info"></i><span>{{ session('info') }}</span></div>@endif
        @if(session('error'))<div class="alert alert-error mb-5" role="alert"><i class="fas fa-circle-exclamation"></i><span>{{ session('error') }}</span></div>@endif
        @yield('content')
    </main>
</body>
</html>
