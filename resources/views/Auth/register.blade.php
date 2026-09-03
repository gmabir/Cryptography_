@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto bg-white p-8 border rounded shadow-sm mt-10">
    <h2 class="text-2xl font-bold mb-6 text-center text-blue-900">Create Account</h2>
    
    <div id="error-message" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"></div>
    
    <div id="success-message" class="hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        <p class="font-bold">Registration Successful!</p>
        <p class="mt-2 text-sm" id="success-text">Your account has been created successfully.</p>
        <a href="/login" class="block text-center bg-blue-600 text-white rounded py-2 mt-4 hover:bg-blue-700">Proceed to Login</a>
    </div>

    <form id="registerForm" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Username</label>
            <input type="text" id="username" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm border p-2 focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Email Address</label>
            <input type="email" id="email" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm border p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Enter your real Gmail">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Password</label>
            <input type="password" id="password" required minlength="8" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm border p-2 focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Role</label>
            <select id="role" onchange="toggleAdminSecret()" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm border p-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="student">Student</option>
                <option value="warden">Hostel Authority / Warden</option>
                <option value="admin">Administrator</option>
            </select>
        </div>
        <div id="adminSecretDiv" class="hidden">
            <label class="block text-sm font-medium text-gray-700">Admin Secret Key</label>
            <input type="password" id="admin_secret" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm border p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Enter secret key">
        </div>
        <button type="submit" id="submitBtn" class="w-full bg-blue-900 text-white rounded-md py-2 hover:bg-blue-800 transition">Register Securely</button>
    </form>
</div>

<script>
function toggleAdminSecret() {
    const role = document.getElementById('role').value;
    const div = document.getElementById('adminSecretDiv');
    if (role === 'admin') {
        div.classList.remove('hidden');
    } else {
        div.classList.add('hidden');
    }
}

document.getElementById('registerForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    const errorDiv = document.getElementById('error-message');
    const successDiv = document.getElementById('success-message');
    const form = this;
    
    submitBtn.disabled = true;
    submitBtn.innerText = 'Encrypting & Registering...';
    errorDiv.classList.add('hidden');
    
    const payload = {
        username: document.getElementById('username').value,
        email: document.getElementById('email').value,
        password: document.getElementById('password').value,
        role: document.getElementById('role').value,
        admin_secret: document.getElementById('admin_secret').value
    };

    try {
        const response = await fetch('/api/register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (response.ok) {
            form.classList.add('hidden');
            document.getElementById('success-text').innerText = data.message;
            successDiv.classList.remove('hidden');
        } else {
            errorDiv.innerText = data.error || data.message || 'Registration failed.';
            errorDiv.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.innerText = 'Register Securely';
        }
    } catch (error) {
        errorDiv.innerText = 'Network error occurred.';
        errorDiv.classList.remove('hidden');
        submitBtn.disabled = false;
        submitBtn.innerText = 'Register Securely';
    }
});
</script>
@endsection