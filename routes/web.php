<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HostelController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Public Authentication & Registration Views
Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/register', function () {
    return view('auth.register');
});

Route::get('/verify-otp', function () {
    return view('auth.verify-otp');
})->name('verify.otp');

// Authentication API Endpoints
Route::post('/api/register', [AuthController::class, 'register']);
Route::post('/api/login/send-otp', [AuthController::class, 'sendLoginOtp']);
Route::post('/api/login/verify-otp', [AuthController::class, 'verifyLoginOtp']);
Route::post('/api/logout', [AuthController::class, 'logout']);

// Authenticated Dashboard Routing (Role-Based View Dispatcher)
Route::get('/dashboard', function () {
    $user = auth()->user();
    if (!$user) {
        return redirect('/login');
    }
    if ($user->role === 'admin') {
        return view('admin');
    }
    if ($user->role === 'warden') {
        return view('warden');
    }
    return view('dashboard');
})->middleware('auth')->name('dashboard');

// Protected Role-Based API Routes
Route::middleware(['auth'])->group(function () {
    // Admin-Only Routes (Warden Approvals & User Management Directory)
    Route::get('/api/admin/pending-wardens', [HostelController::class, 'getPendingWardens']);
    Route::post('/api/admin/approve-warden/{id}', [HostelController::class, 'approveWarden']);
    Route::get('/api/admin/users', [AuthController::class, 'getAllUsers']);
    Route::delete('/api/admin/users/{id}', [AuthController::class, 'deleteUser']);
    
    // Student & Warden Hostel Management Routes
    Route::post('/api/hostel/apply', [HostelController::class, 'applyForRoom']);
    Route::get('/api/my-allocation', [HostelController::class, 'myAccommodation']);
    Route::get('/api/hostel/applications', [HostelController::class, 'viewApplications']);
    Route::post('/api/hostel/allocate', [HostelController::class, 'allocateRoom']);

    // Warden Management API Routes
    Route::get('/api/warden/applications', [HostelController::class, 'viewApplications']);
    Route::post('/api/warden/allocations', [HostelController::class, 'allocateRoom']);
    Route::get('/api/warden/maintenance', [HostelController::class, 'getWardenMaintenanceTickets']);
    Route::post('/api/warden/maintenance/{id}/respond', [HostelController::class, 'respondMaintenanceTicket']);

    // Profile Photo & Management Routes
    Route::post('/api/hostel/profile-photo', [HostelController::class, 'uploadProfilePhoto']);
    Route::get('/hostel/profile-photo/{filename}', [HostelController::class, 'viewProfilePhoto'])->name('hostel.profile.photo');
    Route::get('/api/profile', [AuthController::class, 'getProfile']);
    Route::post('/api/profile/update', [AuthController::class, 'updateProfile']);
    
    // Maintenance Ticket Routes
    Route::get('/api/hostel/maintenance', [HostelController::class, 'getMaintenanceTickets']);
    Route::post('/api/hostel/maintenance', [HostelController::class, 'storeMaintenanceTicket']);

    //message
    Route::get('/api/hostel/messages', [\App\Http\Controllers\HostelController::class, 'getMessages']);
    Route::post('/api/hostel/messages', [\App\Http\Controllers\HostelController::class, 'sendMessage']);
});