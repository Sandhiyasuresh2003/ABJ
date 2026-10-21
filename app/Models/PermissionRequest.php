<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermissionRequest extends Model
{
    protected $fillable = ['employee_id', 'permission_date', 'start_time', 'end_time', 'reason', 'status', 'reviewed_by', 'reviewed_at'];
    protected function casts(): array { return ['permission_date' => 'date', 'reviewed_at' => 'datetime']; }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
}
