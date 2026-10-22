<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function permitted(User $user, string $permission): bool
    {
        return in_array($permission, config('leads.permissions.'.$user->role, []), true);
    }

    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'owner', 'employee'], true);
    }

    public function view(User $user, Lead $lead): bool
    {
        return Lead::visibleTo($user)->whereKey($lead->id)->exists();
    }

    public function create(User $user): bool
    {
        return $this->permitted($user, 'create');
    }

    public function import(User $user): bool
    {
        return $this->permitted($user, 'import');
    }

    public function history(User $user): bool
    {
        return $this->permitted($user, 'history');
    }

    public function export(User $user): bool
    {
        return $this->permitted($user, 'export');
    }

    public function update(User $user, Lead $lead): bool
    {
        return $this->view($user, $lead) && $this->permitted($user, 'edit');
    }

    public function assign(User $user, Lead $lead): bool
    {
        return $this->view($user, $lead) && $this->permitted($user, 'assign');
    }

    public function status(User $user, Lead $lead): bool
    {
        return $this->view($user, $lead) && $this->permitted($user, 'status');
    }

    public function followup(User $user, Lead $lead): bool
    {
        return $this->view($user, $lead) && $this->permitted($user, 'followup');
    }

    public function viewMobile(User $user, Lead $lead): bool
    {
        return $this->permitted($user, 'mobile') && $this->view($user, $lead);
    }
}
