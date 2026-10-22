<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Gate;

class Lead extends Model
{
    protected $fillable = ['lead_name', 'mobile_number', 'normalized_mobile', 'location', 'requirement', 'assigned_to', 'status', 'created_by', 'source_data'];

    protected $hidden = ['mobile_number', 'normalized_mobile', 'source_data'];

    protected function casts(): array
    {
        return ['source_data' => 'encrypted:array'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function followups(): HasMany
    {
        return $this->hasMany(LeadFollowup::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(LeadAssignment::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(LeadAudit::class);
    }

    public function lastFollowup(): HasOne
    {
        return $this->hasOne(LeadFollowup::class)->ofMany(['followup_at' => 'max', 'id' => 'max']);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if (in_array($user->role, ['admin', 'owner'], true)) {
            return $query;
        }
        if ($user->role !== 'employee') {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('assigned_to', Employee::query()->select('id')
            ->where('email', $user->email)->where('employment_status', 'Active'));
    }

    public function displayMobile(User $user): string
    {
        return Gate::forUser($user)->allows('viewMobile', $this)
            ? $this->mobile_number : str_repeat('*', max(0, strlen($this->normalized_mobile) - 4)).substr($this->normalized_mobile, -4);
    }
}
