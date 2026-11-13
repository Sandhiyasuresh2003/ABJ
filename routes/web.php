<?php

use App\Http\Controllers\Admin\PasswordRecoveryCodeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\AccountPasswordSetupController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PropertyDocumentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeePortalController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\PermissionRequestController;
use App\Http\Controllers\PettyCashController;
use App\Http\Controllers\RequestReviewController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeadActivityController;
use App\Http\Controllers\LeadImportController;
use App\Http\Controllers\PublicHomeController;

Route::middleware(['auth', \App\Http\Middleware\LeadSecurityHeaders::class])->prefix('leads')->name('leads.')->group(function () {
    Route::get('/dashboard', [LeadController::class, 'dashboard'])->name('dashboard');
    Route::get('/', [LeadController::class, 'index'])->name('index');
    Route::get('/create', [LeadController::class, 'create'])->name('create');
    Route::post('/', [LeadController::class, 'store'])->middleware('throttle:30,1')->name('store');
    Route::get('/export', [LeadController::class, 'export'])->middleware('throttle:6,1')->name('export');
    Route::get('/imports', [LeadImportController::class, 'index'])->name('imports.index');
    Route::get('/imports/create', [LeadImportController::class, 'create'])->name('imports.create');
    Route::get('/imports/template', [LeadImportController::class, 'template'])->name('imports.template');
    Route::post('/imports', [LeadImportController::class, 'store'])->middleware('throttle:6,1')->name('imports.store');
    Route::get('/imports/{import}', [LeadImportController::class, 'show'])->whereNumber('import')->name('imports.show');
    Route::post('/imports/{import}/preview', [LeadImportController::class, 'preview'])->whereNumber('import')->middleware('throttle:10,1')->name('imports.preview');
    Route::post('/imports/{import}/confirm', [LeadImportController::class, 'confirm'])->whereNumber('import')->middleware('throttle:6,1')->name('imports.confirm');
    Route::get('/{lead}/followups/history', [LeadController::class, 'followupHistory'])->whereNumber('lead')->name('followups.history');
    Route::get('/{lead}', [LeadController::class, 'show'])->whereNumber('lead')->name('show');
    Route::get('/{lead}/edit', [LeadController::class, 'edit'])->whereNumber('lead')->name('edit');
    Route::put('/{lead}', [LeadController::class, 'update'])->whereNumber('lead')->name('update');
    Route::get('/{lead}/assign', [LeadController::class, 'assignment'])->whereNumber('lead')->name('assignment');
    Route::post('/{lead}/assign', [LeadController::class, 'assign'])->whereNumber('lead')->name('assign');
    Route::patch('/{lead}/status', [LeadController::class, 'status'])->whereNumber('lead')->name('status');
    Route::post('/{lead}/site-visits', [LeadActivityController::class, 'storeSiteVisit'])->whereNumber('lead')->name('site-visits.store');
    Route::post('/{lead}/book', [LeadActivityController::class, 'book'])->whereNumber('lead')->name('book');
    Route::get('/{lead}/followups/create', [LeadController::class, 'followup'])->whereNumber('lead')->name('followup');
    Route::post('/{lead}/followups', [LeadController::class, 'storeFollowup'])->whereNumber('lead')->middleware('throttle:30,1')->name('followups.store');
});

Route::redirect('/agropro', '/login')->name('agropro.home');
Route::get('/agropro/{page}.html', fn () => redirect()->route('login'))
    ->where('page', '[^/]+')
    ->name('agropro.page');

Route::match(['get', 'post'], '/request-review/{kind}/{id}/{decision}', RequestReviewController::class)
    ->whereIn('kind', ['leave', 'permission'])
    ->whereNumber('id')
    ->whereIn('decision', ['approve', 'reject'])
    ->middleware(['signed', 'throttle:30,1'])
    ->name('requests.review');

