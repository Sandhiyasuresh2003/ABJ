@extends('layouts.app')
@section('title','Assign lead')
@section('heading','Assign lead')
@section('breadcrumb','Leads')
@section('content')
@include('leads.nav')
<form method="POST" action="{{ route('leads.assign',$lead) }}" class="max-w-3xl space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-soft" onsubmit="return confirm('Save this lead assignment? The previous employee will lose access.')">
    @csrf
    <p class="font-semibold">{{ $lead->lead_name }}</p><p class="text-sm text-slate-500">Currently assigned to {{ $lead->employee?->name??'no employee' }}.</p>
    <div><label for="assigned-to" class="mb-1 block text-sm font-semibold">Assign to</label><select id="assigned-to" class="field" name="assigned_to"><option value="">Unassigned</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected(old('assigned_to',$lead->assigned_to)==$employee->id)>{{ $employee->employee_code }} — {{ $employee->name }}</option>@endforeach</select></div>
    <div><label for="reason" class="mb-1 block text-sm font-semibold">Reason (optional)</label><textarea id="reason" class="field" rows="3" name="reason" maxlength="1000">{{ old('reason') }}</textarea></div>
    <button class="btn btn-primary">Save assignment</button><a class="btn btn-light" href="{{ route('leads.show',$lead) }}">Cancel</a>
</form>
@endsection
