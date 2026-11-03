<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceAllocation extends Model
{
    protected $fillable = [
        'invoice_id', 'department_id', 'cargowise_job_ref', 'cost_type', 'allocated_amount',
        'accrual_confirmed', 'accrual_confirmed_by', 'accrual_confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'allocated_amount' => 'decimal:2',
            'accrual_confirmed' => 'boolean',
            'accrual_confirmed_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
