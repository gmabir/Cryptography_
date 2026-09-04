{{-- resources/views/dashboard.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <div class="relative overflow-hidden bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 rounded-2xl shadow-xl p-8 text-white flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-white/5 rounded-full blur-2xl pointer-events-none"></div>
        
        <div class="flex items-center gap-6 z-10">
            <div class="relative group">
                <img id="profileHeaderAvatar" 
                     src="{{ Auth::user()->profile_photo ? route('hostel.profile.photo', ['filename' => basename(Auth::user()->profile_photo)]) : 'https://ui-avatars.com/api/?name=Student&background=3b82f6&color=fff' }}" 
                     alt="Profile Picture" 
                     class="w-20 h-20 rounded-full object-cover border-4 border-white/20 shadow-lg transition-transform duration-300 group-hover:scale-105">
                <label for="avatarInput" class="absolute inset-0 bg-black/40 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer text-xs font-medium">
                    Change
                </label>
                <input type="file" id="avatarInput" accept="image/*" class="hidden" onchange="uploadProfilePhoto(this)">
            </div>
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-3xl font-extrabold tracking-tight" id="greetingUsername">Student Portal</h1>
                    <span id="user-role-badge" class="bg-blue-500/30 border border-blue-400/30 text-blue-200 text-xs font-semibold px-3 py-1 rounded-full uppercase tracking-wider backdrop-blur-sm">Student</span>
                </div>
                <p class="text-blue-200/80 text-sm mt-1 flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-400 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3 z-10">
            <button onclick="triggerFileSelect()" class="bg-white/10 hover:bg-white/20 border border-white/20 text-white px-4 py-2 rounded-xl text-sm font-medium transition-all backdrop-blur-sm flex items-center gap-2 shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                Upload Photo
            </button>
        </div>
    </div>

    <div id="dashboard-alert" class="hidden p-4 rounded-xl text-sm font-medium border shadow-sm"></div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 relative overflow-hidden">
                <div class="flex justify-between items-center border-b border-slate-100 pb-4 mb-4">
                    <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        Encrypted Profile (RSA)
                    </h2>
                    <button onclick="toggleEditProfile()" id="editProfileBtn" class="text-blue-600 hover:text-blue-700 text-xs font-semibold uppercase tracking-wider bg-blue-50 px-2.5 py-1 rounded-lg transition-colors">
                        Edit
                    </button>
                </div>

                <div id="profile-loading" class="py-8 text-center text-slate-400 text-sm animate-pulse">
                    Decrypting profile credentials...
                </div>

                <div id="profile-content" class="hidden space-y-4 text-sm">
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                        <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Username</span>
                        <span id="p-username" class="font-mono text-slate-800 font-medium"></span>
                    </div>
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                        <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Email Address</span>
                        <span id="p-email" class="font-mono text-slate-800 font-medium truncate block"></span>
                    </div>
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                        <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Phone Number</span>
                        <span id="p-phone" class="font-mono text-slate-800 font-medium">Not set</span>
                    </div>
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                        <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Student ID</span>
                        <span id="p-studentid" class="font-mono text-slate-800 font-medium">Not set</span>
                    </div>
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                        <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Residential Address</span>
                        <span id="p-address" class="font-mono text-slate-800 font-medium">Not set</span>
                    </div>
                </div>

                <form id="updateProfileForm" class="hidden space-y-4 pt-2">
                    <h3 class="font-bold text-xs text-slate-500 uppercase tracking-wider">Update Details</h3>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Phone</label>
                        <input type="text" id="edit-phone" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Student ID</label>
                        <input type="text" id="edit-studentid" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Address</label>
                        <input type="text" id="edit-address" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                    </div>
                    <div class="flex gap-2 pt-2">
                        <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded-xl text-sm font-semibold hover:bg-blue-700 shadow-sm transition-all">Save Changes</button>
                        <button type="button" onclick="toggleEditProfile()" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-xl text-sm font-semibold hover:bg-slate-200 transition-all">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-8">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h2 class="text-lg font-bold text-slate-800 border-b border-slate-100 pb-4 mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    Room Application & Allocation Status (ECC)
                </h2>

                <div id="accommodation-status" class="mb-6 p-4 rounded-xl bg-slate-50 border border-slate-200/60 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Current Allocation</span>
                        <span id="alloc-status" class="text-sm font-bold text-slate-700 mt-0.5 inline-block">Checking status...</span>
                    </div>
                    <div id="alloc-details" class="text-sm text-slate-600 text-right hidden"></div>
                </div>

                <form id="applicationForm" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Room Preferences</label>
                        <textarea id="app-preferences" required rows="2" class="w-full border border-slate-200 rounded-xl p-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500" placeholder="e.g., Single occupancy, quiet wing, upper floor"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Medical Needs (Optional)</label>
                        <input type="text" id="app-medical" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500" placeholder="e.g., Ground floor access required">
                    </div>
                    <button type="submit" class="w-full bg-gradient-to-r from-blue-900 to-indigo-900 text-white py-3 rounded-xl text-sm font-bold hover:from-blue-800 hover:to-indigo-800 shadow-md transition-all flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                        Submit Secure ECC Application
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h2 class="text-lg font-bold text-slate-800 border-b border-slate-100 pb-4 mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A40.015 40.015 0 017 6.083c3.2 0 6.3 1.1 8.8 3.1"></path></svg>
                    Maintenance Tickets & Complaints (ECC)
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <form id="maintenanceForm" class="space-y-4 md:col-span-1 md:border-r md:border-slate-100 md:pr-6">
                        <h3 class="font-bold text-xs text-slate-500 uppercase tracking-wider">Raise New Ticket</h3>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Issue Title</label>
                            <input type="text" id="maint-title" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Description</label>
                            <textarea id="maint-desc" required rows="3" class="w-full border border-slate-200 rounded-xl p-3 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500" placeholder="Describe the issue..."></textarea>
                        </div>
                        <button type="submit" class="w-full bg-slate-900 text-white py-2.5 rounded-xl text-sm font-semibold hover:bg-slate-800 shadow-sm transition-all">Submit Ticket</button>
                    </form>

                    <div class="md:col-span-2">
                        <h3 class="font-bold text-xs text-slate-500 uppercase tracking-wider mb-3">Ticket History</h3>
                        <div id="tickets-list" class="space-y-3 max-h-96 overflow-y-auto pr-1">
                            <p class="text-sm text-slate-400 text-center py-6">Loading tickets...</p>
                        </div>
                    </div>
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

function triggerFileSelect() {
    document.getElementById('avatarInput').click();
}

async function uploadProfilePhoto(input) {
    if (input.files.length === 0) return;
    const formData = new FormData();
    formData.append('photo', input.files[0]);

    try {
        const res = await fetch('/api/hostel/profile-photo', {
            method: 'POST',
            headers: { 
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content 
            },
            body: formData
        });

        const data = await res.json();
        if (res.ok) {
            const filename = data.photo_url.split('/').pop();
            document.getElementById('profileHeaderAvatar').src = `/hostel/profile-photo/${filename}`;
            showAlert(data.message, 'success');
        } else {
            showAlert(data.error || 'Upload failed.', 'error');
        }
    } catch (err) {
        console.error(err);
        showAlert('Network error occurred during photo upload.', 'error');
    }
}

function showAlert(message, type) {
    const alertBox = document.getElementById('dashboard-alert');
    alertBox.innerText = message;
    alertBox.className = `p-4 rounded-xl text-sm font-medium border shadow-sm ${type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-red-50 border-red-200 text-red-800'}`;
    alertBox.classList.remove('hidden');
    setTimeout(() => alertBox.classList.add('hidden'), 5000);
}

// Load Profile
async function loadProfile() {
    try {
        const res = await fetch('/api/profile', { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (res.ok) {
            const p = data.profile;
            document.getElementById('p-username').innerText = p.username;
            document.getElementById('greetingUsername').innerText = p.username;
            document.getElementById('p-email').innerText = p.email;
            document.getElementById('p-phone').innerText = p.phone || 'Not set';
            document.getElementById('p-studentid').innerText = p.student_id || 'Not set';
            document.getElementById('p-address').innerText = p.address || 'Not set';

            document.getElementById('edit-phone').value = p.phone || '';
            document.getElementById('edit-studentid').value = p.student_id || '';
            document.getElementById('edit-address').value = p.address || '';

            if (p.profile_photo) {
                const filename = p.profile_photo.split('/').pop();
                document.getElementById('profileHeaderAvatar').src = `/hostel/profile-photo/${filename}`;
            }

            document.getElementById('profile-loading').classList.add('hidden');
            document.getElementById('profile-content').classList.remove('hidden');
        }
    } catch (e) {
        console.error('Failed to load profile', e);
        document.getElementById('profile-loading').innerText = 'Failed to load profile credentials.';
    }
}

function toggleEditProfile() {
    const content = document.getElementById('profile-content');
    const form = document.getElementById('updateProfileForm');
    const btn = document.getElementById('editProfileBtn');
    content.classList.toggle('hidden');
    form.classList.toggle('hidden');
    btn.innerText = form.classList.contains('hidden') ? 'Edit' : 'Close';
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
        showAlert('Profile updated successfully.', 'success');
    } else {
        showAlert('Failed to update profile.', 'error');
    }
});

// Room Application Submit
document.getElementById('applicationForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const payload = {
        preferences: document.getElementById('app-preferences').value,
        medical_needs: document.getElementById('app-medical').value
    };

    const res = await fetch('/api/hostel/apply', {
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
        showAlert('Room application submitted securely via ECC!', 'success');
        document.getElementById('app-preferences').value = '';
        document.getElementById('app-medical').value = '';
        loadAccommodation();
    } else {
        showAlert(data.error || 'Submission failed', 'error');
    }
});

// Load Accommodation Status
async function loadAccommodation() {
    try {
        const res = await fetch('/api/my-allocation', { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        const statusEl = document.getElementById('alloc-status');
        const detailsEl = document.getElementById('alloc-details');

        if (res.ok) {
            statusEl.innerText = 'Allocated Room';
            statusEl.className = 'text-sm font-bold text-emerald-600 mt-0.5 inline-block';
            detailsEl.innerHTML = `Building: <b>${data.allocation.building_name}</b> | Room: <b>${data.allocation.room_number}</b>${data.allocation.notes ? '<br>Notes: ' + data.allocation.notes : ''}`;
            detailsEl.classList.remove('hidden');
        } else {
            statusEl.innerText = 'Pending / None Allocated';
            statusEl.className = 'text-sm font-bold text-amber-600 mt-0.5 inline-block';
        }
    } catch (e) {
        document.getElementById('alloc-status').innerText = 'Pending / None Allocated';
        document.getElementById('alloc-status').className = 'text-sm font-bold text-amber-600 mt-0.5 inline-block';
    }
}

// Maintenance Tickets Submit
document.getElementById('maintenanceForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const payload = {
        title: document.getElementById('maint-title').value,
        description: document.getElementById('maint-desc').value
    };

    const res = await fetch('/api/hostel/maintenance', {
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
        showAlert('Maintenance ticket raised successfully.', 'success');
    } else {
        showAlert('Failed to submit ticket.', 'error');
    }
});

// Load Maintenance Tickets
async function loadMaintenanceTickets() {
    try {
        const res = await fetch('/api/hostel/maintenance', { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        const listEl = document.getElementById('tickets-list');

        if (res.ok && data.requests && data.requests.length > 0) {
            listEl.innerHTML = data.requests.map(req => `
                <div class="p-4 border border-slate-100 rounded-xl bg-slate-50 text-sm space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="font-bold text-slate-800">${req.title}</span>
                        <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full uppercase ${req.status === 'resolved' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'}">${req.status}</span>
                    </div>
                    <p class="text-slate-600">${req.description}</p>
                    ${req.response ? `<div class="p-3 bg-indigo-50/60 border border-indigo-100 rounded-lg text-xs text-indigo-900"><b>Authority Response:</b> ${req.response}</div>` : ''}
                </div>
            `).join('');
        } else {
            listEl.innerHTML = '<p class="text-sm text-slate-400 text-center py-6">No maintenance tickets found.</p>';
        }
    } catch (e) {
        document.getElementById('tickets-list').innerHTML = '<p class="text-sm text-red-500 text-center py-6">Failed to load tickets.</p>';
    }
}
</script>
@endsection
