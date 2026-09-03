@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <!-- Welcome Header -->
    <div class="bg-white p-6 rounded-lg shadow-sm border flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-blue-900">Student Dashboard</h1>
            <p class="text-gray-600 mt-1">Welcome back! All your data is secured with RSA & ECC encryption.</p>
        </div>
        <div id="user-role-badge" class="bg-blue-100 text-blue-800 text-sm font-semibold px-3 py-1 rounded-full uppercase">Student</div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Profile Section -->
        <div class="bg-white p-6 rounded-lg shadow-sm border">
            <h2 class="text-xl font-bold mb-4 text-gray-800 border-b pb-2">My Encrypted Profile (RSA)</h2>
            <div id="profile-loading" class="text-gray-500 py-4">Decrypting profile...</div>
            <div id="profile-content" class="hidden space-y-3">
                <div><span class="font-semibold text-gray-600">Username:</span> <span id="p-username" class="text-gray-900 font-mono"></span></div>
                <div><span class="font-semibold text-gray-600">Email:</span> <span id="p-email" class="text-gray-900 font-mono"></span></div>
                <div><span class="font-semibold text-gray-600">Phone:</span> <span id="p-phone" class="text-gray-900 font-mono">Not set</span></div>
                <div><span class="font-semibold text-gray-600">Student ID:</span> <span id="p-studentid" class="text-gray-900 font-mono">Not set</span></div>
                <div><span class="font-semibold text-gray-600">Address:</span> <span id="p-address" class="text-gray-900 font-mono">Not set</span></div>
                
                <button onclick="toggleEditProfile()" class="mt-4 bg-gray-800 text-white px-4 py-2 rounded text-sm hover:bg-gray-700">Update Profile</button>
            </div>

            <!-- Edit Profile Form -->
            <form id="updateProfileForm" class="hidden mt-4 space-y-3 border-t pt-4">
                <h3 class="font-bold text-sm text-gray-700">Edit Additional Details</h3>
                <div>
                    <label class="block text-xs text-gray-600">Phone</label>
                    <input type="text" id="edit-phone" class="w-full border rounded p-1 text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-600">Student ID</label>
                    <input type="text" id="edit-studentid" class="w-full border rounded p-1 text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-600">Address</label>
                    <input type="text" id="edit-address" class="w-full border rounded p-1 text-sm">
                </div>
                <div class="flex space-x-2">
                    <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-500">Save</button>
                    <button type="button" onclick="toggleEditProfile()" class="bg-gray-300 text-gray-800 px-3 py-1 rounded text-sm">Cancel</button>
                </div>
            </form>
        </div>

        <!-- Room Application Section (ECC) -->
        <div class="bg-white p-6 rounded-lg shadow-sm border">
            <h2 class="text-xl font-bold mb-4 text-gray-800 border-b pb-2">Room Application (ECC)</h2>
            
            <div id="accommodation-status" class="mb-4 p-4 bg-gray-50 rounded border">
                <p class="text-sm font-semibold text-gray-700">Allocation Status: <span id="alloc-status" class="text-blue-600">Checking...</span></p>
                <div id="alloc-details" class="mt-2 text-sm text-gray-600 hidden"></div>
            </div>

            <form id="applicationForm" class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-700">Room Preferences</label>
                    <textarea id="app-preferences" required rows="2" class="w-full border rounded p-2 text-sm" placeholder="e.g., Single room, quiet block"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700">Medical Needs (Optional)</label>
                    <input type="text" id="app-medical" class="w-full border rounded p-2 text-sm" placeholder="e.g., Ground floor required">
                </div>
                <button type="submit" class="w-full bg-blue-900 text-white py-2 rounded text-sm hover:bg-blue-800">Submit Secure Application</button>
            </form>
        </div>
    </div>

    <!-- Maintenance Tickets Section (ECC) -->
    <div class="bg-white p-6 rounded-lg shadow-sm border">
        <h2 class="text-xl font-bold mb-4 text-gray-800 border-b pb-2">Maintenance Tickets & Complaints (ECC)</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Create Ticket Form -->
            <form id="maintenanceForm" class="space-y-3 md:col-span-1 border-r pr-6">
                <h3 class="font-bold text-sm text-gray-700">Raise New Ticket</h3>
                <div>
                    <label class="block text-xs font-semibold text-gray-700">Issue Title</label>
                    <input type="text" id="maint-title" required class="w-full border rounded p-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700">Description</label>
                    <textarea id="maint-desc" required rows="3" class="w-full border rounded p-2 text-sm" placeholder="Describe the issue..."></textarea>
                </div>
                <button type="submit" class="w-full bg-gray-900 text-white py-2 rounded text-sm hover:bg-gray-800">Send Ticket</button>
            </form>

            <!-- Ticket List -->
            <div class="md:col-span-2">
                <h3 class="font-bold text-sm text-gray-700 mb-2">My Tickets</h3>
                <div id="tickets-list" class="space-y-3 max-h-96 overflow-y-auto">
                    <p class="text-sm text-gray-500">Loading tickets...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    loadProfile();
    loadAccommodation();
    loadMaintenanceTickets();
});

