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
        if (($mobile = $filters['mobile'] ?? null) && in_array('mobile', config('leads.permissions.'.$user->role, []), true)) {
            $query->where('normalized_mobile', 'like', '%'.LeadData::normalizePhone($mobile).'%');
        }
        if (($filters['assigned_to'] ?? '') !== '') {
            $query->where('assigned_to', $filters['assigned_to']);
        }
        if (($filters['project_category'] ?? '') !== '') {
            $query->where('project_category', $filters['project_category']);
        }
        if ($text = $filters['text'] ?? null) {
            $query->where(function (Builder $q) use ($text) {
                $q->whereHas('lastFollowup', fn (Builder $followup) => $followup->where('outcome', 'like', '%'.$text.'%'))
                    ->orWhere('requirement', 'like', '%'.$text.'%');
            });
        }

        return $query->with(['employee', 'lastFollowup'])->orderBy('created_at', 'desc')->orderBy('id', 'desc');
    }
}
