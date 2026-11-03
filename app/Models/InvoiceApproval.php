<?php

namespace App\Models;

use App\Enums\ApprovalDecision;
use App\Enums\ApprovalStage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceApproval extends Model
{
    protected $fillable = ['invoice_id', 'user_id', 'stage', 'decision', 'from_status', 'to_status', 'comment', 'decided_at'];

    protected function casts(): array
    {
        return [
            'stage' => ApprovalStage::class,
            'decision' => ApprovalDecision::class,
            'decided_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
