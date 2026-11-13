<?php

namespace App\Http\Controllers;

use App\Http\Requests\Leads\FollowupRequest;
use App\Http\Requests\Leads\LeadFilterRequest;
use App\Http\Requests\Leads\SaveLeadRequest;
use App\Models\Employee;
use App\Models\Lead;
use App\Models\SiteVisit;
use App\Services\Leads\LeadActions;
use App\Services\Leads\LeadData;
use App\Services\Leads\LeadQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Carbon;

class LeadController extends Controller
{
    public function dashboard(Request $request)
    {
        Gate::authorize('viewAny', Lead::class);
        $base = Lead::visibleTo($request->user());
        LeadActions::audit($request->user(), 'dashboard_viewed');

        $currentMonth = Carbon::now(config('app.timezone'))->startOfMonth();
        $months = collect(range(5, 0))->map(fn (int $monthsAgo) => $currentMonth->copy()->subMonths($monthsAgo));
        $siteVisitCounts = $this->monthlyCounts(
            SiteVisit::query()->whereHas('lead', fn (Builder $query) => $query->visibleTo($request->user())),
            'visit_date',
            $months
        );
        $bookingCounts = $this->monthlyCounts((clone $base), 'booked_at', $months);
        $monthlyActivity = $months->values()->map(fn (Carbon $month, int $index) => [
            'label' => $month->format('M Y'),
            'site_visits' => (int) ($siteVisitCounts->{'month_'.$index} ?? 0),
            'booked_clients' => (int) ($bookingCounts->{'month_'.$index} ?? 0),
        ]);
        $siteVisitsTotal = (int) $monthlyActivity->sum('site_visits');
        $bookedClientsTotal = (int) $monthlyActivity->sum('booked_clients');
        $chartMax = max(1, (int) $monthlyActivity->max(fn (array $month) => max($month['site_visits'], $month['booked_clients'])));

        return view('leads.dashboard', [
            'total' => (clone $base)->count(),
            'wanted' => (clone $base)->where('status', 'Active')->count(),
            'unwanted' => (clone $base)->where('status', 'Inactive')->count(),
            'recent' => (clone $base)->with(['employee', 'lastFollowup'])->latest()->limit(5)->get(),
            'monthlyActivity' => $monthlyActivity,
            'siteVisitsTotal' => $siteVisitsTotal,
            'bookedClientsTotal' => $bookedClientsTotal,
            'chartMax' => $chartMax,
        ]);
    }

    private function monthlyCounts(Builder $query, string $column, Collection $months): object
    {
        $selects = [];
        $bindings = [];

        foreach ($months as $index => $month) {
            $nextMonth = $month->copy()->addMonth();
            $selects[] = "SUM(CASE WHEN {$column} >= ? AND {$column} < ? THEN 1 ELSE 0 END) AS month_{$index}";
            $bindings[] = $month->toDateString();
            $bindings[] = $nextMonth->toDateString();
        }

        return $query
            ->where($column, '>=', $months->first()->toDateString())
            ->where($column, '<', $months->last()->copy()->addMonth()->toDateString())
            ->selectRaw(implode(', ', $selects), $bindings)
            ->first();
    }

    public function index(LeadFilterRequest $request, LeadQuery $query)
    {
        $filters = $request->validated();
        LeadActions::audit($request->user(), 'list_viewed');

        return view('leads.index', [
            'leads' => $query->build($request->user(), $filters)->paginate(10)->withQueryString(),
            'filters' => $filters,
            'employees' => Employee::query()->when($request->user()->role === 'employee', fn ($q) => $q->where('email', $request->user()->email)->where('employment_status', 'Active'))->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        Gate::authorize('create', Lead::class);

        return view('leads.form', ['lead' => new Lead, 'editing' => false]);
    }

    public function store(SaveLeadRequest $request, LeadActions $actions)
    {
        try {
            $lead = $actions->create($request->user(), $request->safe()->only(array_keys(LeadData::rules())));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['mobile_number' => 'This mobile number already belongs to a lead.']);
        }

        return redirect()->route('leads.show', $lead)->with('success', 'Lead created.');
    }

    public function show(Request $request, Lead $lead)
    {
        Gate::authorize('view', $lead);
        LeadActions::audit($request->user(), 'viewed', $lead);

        return view('leads.show', [
            'lead' => $lead->load(['employee', 'creator', 'lastFollowup', 'bookedBy']),
            'followups' => $lead->followups()->with('creator')->orderBy('followup_at')->orderBy('id')->paginate(15, ['*'], 'followup_page')->withQueryString(),
            'siteVisits' => $lead->siteVisits()->with('creator')->orderByDesc('visit_date')->orderByDesc('id')->paginate(10, ['*'], 'site_visit_page')->withQueryString(),
            'assignments' => $lead->assignments()->with(['previousEmployee', 'newEmployee', 'actor'])->latest('id')->paginate(10, ['*'], 'assignment_page')->withQueryString(),
            'audits' => $lead->audits()->with('actor')->latest('id')->paginate(10, ['*'], 'audit_page')->withQueryString(),
        ]);
    }

    public function followupHistory(Request $request, Lead $lead)
    {
        Gate::authorize('view', $lead);
        LeadActions::audit($request->user(), 'followups_viewed', $lead);

        return view('leads.followups.index', [
            'lead' => $lead->load('employee'),
            'requirementEntries' => LeadData::datedRequirementEntries($lead->requirement ?? ''),
            'followups' => $lead->followups()->with('creator')->latest('followup_at')->latest('id')->paginate(20)->withQueryString(),
        ]);
    }

    public function edit(Lead $lead)
    {
        Gate::authorize('update', $lead);

        return view('leads.form', ['lead' => $lead, 'editing' => true]);
    }

    public function update(SaveLeadRequest $request, Lead $lead)
    {
        try {
            DB::transaction(function () use ($request, $lead) {
                $locked = Lead::whereKey($lead->id)->lockForUpdate()->firstOrFail();
                Gate::authorize('update', $locked);
                $data = $request->safe()->only($request->user()->role === 'employee' ? ['location', 'requirement', 'project_category'] : array_keys(LeadData::rules()));
                if (isset($data['mobile_number'])) {
                    $data['normalized_mobile'] = $data['mobile_number'];
                }
                $locked->update($data);
                LeadActions::audit($request->user(), 'updated', $locked, ['fields' => array_keys($data)]);
            }, 3);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['mobile_number' => 'This mobile number already belongs to a lead.']);
        }

        return redirect()->route('leads.show', $lead)->with('success', 'Lead updated.');
    }

