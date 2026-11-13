<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Services\Leads\LeadActions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class LeadActivityController extends Controller
{
    public function storeSiteVisit(Request $request, Lead $lead)
    {
        Gate::authorize('siteVisit', $lead);
        $data = $request->validate([
            'visit_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.today()->toDateString(), 'after_or_equal:'.$lead->created_at->toDateString()],
            'details' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $lead, $data) {
            $locked = Lead::query()->whereKey($lead->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('siteVisit', $locked);
            $visit = $locked->siteVisits()->create([
                'visit_date' => $data['visit_date'],
                'details' => $data['details'] ?? null,
                'created_by' => $request->user()->id,
            ]);
            LeadActions::audit($request->user(), 'site_visit_recorded', $locked, [
                'site_visit_id' => $visit->id,
                'visit_date' => $visit->visit_date->toDateString(),
            ]);
        }, 3);

        return redirect()->route('leads.show', $lead)->with('success', 'Completed site visit recorded.');
    }

    public function book(Request $request, Lead $lead)
    {
        Gate::authorize('book', $lead);
        $data = $request->validate([
            'booked_at' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.today()->toDateString(), 'after_or_equal:'.$lead->created_at->toDateString()],
        ]);

        DB::transaction(function () use ($request, $lead, $data) {
            $locked = Lead::query()->whereKey($lead->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('book', $locked);
            if ($locked->booked_at) {
                throw ValidationException::withMessages(['booked_at' => 'This lead is already marked as booked.']);
            }

            $locked->update(['booked_at' => $data['booked_at'], 'booked_by' => $request->user()->id]);
            LeadActions::audit($request->user(), 'lead_booked', $locked, ['booked_at' => $data['booked_at']]);
        }, 3);

        return redirect()->route('leads.show', $lead)->with('success', 'Client marked as booked.');
    }
}
