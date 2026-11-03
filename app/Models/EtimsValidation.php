<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EtimsValidation extends Model
{
    protected $fillable = ['invoice_id', 'status', 'response_code', 'kra_reference', 'payload', 'checked_at'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'checked_at' => 'datetime'];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
