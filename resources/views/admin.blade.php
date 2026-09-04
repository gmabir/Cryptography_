@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto p-6 bg-white border rounded shadow-sm mt-10">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-blue-900">Admin Control Dashboard</h2>
        <form action="/api/logout" method="POST">
            @csrf
            <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 text-sm">Logout</button>
        </form>
    </div>

    <div id="error-message" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"></div>
    <div id="success-message" class="hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"></div>

    <!-- Pending Wardens Section -->
    <div class="mb-10">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Pending Warden Approvals</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead>
                    <tr class="bg-gray-100 text-left text-sm font-semibold text-gray-700">
                        <th class="py-2 px-4 border-b">ID</th>
                        <th class="py-2 px-4 border-b">Username</th>
                        <th class="py-2 px-4 border-b">Email</th>
                        <th class="py-2 px-4 border-b">Status</th>
                        <th class="py-2 px-4 border-b text-center">Action</th>
                    </tr>
                </thead>
                <tbody id="pendingWardensTable">
                    <tr>
                        <td colspan="5" class="py-4 px-4 text-center text-gray-500">Loading pending wardens...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- All Users Directory Section -->
    <div>
        <h3 class="text-lg font-semibold text-gray-800 mb-4">User Management Directory (Students & Wardens)</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead>
                    <tr class="bg-gray-100 text-left text-sm font-semibold text-gray-700">
                        <th class="py-2 px-4 border-b">ID</th>
                        <th class="py-2 px-4 border-b">Username</th>
                        <th class="py-2 px-4 border-b">Email</th>
                        <th class="py-2 px-4 border-b">Role</th>
                        <th class="py-2 px-4 border-b">Approval Status</th>
                        <th class="py-2 px-4 border-b text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="allUsersTable">
                    <tr>
                        <td colspan="6" class="py-4 px-4 text-center text-gray-500">Loading users...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
async function fetchPendingWardens() {
    try {
        const response = await fetch('/api/admin/pending-wardens', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        });
        const data = await response.json();
        const tbody = document.getElementById('pendingWardensTable');
        
        if (response.ok) {
            if (!data.pending_wardens || data.pending_wardens.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" class="py-4 px-4 text-center text-gray-500">No pending wardens found.</td></tr>`;
                return;
            }

            tbody.innerHTML = '';
            data.pending_wardens.forEach(warden => {
                tbody.innerHTML += `
                    <tr class="hover:bg-gray-50 text-sm">
                        <td class="py-2 px-4 border-b">${warden.id}</td>
                        <td class="py-2 px-4 border-b font-medium text-gray-800">${warden.username}</td>
                        <td class="py-2 px-4 border-b text-gray-600">${warden.email}</td>
                        <td class="py-2 px-4 border-b text-yellow-600 font-semibold">Pending Approval</td>
                        <td class="py-2 px-4 border-b text-center">
                            <button onclick="approveWarden(${warden.id})" class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 text-xs">Approve</button>
                        </td>
                    </tr>
                `;
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="5" class="py-4 px-4 text-center text-red-500">Failed to load pending wardens.</td></tr>`;
        }
    } catch (err) {
        console.error(err);
    }
}

async function fetchAllUsers() {
    try {
        const response = await fetch('/api/admin/users', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        });
        const data = await response.json();
        const tbody = document.getElementById('allUsersTable');

        if (response.ok) {
            if (!data.users || data.users.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="py-4 px-4 text-center text-gray-500">No users found.</td></tr>`;
                return;
            }

            tbody.innerHTML = '';
            data.users.forEach(user => {
                const approvalText = user.role === 'warden' 
                    ? (user.is_approved ? '<span class="text-green-600 font-semibold">Approved</span>' : '<span class="text-yellow-600 font-semibold">Pending</span>')
                    : '<span class="text-gray-400">N/A</span>';

                tbody.innerHTML += `
                    <tr class="hover:bg-gray-50 text-sm">
                        <td class="py-2 px-4 border-b">${user.id}</td>
                        <td class="py-2 px-4 border-b font-medium text-gray-800">${user.username}</td>
                        <td class="py-2 px-4 border-b text-gray-600">${user.email}</td>
                        <td class="py-2 px-4 border-b capitalize font-semibold">${user.role}</td>
                        <td class="py-2 px-4 border-b">${approvalText}</td>
                        <td class="py-2 px-4 border-b text-center">
                            <button onclick="deleteUser(${user.id})" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 text-xs">Delete</button>
                        </td>
                    </tr>
                `;
            });
        }
    } catch (err) {
        console.error(err);
    }
}

async function approveWarden(id) {
    const errorDiv = document.getElementById('error-message');
    const successDiv = document.getElementById('success-message');
    errorDiv.classList.add('hidden');
    successDiv.classList.add('hidden');

    try {
        const response = await fetch(`/api/admin/approve-warden/${id}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        });
        const data = await response.json();

        if (response.ok) {
            successDiv.innerText = data.message;
            successDiv.classList.remove('hidden');
            fetchPendingWardens();
            fetchAllUsers();
        } else {
            errorDiv.innerText = data.error || 'Approval failed.';
            errorDiv.classList.remove('hidden');
        }
    } catch (err) {
        errorDiv.innerText = 'Network error occurred.';
        errorDiv.classList.remove('hidden');
    }
}

async function deleteUser(id) {
    if (!confirm('Are you sure you want to permanently delete this user account?')) return;

    const errorDiv = document.getElementById('error-message');
    const successDiv = document.getElementById('success-message');
    errorDiv.classList.add('hidden');
    successDiv.classList.add('hidden');

    try {
        const response = await fetch(`/api/admin/users/${id}`, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        });
        const data = await response.json();

        if (response.ok) {
            successDiv.innerText = data.message;
            successDiv.classList.remove('hidden');
            fetchPendingWardens();
            fetchAllUsers();
        } else {
            errorDiv.innerText = data.error || 'Deletion failed.';
            errorDiv.classList.remove('hidden');
        }
    } catch (err) {
        errorDiv.innerText = 'Network error occurred.';
        errorDiv.classList.remove('hidden');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    fetchPendingWardens();
    fetchAllUsers();
});
</script>
@endsection
@include('components.chat')