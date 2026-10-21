@extends('layouts.app')@section('title',$kind==='leave'?'New leave request':'New permission request')@section('breadcrumb',$kind==='leave'?'Leave requests':'Permissions')@section('heading',$kind==='leave'?'New leave request':'New permission request')
@section('content')
@php $isLeave=$kind==='leave'; @endphp
<div class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-5 shadow-soft sm:p-8"><p class="mb-5 text-sm text-slate-500">New requests are saved with Pending status and can be reviewed by an administrator.</p><form method="POST" action="{{ route($isLeave?'leaves.store':'permissions.store') }}">@csrf
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2"><div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-semibold">Employee *</label><select class="field" name="employee_id" required><option value="">Select employee</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected(old('employee_id')==$employee->id)>{{ $employee->employee_code }} — {{ $employee->name }}</option>@endforeach</select></div>
@if($isLeave)
<div><label class="mb-1.5 block text-sm font-semibold">Leave type *</label><select class="field" name="leave_type" required>@foreach(['Casual Leave','Sick Leave','Earned Leave','Unpaid Leave','Other'] as $type)<option @selected(old('leave_type')===$type)>{{ $type }}</option>@endforeach</select></div>
<div></div>
<div><label class="mb-1.5 block text-sm font-semibold">Start date *</label><input class="field" type="date" name="start_date" value="{{ old('start_date') }}" required></div><div><label class="mb-1.5 block text-sm font-semibold">End date *</label><input class="field" type="date" name="end_date" value="{{ old('end_date') }}" required></div>
@else
<div><label class="mb-1.5 block text-sm font-semibold">Permission date *</label><input class="field" type="date" name="permission_date" value="{{ old('permission_date',now()->toDateString()) }}" required></div><div></div>
<div><label class="mb-1.5 block text-sm font-semibold">Start time *</label><input class="field" type="time" name="start_time" value="{{ old('start_time') }}" required></div><div><label class="mb-1.5 block text-sm font-semibold">End time *</label><input class="field" type="time" name="end_time" value="{{ old('end_time') }}" required></div>
@endif
<div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-semibold">Reason *</label><textarea class="field" rows="4" name="reason" required maxlength="1000">{{ old('reason') }}</textarea></div></div>
<div class="mt-6 flex justify-end gap-3"><a class="btn btn-light" href="{{ route($isLeave?'leaves.index':'permissions.index') }}">Cancel</a><button class="btn btn-primary">Submit request</button></div></form></div>
@endsection
