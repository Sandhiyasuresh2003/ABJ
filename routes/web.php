<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\PermissionRequestController;
use App\Http\Controllers\PettyCashController;
use App\Http\Controllers\RequestReviewController;

Route::match(['get', 'post'], '/request-review/{kind}/{id}/{decision}', RequestReviewController::class)
    ->whereIn('kind', ['leave', 'permission'])
    ->whereNumber('id')
    ->whereIn('decision', ['approve', 'reject'])
    ->middleware(['signed', 'throttle:30,1'])
    ->name('requests.review');

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : redirect()->route('login'));
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
});
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('employees', EmployeeController::class)->except(['show']);

    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/create', [AttendanceController::class, 'create'])->name('attendance.create');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::get('/attendance/{attendance}/edit', [AttendanceController::class, 'edit'])->name('attendance.edit');
    Route::put('/attendance/{attendance}', [AttendanceController::class, 'update'])->name('attendance.update');
    Route::delete('/attendance/{attendance}', [AttendanceController::class, 'destroy'])->name('attendance.destroy');

    Route::get('/leave-requests', [LeaveRequestController::class, 'index'])->name('leaves.index');
    Route::get('/leave-requests/create', [LeaveRequestController::class, 'create'])->name('leaves.create');
    Route::post('/leave-requests', [LeaveRequestController::class, 'store'])->name('leaves.store');
    Route::patch('/leave-requests/{leaveRequest}/status', [LeaveRequestController::class, 'status'])->name('leaves.status');
    Route::delete('/leave-requests/{leaveRequest}', [LeaveRequestController::class, 'destroy'])->name('leaves.destroy');

    Route::get('/permission-requests', [PermissionRequestController::class, 'index'])->name('permissions.index');
    Route::get('/permission-requests/create', [PermissionRequestController::class, 'create'])->name('permissions.create');
    Route::post('/permission-requests', [PermissionRequestController::class, 'store'])->name('permissions.store');
    Route::patch('/permission-requests/{permissionRequest}/status', [PermissionRequestController::class, 'status'])->name('permissions.status');
    Route::delete('/permission-requests/{permissionRequest}', [PermissionRequestController::class, 'destroy'])->name('permissions.destroy');

    Route::get('/petty-cash', [PettyCashController::class, 'index'])->name('petty-cash.index');
    Route::get('/petty-cash/create', [PettyCashController::class, 'create'])->name('petty-cash.create');
    Route::post('/petty-cash', [PettyCashController::class, 'store'])->name('petty-cash.store');
    Route::get('/petty-cash/{pettyCash}/edit', [PettyCashController::class, 'edit'])->name('petty-cash.edit');
    Route::put('/petty-cash/{pettyCash}', [PettyCashController::class, 'update'])->name('petty-cash.update');
    Route::delete('/petty-cash/{pettyCash}', [PettyCashController::class, 'destroy'])->name('petty-cash.destroy');
});
