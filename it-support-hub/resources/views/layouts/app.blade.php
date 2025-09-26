<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>IT Support Hub</title>
    <!-- Tailwind via CDN for quick preview; Breeze would compile assets -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body{font-family:Inter,ui-sans-serif,system-ui}; </style>
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ route('articles.index') }}" class="text-lg font-semibold">IT Support Hub</a>
            <div class="flex items-center gap-3">
                @auth
                    <span class="text-sm text-gray-600">Hello, {{ auth()->user()->name }} ({{ auth()->user()->role ?? 'staff' }})</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="text-sm text-blue-700">Logout</button>
                    </form>
                @else
                    <a class="text-sm text-blue-700" href="{{ route('login') }}">Login</a>
                @endauth
            </div>
        </div>
    </nav>

    @if (session('status'))
        <div class="max-w-7xl mx-auto px-6 mt-4">
            <div class="rounded bg-green-100 text-green-800 px-4 py-3">{{ session('status') }}</div>
        </div>
    @endif

    @yield('content')
</body>
</html>



