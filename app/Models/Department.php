<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Department extends Model
{
    protected $fillable = ['name', 'default_approver_id', 'substitute_approver_id'];

    public function defaultApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'default_approver_id');
    }

    public function substituteApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'substitute_approver_id');
    }
}
