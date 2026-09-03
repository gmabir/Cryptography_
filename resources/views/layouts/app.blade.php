<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureHostel</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen font-sans antialiased text-gray-900">
    <nav class="bg-blue-900 text-white shadow-lg">
        <div class="max-w-6xl mx-auto px-4 py-3 flex justify-between items-center">
            <a href="/" class="text-2xl font-bold tracking-tight">SecureHostel</a>
            <div class="space-x-4">
                @guest
                    <a href="/login" class="hover:text-blue-300">Login</a>
                    <a href="/register" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded">Register</a>
                @endguest
                @auth
                    <a href="/dashboard" class="hover:text-blue-300">Dashboard</a>
                    <button onclick="logout()" class="bg-red-600 hover:bg-red-500 px-4 py-2 rounded">Logout</button>
                @endauth
            </div>
        </div>
    </nav>

    <main class="max-w-6xl mx-auto px-4 py-8">
        @yield('content')
    </main>

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