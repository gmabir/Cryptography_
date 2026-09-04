<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureHostel</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800 antialiased selection:bg-blue-600 selection:text-white">
    <!-- Modern Navbar -->
    <nav class="bg-gradient-to-r from-blue-950 via-blue-900 to-indigo-950 text-white shadow-md sticky top-0 z-40 backdrop-blur-md bg-opacity-95 border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex justify-between items-center">
            <a href="/" class="text-xl font-extrabold tracking-tight flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-blue-500 animate-pulse"></span>
                SecureHostel
            </a>
            <div class="flex items-center gap-4 text-sm font-medium">
                @guest
                    <a href="/login" class="hover:text-blue-300 transition">Login</a>
                    <a href="/register" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-xl shadow-sm transition">Register</a>
                @endguest
                @auth
                    <a href="/dashboard" class="hover:text-blue-300 transition">Dashboard</a>
                    <button onclick="logout()" class="bg-red-500/20 hover:bg-red-500/30 border border-red-500/30 text-red-100 px-4 py-2 rounded-xl transition">Logout</button>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @yield('content')
    </main>

    <!-- Automatically inject floating Messenger chat on all pages for logged-in users -->
    @auth
        @include('components.chat')
    @endauth

    <script>
        async function logout() {
            try {
                const response = await fetch('/api/logout', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                if (response.ok) {
                    window.location.href = '/login';
                }
            } catch (error) {
                console.error('Logout failed', error);
            }
        }
    </script>
</body>
</html>