Route::get('/', [PublicHomeController::class, 'index'])->name('home');
Route::get('/latest-news/{latestNews}', [\App\Http\Controllers\PublicLatestNewsController::class, 'show'])
    ->whereNumber('latestNews')
    ->name('latest-news.show');
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1')->name('login.submit');

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
    Route::get('/requests/{kind}/create', [\App\Http\Controllers\EmployeeSalaryRequestController::class, 'create'])->whereIn('kind', ['leave', 'permission'])->name('requests.create');
    Route::post('/requests/{kind}', [\App\Http\Controllers\EmployeeSalaryRequestController::class, 'store'])->whereIn('kind', ['leave', 'permission'])->middleware('throttle:10,1')->name('requests.store');
    Route::get('/workspace', [EmployeePortalController::class, 'workspace'])->name('workspace');
    Route::get('/attendance', [EmployeePortalController::class, 'attendance'])->name('attendance');
    Route::get('/leave-requests', [EmployeePortalController::class, 'leaves'])->name('leaves');
    Route::get('/permission-requests', [EmployeePortalController::class, 'permissions'])->name('permissions');
    Route::get('/modules/{module}', function (string $module) {
        if ($module === 'leads') {
            return redirect()->route('leads.dashboard');
        }
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
    Route::get('/admin/about-us', [\App\Http\Controllers\Admin\AboutUsPageController::class, 'edit'])->name('admin.about-us.edit');
    Route::put('/admin/about-us', [\App\Http\Controllers\Admin\AboutUsPageController::class, 'update'])->name('admin.about-us.update');
    Route::get('/admin/banners', [\App\Http\Controllers\Admin\BannerImageController::class, 'index'])->name('admin.banners.index');
    Route::post('/admin/banners', [\App\Http\Controllers\Admin\BannerImageController::class, 'store'])->name('admin.banners.store');
    Route::delete('/admin/banners/{banner}', [\App\Http\Controllers\Admin\BannerImageController::class, 'destroy'])->name('admin.banners.destroy');
    Route::get('/admin/services', [\App\Http\Controllers\Admin\ServiceController::class, 'index'])->name('admin.services.index');
    Route::post('/admin/services', [\App\Http\Controllers\Admin\ServiceController::class, 'store'])->name('admin.services.store');
    Route::put('/admin/services/{service}', [\App\Http\Controllers\Admin\ServiceController::class, 'update'])->name('admin.services.update');
    Route::delete('/admin/services/{service}', [\App\Http\Controllers\Admin\ServiceController::class, 'destroy'])->name('admin.services.destroy');
    Route::get('/admin/client-feedback', [\App\Http\Controllers\Admin\ClientTestimonialController::class, 'index'])->name('admin.client-feedback.index');
    Route::post('/admin/client-feedback', [\App\Http\Controllers\Admin\ClientTestimonialController::class, 'store'])->name('admin.client-feedback.store');
    Route::put('/admin/client-feedback/{testimonial}', [\App\Http\Controllers\Admin\ClientTestimonialController::class, 'update'])->name('admin.client-feedback.update');
    Route::delete('/admin/client-feedback/{testimonial}', [\App\Http\Controllers\Admin\ClientTestimonialController::class, 'destroy'])->name('admin.client-feedback.destroy');
    Route::get('/admin/latest-news', [\App\Http\Controllers\Admin\LatestNewsController::class, 'index'])->name('admin.latest-news.index');
    Route::post('/admin/latest-news', [\App\Http\Controllers\Admin\LatestNewsController::class, 'store'])->name('admin.latest-news.store');
    Route::put('/admin/latest-news/{latestNews}', [\App\Http\Controllers\Admin\LatestNewsController::class, 'update'])->name('admin.latest-news.update');
    Route::delete('/admin/latest-news/{latestNews}/gallery/{galleryImage}', [\App\Http\Controllers\Admin\LatestNewsController::class, 'destroyGalleryImage'])->name('admin.latest-news.gallery.destroy');
    Route::delete('/admin/latest-news/{latestNews}', [\App\Http\Controllers\Admin\LatestNewsController::class, 'destroy'])->name('admin.latest-news.destroy');

    Route::prefix('employee-salary')->name('salary.')->group(function () {
        Route::get('/', [\App\Http\Controllers\SalaryController::class, 'index'])->name('index');
        Route::get('/employees/{employee}/configuration', [\App\Http\Controllers\SalaryController::class, 'configure'])->name('configure');
        Route::post('/employees/{employee}/configuration', [\App\Http\Controllers\SalaryController::class, 'saveConfiguration'])->name('configuration.save');
        Route::post('/employees/{employee}/generate', [\App\Http\Controllers\SalaryController::class, 'generate'])->name('generate');
        Route::get('/statements/{statement}', [\App\Http\Controllers\SalaryController::class, 'show'])->name('show');
        Route::post('/statements/{statement}/{action}', [\App\Http\Controllers\SalaryController::class, 'change'])->whereIn('action', ['finalize', 'revise', 'adjust', 'payment'])->name('change');
        Route::get('/statements/{statement}/export/{format}', [\App\Http\Controllers\SalaryController::class, 'export'])->whereIn('format', ['xlsx', 'pdf'])->middleware('throttle:10,1')->name('export');
    });
    Route::get('/documents', [PropertyDocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [PropertyDocumentController::class, 'store'])->middleware('throttle:20,1')->name('documents.store');
    Route::get('/documents/{document}/download', [PropertyDocumentController::class, 'download'])->whereNumber('document')->name('documents.download');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/admin/pending/{module}', function (string $module) {
        if ($module === 'leads') {
            return redirect()->route('leads.dashboard');
        }
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
