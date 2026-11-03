<?php

namespace App\Models;

use App\Enums\ClaimStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Claim extends Model
{
    protected $fillable = ['invoice_id', 'owner_id', 'status', 'claimed_amount', 'recovered_amount', 'credit_note_ref', 'notes', 'closed_at'];

    protected function casts(): array
    {
        return [
            'status' => ClaimStatus::class,
            'claimed_amount' => 'decimal:2',
            'recovered_amount' => 'decimal:2',
            'closed_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
