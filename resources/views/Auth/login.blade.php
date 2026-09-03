@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto bg-white p-8 border rounded shadow-sm mt-10">
    <h2 class="text-2xl font-bold mb-6 text-center text-blue-900">Secure Gmail Login</h2>
    
    <div id="error-message" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"></div>
    <div id="success-message" class="hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"></div>

    <!-- Step 1 Form: Username & Password -->
    <form id="step1Form" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Username</label>
            <input type="text" id="username" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm border p-2">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Password</label>
            <input type="password" id="password" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm border p-2">
        </div>
        <button type="submit" id="step1Btn" class="w-full bg-blue-900 text-white rounded-md py-2 hover:bg-blue-800 transition">Continue & Send Gmail OTP</button>
    </form>

    <!-- Step 2 Form: Enter Email OTP (Hidden by default) -->
    <form id="step2Form" class="space-y-4 hidden">
        <p class="text-sm text-gray-600 bg-blue-50 p-3 rounded">We have sent a 6-digit code to your registered Gmail inbox.</p>
        <div>
            <label class="block text-sm font-medium text-gray-700">Enter 6-Digit Code</label>
            <input type="text" id="otp" required pattern="[0-9]{6}" maxlength="6" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm border p-2 font-mono tracking-widest text-center text-xl" placeholder="123456">
        </div>
        <button type="submit" id="step2Btn" class="w-full bg-green-700 text-white rounded-md py-2 hover:bg-green-600 transition">Verify Code & Login</button>
    </form>
</div>

<script>
let tempUsername = '';
let tempPassword = '';

// Step 1 Submission
document.getElementById('step1Form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('step1Btn');
    const errorDiv = document.getElementById('error-message');
    const successDiv = document.getElementById('success-message');
    
    btn.disabled = true;
    btn.innerText = 'Sending OTP to Gmail...';
    errorDiv.classList.add('hidden');
    
    tempUsername = document.getElementById('username').value;
    tempPassword = document.getElementById('password').value;

    try {
        const response = await fetch('/api/login/send-otp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ username: tempUsername, password: tempPassword })
        });

        const data = await response.json();

        if (response.ok) {
            successDiv.innerText = data.message;
            successDiv.classList.remove('hidden');
            document.getElementById('step1Form').classList.add('hidden');
            document.getElementById('step2Form').classList.remove('hidden');
        } else {
            errorDiv.innerText = data.error || 'Login failed.';
            errorDiv.classList.remove('hidden');
            btn.disabled = false;
            btn.innerText = 'Continue & Send Gmail OTP';
        }
    } catch (error) {
        errorDiv.innerText = 'Network error occurred.';
        errorDiv.classList.remove('hidden');
        btn.disabled = false;
        btn.innerText = 'Continue & Send Gmail OTP';
    }
});

// Step 2 Submission (OTP Verification)
document.getElementById('step2Form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('step2Btn');
    const errorDiv = document.getElementById('error-message');
    
    btn.disabled = true;
    btn.innerText = 'Verifying...';
    errorDiv.classList.add('hidden');

    try {
        const response = await fetch('/api/login/verify-otp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ otp: document.getElementById('otp').value })
        });

        const data = await response.json();

        if (response.ok) {
            window.location.href = '/dashboard';
        } else {
            errorDiv.innerText = data.error || 'Invalid OTP.';
            errorDiv.classList.remove('hidden');
            btn.disabled = false;
            btn.innerText = 'Verify Code & Login';
        }
    } catch (error) {
        errorDiv.innerText = 'Network error occurred.';
        errorDiv.classList.remove('hidden');
        btn.disabled = false;
        btn.innerText = 'Verify Code & Login';
    }
});
</script>
@endsection