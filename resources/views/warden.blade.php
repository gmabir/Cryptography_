@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <!-- Header -->
    <div class="bg-white p-6 rounded-lg shadow-sm border flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-blue-900">Hostel Authority / Warden Dashboard</h1>
            <p class="text-gray-600 mt-1">Manage room applications, allocations, and maintenance responses securely.</p>
        </div>
        <div class="bg-purple-100 text-purple-800 text-sm font-semibold px-3 py-1 rounded-full uppercase">Warden</div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Room Applications Management -->
        <div class="bg-white p-6 rounded-lg shadow-sm border">
            <h2 class="text-xl font-bold mb-4 text-gray-800 border-b pb-2">Student Room Applications (ECC)</h2>
            <div id="applications-list" class="space-y-4 max-h-96 overflow-y-auto">
                <p class="text-sm text-gray-500">Loading applications...</p>
            </div>
        </div>

        <!-- Room Allocation Form -->
        <div class="bg-white p-6 rounded-lg shadow-sm border">
            <h2 class="text-xl font-bold mb-4 text-gray-800 border-b pb-2">Allocate Room (ECC)</h2>
            <form id="allocationForm" class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-700">Student User ID</label>
                    <input type="number" id="alloc-userid" required class="w-full border rounded p-2 text-sm" placeholder="Enter Student ID">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700">Building Name</label>
                    <input type="text" id="alloc-building" required class="w-full border rounded p-2 text-sm" placeholder="e.g., North Block">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700">Room Number</label>
                    <input type="text" id="alloc-room" required class="w-full border rounded p-2 text-sm" placeholder="e.g., 402-B">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700">Notes (Optional)</label>
                    <input type="text" id="alloc-notes" class="w-full border rounded p-2 text-sm" placeholder="e.g., Corner room">
                </div>
                <button type="submit" class="w-full bg-blue-900 text-white py-2 rounded text-sm hover:bg-blue-800">Securely Allocate Room</button>
            </form>
        </div>
    </div>

    <!-- Maintenance Management -->
    <div class="bg-white p-6 rounded-lg shadow-sm border">
        <h2 class="text-xl font-bold mb-4 text-gray-800 border-b pb-2">Maintenance Tickets Review (ECC)</h2>
        <div id="warden-tickets-list" class="space-y-4">
            <p class="text-sm text-gray-500">Loading tickets...</p>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    loadWardenApplications();
    loadWardenMaintenance();
});

async function loadWardenApplications() {
    try {
        const res = await fetch('/api/warden/applications', { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        const listEl = document.getElementById('applications-list');

        if (res.ok && data.applications.length > 0) {
            listEl.innerHTML = data.applications.map(app => `
                <div class="p-3 border rounded bg-gray-50 text-sm flex justify-between items-center">
                    <div>
                        <p class="font-bold text-gray-800">Student ID: ${app.user_id} <span class="text-xs text-blue-600 font-normal">(${app.status})</span></p>
                        <p class="text-gray-600 mt-1"><b>Preferences:</b> ${app.preferences}</p>
                        ${app.medical_needs ? `<p class="text-red-600 text-xs mt-0.5"><b>Medical:</b> ${app.medical_needs}</p>` : ''}
                    </div>
                    <button onclick="quickAllocate(${app.user_id})" class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-500">Select</button>
                </div>
            `).join('');
        } else {
            listEl.innerHTML = '<p class="text-sm text-gray-500">No applications found.</p>';
        }
    } catch (e) {
        document.getElementById('applications-list').innerHTML = '<p class="text-sm text-red-500">Failed to load applications.</p>';
    }
}

function quickAllocate(userId) {
    document.getElementById('alloc-userid').value = userId;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

document.getElementById('allocationForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const payload = {
        student_id: document.getElementById('alloc-userid').value,
        building_name: document.getElementById('alloc-building').value,
        room_number: document.getElementById('alloc-room').value,
        notes: document.getElementById('alloc-notes').value
    };

    const res = await fetch('/api/warden/allocations', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
    });

    if (res.ok) {
        alert('Room allocated successfully via ECC!');
        document.getElementById('allocationForm').reset();
        loadWardenApplications();
    } else {
        alert('Allocation failed.');
    }
});

async function loadWardenMaintenance() {
    try {
        const res = await fetch('/api/warden/maintenance', { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        const listEl = document.getElementById('warden-tickets-list');

        if (res.ok && data.requests.length > 0) {
            listEl.innerHTML = data.requests.map(req => `
                <div class="p-4 border rounded bg-gray-50 text-sm space-y-2">
                    <div class="flex justify-between font-bold">
                        <span>Ticket #${req.id}: ${req.title} <span class="text-xs font-normal text-gray-500">(Student ID: ${req.student_id})</span></span>
                        <span class="px-2 py-0.5 text-xs rounded uppercase bg-yellow-100 text-yellow-800">${req.status}</span>
                    </div>
                    <p class="text-gray-700">${req.description}</p>
                    ${req.response ? `<div class="p-2 bg-blue-50 border border-blue-200 rounded text-xs text-blue-900"><b>Current Response:</b> ${req.response}</div>` : ''}
                    
                    <form onsubmit="submitResponse(event, ${req.id})" class="mt-2 flex space-x-2 pt-2 border-t">
                        <input type="text" id="resp-${req.id}" required placeholder="Write response..." class="flex-1 border rounded p-1 text-xs">
                        <select id="status-${req.id}" class="border rounded p-1 text-xs">
                            <option value="open">Open</option>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                        </select>
                        <button type="submit" class="bg-gray-800 text-white px-3 py-1 rounded text-xs hover:bg-gray-700">Reply</button>
                    </form>
                </div>
            `).join('');
        } else {
            listEl.innerHTML = '<p class="text-sm text-gray-500">No maintenance tickets found.</p>';
        }
    } catch (e) {
        document.getElementById('warden-tickets-list').innerHTML = '<p class="text-sm text-red-500">Failed to load maintenance tickets.</p>';
    }
}

async function submitResponse(e, id) {
    e.preventDefault();
    const payload = {
        response: document.getElementById(`resp-${id}`).value,
        status: document.getElementById(`status-${id}`).value
    };

    const res = await fetch(`/api/warden/maintenance/${id}/respond`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
    });

    if (res.ok) {
        loadWardenMaintenance();
    } else {
        alert('Failed to send response.');
    }
}
</script>
@endsection