<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'triggered_by_user_id',
    'mode',
    'status',
    'local_version',
    'target_version',
    'local_commit',
    'target_commit',
    'changed_files',
    'summary',
    'log_output',
    'started_at',
    'ended_at',
])]
class UpdateRun extends Model
{
    protected function casts(): array
    {
        return [
            'changed_files' => 'array',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }
}