// Load Profile
async function loadProfile() {
    try {
        const res = await fetch('/api/profile', { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (res.ok) {
            const p = data.profile;
            document.getElementById('p-username').innerText = p.username;
            document.getElementById('p-email').innerText = p.email;
            document.getElementById('p-phone').innerText = p.phone || 'Not set';
            document.getElementById('p-studentid').innerText = p.student_id || 'Not set';
            document.getElementById('p-address').innerText = p.address || 'Not set';

            document.getElementById('edit-phone').value = p.phone || '';
            document.getElementById('edit-studentid').value = p.student_id || '';
            document.getElementById('edit-address').value = p.address || '';

            document.getElementById('profile-loading').classList.add('hidden');
            document.getElementById('profile-content').classList.remove('hidden');
        }
    } catch (e) {
        console.error('Failed to load profile', e);
    }
}

function toggleEditProfile() {
    const content = document.getElementById('profile-content');
    const form = document.getElementById('updateProfileForm');
    content.classList.toggle('hidden');
    form.classList.toggle('hidden');
}

// Update Profile
document.getElementById('updateProfileForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const payload = {
        phone: document.getElementById('edit-phone').value,
        student_id: document.getElementById('edit-studentid').value,
        address: document.getElementById('edit-address').value
    };

    const res = await fetch('/api/profile/update', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
    });

    if (res.ok) {
        toggleEditProfile();
        loadProfile();
    }
});

// Room Application Submit
document.getElementById('applicationForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const payload = {
        preferences: document.getElementById('app-preferences').value,
        medical_needs: document.getElementById('app-medical').value
    };

    const res = await fetch('/api/applications', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
    });

    const data = await res.json();
    if (res.ok) {
        alert('Room application submitted securely via ECC!');
        document.getElementById('app-preferences').value = '';
        document.getElementById('app-medical').value = '';
    } else {
        alert(data.error || 'Submission failed');
    }
});

// Load Accommodation Status
async function loadAccommodation() {
    try {
        const res = await fetch('/api/my-accommodation', { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        const statusEl = document.getElementById('alloc-status');
        const detailsEl = document.getElementById('alloc-details');

        if (res.ok) {
            statusEl.innerText = 'Allocated';
            statusEl.className = 'text-green-600 font-bold';
            detailsEl.innerHTML = `Building: <b>${data.allocation.building_name}</b> | Room: <b>${data.allocation.room_number}</b><br>${data.allocation.notes ? 'Notes: ' + data.allocation.notes : ''}`;
            detailsEl.classList.remove('hidden');
        } else {
            statusEl.innerText = 'Pending / None';
        }
    } catch (e) {
        document.getElementById('alloc-status').innerText = 'Pending / None';
    }
}

// Maintenance Tickets Submit
document.getElementById('maintenanceForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const payload = {
        title: document.getElementById('maint-title').value,
        description: document.getElementById('maint-desc').value
    };

    const res = await fetch('/api/maintenance', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
    });

    if (res.ok) {
        document.getElementById('maint-title').value = '';
        document.getElementById('maint-desc').value = '';
        loadMaintenanceTickets();
    }
});

// Load Maintenance Tickets
async function loadMaintenanceTickets() {
    try {
        const res = await fetch('/api/maintenance', { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        const listEl = document.getElementById('tickets-list');

        if (res.ok && data.requests.length > 0) {
            listEl.innerHTML = data.requests.map(req => `
                <div class="p-3 border rounded bg-gray-50 text-sm">
                    <div class="flex justify-between font-bold">
                        <span>${req.title}</span>
                        <span class="px-2 py-0.5 text-xs rounded uppercase ${req.status === 'resolved' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">${req.status}</span>
                    </div>
                    <p class="text-gray-600 mt-1">${req.description}</p>
                    ${req.response ? `<div class="mt-2 p-2 bg-blue-50 border border-blue-200 rounded text-xs text-blue-900"><b>Authority Response:</b> ${req.response}</div>` : ''}
                </div>
            `).join('');
        } else {
            listEl.innerHTML = '<p class="text-sm text-gray-500">No tickets found.</p>';
        }
    } catch (e) {
        document.getElementById('tickets-list').innerHTML = '<p class="text-sm text-red-500">Failed to load tickets.</p>';
    }
}
</script>
@endsection