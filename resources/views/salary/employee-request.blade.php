@extends('layouts.app')
@section('title', 'Submit Request')
@section('heading', 'Submit Request')
@section('content')
<form class="max-w-2xl space-y-5 rounded-xl border bg-white p-6" method="POST" action="{{ route('employee.requests.store',['kind'=>$kind]) }}">@csrf
<h2 class="font-bold">{{ $kind === 'leave' ? 'Casual Leave' : 'Emergency permission' }} · {{ $employee->name }}</h2>
@if($kind === 'leave')
<label class="block">From<input class="field" type="date" name="start_date" value="{{ old('start_date') }}" required></label><label class="block">To<input class="field" type="date" name="end_date" value="{{ old('end_date') }}" required></label>
@else
<label class="block">Date<input class="field" type="date" name="permission_date" value="{{ old('permission_date') }}" required></label><label class="block">From<input class="field" type="time" name="start_time" value="{{ old('start_time','10:00') }}" required></label><label class="block">To<input class="field" type="time" name="end_time" value="{{ old('end_time') }}" required></label>
@endif
<label class="block">Reason<textarea class="field" name="reason" maxlength="1000" required>{{ old('reason') }}</textarea></label><button class="btn btn-primary">Submit for approval</button>
</form>
@endsection
