<?php

namespace App\Services\Leads;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class LeadQuery
{
    public function build(User $user, array $filters): Builder
    {
        $query = Lead::visibleTo($user);
        if ($search = $filters['search'] ?? null) {
            $query->where(function (Builder $q) use ($search, $user) {
                $q->where('lead_name', 'like', '%'.$search.'%');
                // A masked user must not recover a phone number through a search oracle.
                if (in_array('mobile', config('leads.permissions.'.$user->role, []), true)) {
                    $q->orWhere('normalized_mobile', 'like', '%'.LeadData::normalizePhone($search).'%');
                }
            });
        }
        foreach (['status', 'assigned_to', 'location'] as $column) {
            if (isset($filters[$column]) && $filters[$column] !== '') {
                $query->where($column, $filters[$column]);
            }
        }
        if ($filters['from'] ?? null) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if ($filters['to'] ?? null) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }
        if ($filters['outcome'] ?? null) {
            $query->whereHas('lastFollowup', fn (Builder $q) => $q->where('outcome', $filters['outcome']));
        }
        if ($filters['next_followup'] ?? null) {
            $query->whereHas('lastFollowup', fn (Builder $q) => $q->whereDate('next_followup_at', $filters['next_followup']));
        }

        return $query->with(['employee', 'lastFollowup'])->orderBy($filters['sort'] ?? 'created_at', $filters['direction'] ?? 'desc')->orderBy('id', 'desc');
    }
}
