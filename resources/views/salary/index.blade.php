@extends('layouts.app')
@section('title', 'Employee Salary Management')
@section('heading', 'Employee Salary Management')
@section('breadcrumb', 'Salary')
@section('content')
<div class="page-intro flex flex-wrap items-center justify-between gap-4">
    <div><h2 class="text-xl font-bold">Monthly payroll</h2><p class="mt-1 text-sm">Configure salary rules, review attendance and finalize monthly statements.</p></div>
    <form method="GET" class="flex gap-2"><input aria-label="Salary month" class="field" type="month" name="month" value="{{ $month }}" required><button class="btn btn-primary">View</button></form>
</div>
<div class="flex flex-wrap gap-2"><a class="btn btn-light" href="{{ route('attendance.index') }}">Manage attendance / Week Off</a><a class="btn btn-light" href="{{ route('permissions.index') }}">Review permissions</a><a class="btn btn-light" href="{{ route('leaves.index') }}">Review leave</a></div>
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
@foreach($metrics as $label => $value)<div class="rounded-xl border bg-white p-4"><p class="text-xs text-slate-500">{{ $label }}</p><p class="mt-2 text-2xl font-bold">{{ $value }}</p></div>@endforeach
@foreach(['Total Monthly Salary' => 'earned_basic', 'Total Salary Deductions' => 'total_deductions', 'Total Payable Salary' => 'payable_salary'] as $label => $key)
<div class="rounded-xl border bg-white p-4"><p class="text-xs text-slate-500">{{ $label }} (INR)</p><p class="mt-2 text-2xl font-bold">{{ number_format(collect($rows)->sum(fn($r) => $r['snapshot'][$key] ?? 0) / 100, 2) }}</p></div>
@endforeach
</div>
<p class="text-sm text-slate-600">Draft figures are previews. Missing attendance and conflicting leave must be resolved before finalization. Salary rules carry forward from their effective month. Totals exclude employees without an active configuration.</p>
<section class="overflow-hidden rounded-2xl border bg-white"><div class="overflow-x-auto"><table class="w-full min-w-[1900px] text-left text-sm"><thead class="bg-slate-50"><tr>
@foreach(['Employee / ID','Basic (INR)','Sunday incentive','Attendance days','Sundays','Late arrivals','Permission used','Permission balance','CL used / balance','Week Off','Absent','Deductions','Payable','Status / Payment','Actions'] as $heading)<th class="p-3">{{ $heading }}</th>@endforeach
</tr></thead><tbody class="divide-y">
@forelse($rows as $row)
@php($s = $row['snapshot'])
<tr><td class="p-3"><strong>{{ $row['employee']->name }}</strong><br>{{ $row['employee']->employee_code }}</td>
@if($s)
<td class="p-3">{{ number_format($s['basic_salary']/100,2) }}</td><td class="p-3">{{ number_format($s['sunday_incentive']/100,2) }}</td><td class="p-3">{{ $s['present_days'] }}</td><td class="p-3">{{ $s['sunday_attendance'] }}/{{ $s['sundays'] }}</td><td class="p-3">{{ $s['late_arrivals'] }}</td><td class="p-3">{{ $s['permission_used'] }} min</td><td class="p-3">{{ $s['permission_balance'] }} min</td><td class="p-3">{{ $s['cl_used'] }} / {{ $s['cl_balance'] }}</td><td class="p-3">{{ $s['week_off_days'] }}</td><td class="p-3">{{ $s['absent_days'] }}</td><td class="p-3">{{ number_format($s['total_deductions']/100,2) }}</td><td class="p-3 font-bold">{{ number_format($s['payable_salary']/100,2) }}</td>
@else<td colspan="12" class="p-3 text-amber-800">{{ $row['error'] }}</td>@endif
<td class="p-3">{{ $row['statement']?->status ?? 'Preview' }} / {{ $row['statement']?->payment_status ?? 'Pending' }}@if($s && $s['missing_days'])<p class="text-amber-800">{{ $s['missing_days'] }} missing days</p>@endif</td>
<td class="space-y-2 p-3"><a class="btn btn-light" href="{{ route('salary.configure', [$row['employee'], 'month' => $month]) }}">Edit salary configuration</a>
@if($s)<form method="POST" action="{{ route('salary.generate', $row['employee']) }}">@csrf<input type="hidden" name="month" value="{{ $month }}"><button class="btn btn-primary">{{ $row['statement']?->status === 'Finalized' ? 'View statement' : 'Generate / refresh statement' }}</button></form>@endif
</td></tr>
@empty<tr><td colspan="15" class="p-6">No employees found.</td></tr>@endforelse
</tbody></table></div></section>
@endsection
