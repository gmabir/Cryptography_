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
    Route::get('/api/hostel/my-allocation', [HostelController::class, 'myAccommodation']);
    Route::get('/api/hostel/applications', [HostelController::class, 'viewApplications']);
    Route::post('/api/hostel/allocate', [HostelController::class, 'allocateRoom']);
});