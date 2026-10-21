<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PermissionRequest;
use App\Models\PettyCashTransaction;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = Carbon::today();
        $employees = Employee::where('employment_status', 'Active')->count();
        $present = Attendance::whereDate('attendance_date', $today)->whereIn('status', ['Present', 'Late'])->count();
        $late = Attendance::whereDate('attendance_date', $today)->where('status', 'Late')->count();
        $pendingLeaves = LeaveRequest::where('status', 'Pending')->count();
        $pendingPermissions = PermissionRequest::where('status', 'Pending')->count();

        $cashIn = (float) PettyCashTransaction::where('type', 'in')->sum('amount');
        $cashOut = (float) PettyCashTransaction::where('type', 'out')->sum('amount');
        $cashBalance = $cashIn - $cashOut;

        $recentAttendance = Attendance::with('employee')->whereDate('attendance_date', $today)
            ->latest('updated_at')->take(6)->get();
        $pendingLeaveItems = LeaveRequest::with('employee')->where('status', 'Pending')->latest()->take(4)->get();
        $pendingPermissionItems = PermissionRequest::with('employee')->where('status', 'Pending')->latest()->take(4)->get();
        $recentCash = PettyCashTransaction::latest('transaction_date')->latest('id')->take(5)->get();

        return view('dashboard.index', compact(
            'today', 'employees', 'present', 'late', 'pendingLeaves', 'pendingPermissions',
            'cashIn', 'cashOut', 'cashBalance', 'recentAttendance', 'pendingLeaveItems',
            'pendingPermissionItems', 'recentCash'
        ));
    }
}
