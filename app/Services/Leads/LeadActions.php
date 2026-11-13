<?php

namespace App\Services\Leads;

use App\Models\Lead;
use App\Models\LeadAssignment;
use App\Models\LeadAudit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class LeadActions
{
    public static function audit(User $user, string $event, ?Lead $lead = null, array $metadata = []): void
    {
        LeadAudit::create(['user_id' => $user->id, 'lead_id' => $lead?->id, 'event' => $event, 'metadata' => $metadata]);
    }

    public function create(User $user, array $data): Lead
    {
        return DB::transaction(function () use ($user, $data) {
            $phone = LeadData::normalizePhone($data['mobile_number']);
            $lead = Lead::create(array_merge($data, ['mobile_number' => $phone, 'normalized_mobile' => $phone, 'status' => 'Active', 'created_by' => $user->id]));
            if ($lead->assigned_to) {
                LeadAssignment::create(['lead_id' => $lead->id, 'new_employee_id' => $lead->assigned_to, 'assigned_by' => $user->id, 'reason' => 'Initial import assignment']);
            }
            self::audit($user, 'created', $lead);

            return $lead;
        });
    }

    public function assign(User $user, Lead $lead, ?int $employeeId, ?string $reason): void
    {
        DB::transaction(function () use ($user, $lead, $employeeId, $reason) {
            $locked = Lead::whereKey($lead->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($user)->authorize('assign', $locked);
            if ($employeeId && ! LeadData::eligibleEmployees()->whereKey($employeeId)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['assigned_to' => 'The employee is no longer eligible.']);
            }
            if ($locked->assigned_to == $employeeId) {
                return;
            }
            LeadAssignment::create(['lead_id' => $locked->id, 'previous_employee_id' => $locked->assigned_to, 'new_employee_id' => $employeeId, 'assigned_by' => $user->id, 'reason' => $reason]);
            $locked->update(['assigned_to' => $employeeId]);
            self::audit($user, 'assigned', $locked, ['employee_id' => $employeeId]);
        }, 3);
    }
}
