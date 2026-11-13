@extends('layouts.app')
@section('title','Employee workspace')
@section('breadcrumb','Dashboard')
@section('heading','Good day, '.auth()->user()->name)
@section('content')
<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft">
    <h2 class="text-xl font-bold text-navy-950">Employee workspace</h2>
    <p class="mt-1 text-sm text-slate-500">You can view your own attendance, leave requests and permission requests.</p>
    @if(!$employee)
        <p class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Your login account is not linked to an employee record. Ask an administrator to make sure the email addresses match.</p>
    @else
        <dl class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-xl bg-slate-50 p-4"><dt class="text-sm text-slate-500">Employee ID</dt><dd class="mt-1 font-bold text-navy-950">{{ $employee->employee_code }}</dd></div>
            <div class="rounded-xl bg-slate-50 p-4"><dt class="text-sm text-slate-500">Department</dt><dd class="mt-1 font-bold text-navy-950">{{ $employee->department }}</dd></div>
            <div class="rounded-xl bg-slate-50 p-4"><dt class="text-sm text-slate-500">Designation</dt><dd class="mt-1 font-bold text-navy-950">{{ $employee->designation }}</dd></div>
        </dl>
    @endif
</section>
<div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
    @foreach([
        ['My attendance', $attendanceCount, route('employee.attendance')],
        ['My leave requests', $leaveCount, route('employee.leaves')],
        ['My permission requests', $permissionCount, route('employee.permissions')],
    ] as [$label, $count, $url])
        <a href="{{ $url }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-soft transition hover:border-emerald-700">
            <p class="text-sm text-slate-500">{{ $label }}</p>
            <p class="mt-2 text-3xl font-bold text-navy-950">{{ $count }}</p>
            <p class="mt-1 text-xs text-slate-400">View your records</p>
        </a>
    @endforeach
</div>
@endsection