    public function assignment(Lead $lead)
    {
        Gate::authorize('assign', $lead);
        LeadActions::audit(request()->user(), 'assignment_viewed', $lead);

        return view('leads.assign', ['lead' => $lead->load('employee'), 'employees' => LeadData::eligibleEmployees()->orderBy('name')->get()]);
    }

    public function assign(Request $request, Lead $lead, LeadActions $actions)
    {
        Gate::authorize('assign', $lead);
        $data = $request->validate(['assigned_to' => LeadData::assignmentRules(), 'reason' => ['nullable', 'string', 'max:1000']]);
        $actions->assign($request->user(), $lead, isset($data['assigned_to']) ? (int) $data['assigned_to'] : null, $data['reason'] ?? null);

        return redirect()->route('leads.show', $lead)->with('success', 'Assignment saved.');
    }

    public function status(Request $request, Lead $lead)
    {
        Gate::authorize('status', $lead);
        $data = $request->validate(['status' => ['required', Rule::in(['Active', 'Inactive'])], 'reason' => ['required', 'string', 'max:1000']]);
        DB::transaction(function () use ($request, $lead, $data) {
            $locked = Lead::whereKey($lead->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('status', $locked);
            $previous = $locked->status;
            if ($previous === $data['status']) {
                return;
            }
            $locked->update(['status' => $data['status']]);
            LeadActions::audit($request->user(), 'status_changed', $locked, ['from' => $previous, 'to' => $data['status'], 'reason' => $data['reason']]);
        }, 3);

        return redirect()->route('leads.show', $lead)->with('success', 'Lead status saved.');
    }

    public function followup(Lead $lead)
    {
        Gate::authorize('followup', $lead);

        return view('leads.followup', compact('lead'));
    }

    public function storeFollowup(FollowupRequest $request, Lead $lead)
    {
        DB::transaction(function () use ($request, $lead) {
            $locked = Lead::whereKey($lead->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('followup', $locked);
            $followup = $locked->followups()->create(array_merge($request->safe()->only(['outcome', 'remarks', 'followup_at', 'next_followup_at']), ['created_by' => $request->user()->id]));
            LeadActions::audit($request->user(), 'followup_added', $locked, ['followup_id' => $followup->id, 'outcome' => $followup->outcome]);
        }, 3);

        return redirect()->route('leads.show', $lead)->with('success', 'Follow-up added to history.');
    }

    public function export(LeadFilterRequest $request, LeadQuery $query)
    {
        Gate::authorize('export', Lead::class);
        $filters = $request->validated();
        $count = $query->build($request->user(), $filters)->count();
        abort_if($count > 10000, 422, 'Narrow the filters to export at most 10,000 leads.');
        LeadActions::audit($request->user(), 'exported', null, ['count' => $count]);

        return response()->streamDownload(function () use ($request, $query, $filters) {
            $output = fopen('php://output', 'wb');
            fputcsv($output, ['Lead Name', 'Mobile Number', 'Project Category', 'Location', 'Requirement', 'Assigned Employee', 'Status'], ',', '"', '');
            foreach ($query->build($request->user(), $filters)->lazy(200) as $lead) {
                fputcsv($output, array_map([LeadData::class, 'safeCsv'], [$lead->lead_name, $lead->displayMobile($request->user()), $lead->project_category, $lead->location, $lead->requirement, $lead->employee?->name ?? '', $lead->status]), ',', '"', '');
            }
            fclose($output);
        }, 'abj-leads.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
