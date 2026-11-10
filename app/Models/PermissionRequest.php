<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermissionRequest extends Model
{
    use \App\Models\Concerns\SerializesEmployeeRecords;
    protected $appends = ['duration_minutes'];

    public function getDurationMinutesAttribute(): int
    {
        [$startHour, $startMinute] = array_map('intval', explode(':', $this->start_time));
        [$endHour, $endMinute] = array_map('intval', explode(':', $this->end_time));
        return max(0, ($endHour * 60 + $endMinute) - ($startHour * 60 + $startMinute));
    }
    protected $fillable = ['employee_id', 'permission_date', 'start_time', 'end_time', 'reason', 'status', 'reviewed_by', 'reviewed_at'];
    protected function casts(): array { return ['permission_date' => 'date', 'reviewed_at' => 'datetime']; }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
}
