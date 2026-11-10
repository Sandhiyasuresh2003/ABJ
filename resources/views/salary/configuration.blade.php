@extends('layouts.app')
@section('title', 'Salary Configuration')
@section('heading', 'Salary Configuration')
@section('content')
<div class="page-intro"><h2 class="text-xl font-bold">{{ $employee->name }} · {{ $employee->employee_code }}</h2><p>Joining date: {{ $employee->joining_date->format('d M Y') }}. Rules apply from the selected month until the next configuration.</p></div>
<form id="salaryConfiguration" class="max-w-4xl rounded-2xl border bg-white p-6" method="POST" action="{{ route('salary.configuration.save', $employee) }}">@csrf
<div class="grid gap-5 sm:grid-cols-2">
<label class="text-sm font-semibold">Effective month<input class="field mt-2" type="month" name="month" value="{{ old('month',$month) }}" required></label>
@foreach(['basic_salary' => ['Monthly basic salary (INR)', 0, '0.01'], 'sunday_incentive' => ['Sunday incentive (INR)', 0, '0.01'], 'fixed_divisor' => ['Fixed salary divisor (required for fixed method)', null, '1'], 'working_minutes' => ['Working minutes per day',480,'1'], 'permission_minutes' => ['Monthly permission allowance (minutes)',120,'1'], 'late_charge_minutes' => ['Allowance used per unapproved late arrival (minutes)',30,'1'], 'cl_allowance' => ['Casual leave allowance (days)',0,'1']] as $name => [$label,$default,$step])
<label class="text-sm font-semibold">{{ $label }}<input class="field mt-2" type="number" min="{{ in_array($name,['fixed_divisor','working_minutes','late_charge_minutes']) ? 1 : 0 }}" step="{{ $step }}" name="{{ $name }}" value="{{ old($name,$configuration->$name ?? $default) }}" @required($name !== 'fixed_divisor')></label>
@endforeach
<label class="text-sm font-semibold">Salary divisor method<select class="field mt-2" name="divisor_method">@foreach(['calendar' => 'Calendar days in salary month', 'fixed' => 'Configured fixed divisor'] as $value => $label)<option value="{{ $value }}" @selected(old('divisor_method',$configuration->divisor_method) === $value)>{{ $label }}</option>@endforeach</select></label>
<label class="text-sm font-semibold">Reporting time<input class="field mt-2" type="time" name="reporting_time" value="{{ old('reporting_time',substr($configuration->reporting_time ?? '10:00',0,5)) }}" required></label>
<label class="text-sm font-semibold">CL allowance period<select class="field mt-2" name="cl_period">@foreach(['monthly','annual'] as $period)<option @selected(old('cl_period',$configuration->cl_period)===$period)>{{ $period }}</option>@endforeach</select></label>
<label class="text-sm font-semibold">Salary status<select class="field mt-2" name="status">@foreach(['Active','Inactive'] as $status)<option @selected(old('status',$configuration->status)===$status)>{{ $status }}</option>@endforeach</select></label>
<label class="text-sm font-semibold sm:col-span-2">Reason for configuration change<textarea class="field mt-2" name="reason" maxlength="1000" required>{{ old('reason') }}</textarea></label>
<div class="rounded-xl bg-slate-50 p-4"><p class="text-sm">Calculated daily salary (INR)</p><output id="dailySalary" class="font-bold" aria-live="polite">—</output></div>
<div class="rounded-xl bg-slate-50 p-4"><p class="text-sm">Calculated per-minute salary (INR)</p><output id="minuteSalary" class="font-bold" aria-live="polite">—</output></div>
</div><p class="my-4 text-sm text-slate-500">Daily salary = basic salary ÷ selected divisor. Per-minute salary = daily salary ÷ working minutes. Mid-month joining prorates basic by calendar days; Sunday incentive requires attendance on every Sunday of the full month. Sundays never consume the late allowance.</p>
<a class="btn btn-light" href="{{ route('salary.index',['month'=>$month]) }}">Back</a> <button class="btn btn-primary">Save configuration</button>
</form>
<script>
(() => {
    const form = document.getElementById('salaryConfiguration');
    function updateRates() {
        const data = new FormData(form);
        const [year, month] = String(data.get('month')).split('-').map(Number);
        const divisor = data.get('divisor_method') === 'calendar' ? new Date(year, month, 0).getDate() : Number(data.get('fixed_divisor'));
        const daily = divisor > 0 ? Number(data.get('basic_salary')) / divisor : NaN;
        const minutes = Number(data.get('working_minutes'));
        document.getElementById('dailySalary').textContent = Number.isFinite(daily) ? daily.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 6}) : 'Set a valid divisor';
        document.getElementById('minuteSalary').textContent = Number.isFinite(daily) && minutes > 0 ? (daily / minutes).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 6}) : 'Set working minutes';
    }
    form.addEventListener('input', updateRates);
    updateRates();
})();
</script>
@endsection
