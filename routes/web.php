<?php

use App\Http\Controllers\Admin\PasswordRecoveryCodeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\AccountPasswordSetupController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeePortalController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\PermissionRequestController;
use App\Http\Controllers\PettyCashController;
use App\Http\Controllers\RequestReviewController;
use Illuminate\Support\Facades\Route;

Route::view('/agropro', 'agropro.index')->name('agropro.home');
Route::get('/agropro/{page}.html', fn (string $page) => view('agropro.'.$page))
    ->whereIn('page', ['index', 'about', 'service', 'testimonail', 'blog', 'contact'])
    ->name('agropro.page');

Route::match(['get', 'post'], '/request-review/{kind}/{id}/{decision}', RequestReviewController::class)
    ->whereIn('kind', ['leave', 'permission'])
    ->whereNumber('id')
    ->whereIn('decision', ['approve', 'reject'])
    ->middleware(['signed', 'throttle:30,1'])
    ->name('requests.review');

Route::get('/', fn () => auth()->check()
    ? redirect()->route(auth()->user()->role === 'employee' ? 'employee.workspace' : 'dashboard')
    : redirect()->route('login'));
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit');

    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.verify');
    Route::get('/reset-password', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');
    Route::get('/set-password/{token}', [AccountPasswordSetupController::class, 'edit'])
        ->middleware('signed')
        ->name('account.password.setup');
    Route::post('/set-password/{token}', [AccountPasswordSetupController::class, 'update'])
        ->middleware('signed')
        ->name('account.password.setup.update');
});
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'employee'])->prefix('employee')->name('employee.')->group(function () {
    Route::get('/workspace', [EmployeePortalController::class, 'workspace'])->name('workspace');
    Route::get('/attendance', [EmployeePortalController::class, 'attendance'])->name('attendance');
    Route::get('/leave-requests', [EmployeePortalController::class, 'leaves'])->name('leaves');
    Route::get('/permission-requests', [EmployeePortalController::class, 'permissions'])->name('permissions');
    Route::get('/modules/{module}', function (string $module) {
        $modules = [
            'leads' => 'Leads',
            'bookings' => 'Bookings',
            'reports' => 'Reports',
        ];

        abort_unless(isset($modules[$module]), 404);

        return view('admin.pending-module', ['module' => $modules[$module]]);
    })->whereIn('module', ['leads', 'bookings', 'reports'])->name('module');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/admin/pending/{module}', function (string $module) {
        $modules = [
            'leads' => 'Leads',
            'bookings' => 'Bookings',
            'properties' => 'Properties',
            'reports' => 'Reports',
            'settings' => 'Settings',
        ];

        abort_unless(isset($modules[$module]), 404);

        return view('admin.pending-module', ['module' => $modules[$module]]);
    })->whereIn('module', ['leads', 'bookings', 'properties', 'reports', 'settings'])
        ->name('admin.pending');

    Route::get('/admin/password-recovery-codes', [PasswordRecoveryCodeController::class, 'index'])->name('admin.password-recovery-codes.index');
    Route::post('/admin/password-recovery-codes', [PasswordRecoveryCodeController::class, 'store'])->name('admin.password-recovery-codes.store');

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
    Route::get('/leave-requests/{leaveRequest}/edit', [LeaveRequestController::class, 'edit'])->name('leaves.edit');
    Route::put('/leave-requests/{leaveRequest}', [LeaveRequestController::class, 'update'])->name('leaves.update');
    Route::patch('/leave-requests/{leaveRequest}/status', [LeaveRequestController::class, 'status'])->name('leaves.status');
    Route::delete('/leave-requests/{leaveRequest}', [LeaveRequestController::class, 'destroy'])->name('leaves.destroy');

    Route::get('/permission-requests', [PermissionRequestController::class, 'index'])->name('permissions.index');
    Route::get('/permission-requests/create', [PermissionRequestController::class, 'create'])->name('permissions.create');
    Route::post('/permission-requests', [PermissionRequestController::class, 'store'])->name('permissions.store');
    Route::get('/permission-requests/{permissionRequest}/edit', [PermissionRequestController::class, 'edit'])->name('permissions.edit');
    Route::put('/permission-requests/{permissionRequest}', [PermissionRequestController::class, 'update'])->name('permissions.update');
    Route::patch('/permission-requests/{permissionRequest}/status', [PermissionRequestController::class, 'status'])->name('permissions.status');
    Route::delete('/permission-requests/{permissionRequest}', [PermissionRequestController::class, 'destroy'])->name('permissions.destroy');

    Route::get('/petty-cash', [PettyCashController::class, 'index'])->name('petty-cash.index');
    Route::get('/petty-cash/create', [PettyCashController::class, 'create'])->name('petty-cash.create');
    Route::post('/petty-cash', [PettyCashController::class, 'store'])->name('petty-cash.store');
    Route::get('/petty-cash/{pettyCash}/edit', [PettyCashController::class, 'edit'])->name('petty-cash.edit');
    Route::put('/petty-cash/{pettyCash}', [PettyCashController::class, 'update'])->name('petty-cash.update');
    Route::delete('/petty-cash/{pettyCash}', [PettyCashController::class, 'destroy'])->name('petty-cash.destroy');
});
