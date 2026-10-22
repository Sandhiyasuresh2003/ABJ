@extends('layouts.app')
@section('title','Add follow-up')
@section('heading','Add follow-up')
@section('breadcrumb','Leads')
@section('content')
@include('leads.nav')
<form method="POST" action="{{ route('leads.followups.store',$lead) }}" class="max-w-3xl space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-soft">
    @csrf <p class="font-semibold">{{ $lead->lead_name }} · {{ $lead->displayMobile(auth()->user()) }}</p>
    <p class="text-sm text-slate-500">Follow-ups are added to permanent history. Times use {{ config('app.timezone') }}. Lead status stays unchanged.</p>
    <div><label for="followup-at" class="mb-1 block text-sm font-semibold">Follow-up date and time *</label><input id="followup-at" class="field" type="datetime-local" step="1" name="followup_at" min="{{ $lead->created_at->format('Y-m-d\TH:i:s') }}" max="{{ now()->format('Y-m-d\TH:i:s') }}" value="{{ old('followup_at',now()->format('Y-m-d\TH:i:s')) }}" required></div>
    <div><label for="followup-outcome" class="mb-1 block text-sm font-semibold">Outcome *</label><select id="followup-outcome" class="field" name="outcome" required>@foreach(App\Models\LeadFollowup::OUTCOMES as $outcome)<option @selected(old('outcome')===$outcome)>{{ $outcome }}</option>@endforeach</select><p class="mt-1 text-xs text-slate-500">RNR means Ringing No Response.</p></div>
    <div><label for="next-followup" class="mb-1 block text-sm font-semibold">Next follow-up date and time</label><input id="next-followup" class="field" type="datetime-local" name="next_followup_at" value="{{ old('next_followup_at') }}"><p class="mt-1 text-xs text-slate-500">Required for Another Call. Must be in the future and after this follow-up.</p></div>
    <div><label for="remarks" class="mb-1 block text-sm font-semibold">Notes / remarks</label><textarea id="remarks" class="field" rows="4" name="remarks" maxlength="3000">{{ old('remarks') }}</textarea><p class="mt-1 text-xs text-slate-500">Required for Others.</p></div>
    <button class="btn btn-primary">Add follow-up</button><a class="btn btn-light" href="{{ route('leads.show',$lead) }}">Cancel</a>
</form>
@endsection
