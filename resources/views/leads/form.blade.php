@extends('layouts.app')
@section('title',$editing?'Edit lead':'New lead')
@section('heading',$editing?'Edit lead':'New lead')
@section('breadcrumb','Leads')
@section('content')
@include('leads.nav')
<form method="POST" action="{{ $editing?route('leads.update',$lead):route('leads.store') }}" class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-soft">
    @csrf @if($editing) @method('PUT') @endif
    @if(!$editing || auth()->user()->role!=='employee')
    <div class="mb-5"><label for="lead-name" class="mb-1 block text-sm font-semibold">Lead name *</label><input id="lead-name" class="field" name="lead_name" maxlength="150" value="{{ old('lead_name',$lead->lead_name) }}" required></div>
    <div class="mb-5"><label for="mobile" class="mb-1 block text-sm font-semibold">Mobile number *</label><input id="mobile" class="field" type="tel" name="mobile_number" maxlength="25" value="{{ old('mobile_number',$lead->mobile_number) }}" required><p class="mt-1 text-xs text-slate-500">Indian mobile number; +91, spaces and separators are accepted.</p></div>
    @else<p class="mb-5 font-semibold">{{ $lead->lead_name }} · {{ $lead->displayMobile(auth()->user()) }}</p>@endif
    <div class="mb-5"><label for="lead-location" class="mb-1 block text-sm font-semibold">Location *</label><input id="lead-location" class="field" name="location" maxlength="150" value="{{ old('location',$lead->location) }}" required></div>
    <div class="mb-5"><label for="requirement" class="mb-1 block text-sm font-semibold">Requirement *</label><textarea id="requirement" class="field" rows="4" name="requirement" maxlength="1000" required>{{ old('requirement',$lead->requirement) }}</textarea></div>
    <div class="flex gap-3"><button class="btn btn-primary">Save lead</button><a class="btn btn-light" href="{{ $editing?route('leads.show',$lead):route('leads.index') }}">Cancel</a></div>
</form>
@endsection
