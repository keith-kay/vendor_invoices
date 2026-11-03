<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'vendor_id', 'invoice_number', 'invoice_date', 'currency', 'gross_amount', 'tax_amount',
        'receipt_channel', 'category', 'status', 'claim_required', 'attachments',
        'posting_reference', 'payment_reference', 'paid_at', 'registered_by',
    ];

    protected $attributes = ['status' => 'registered'];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'invoice_date' => 'date',
            'gross_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'claim_required' => 'boolean',
            'attachments' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function registrar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(InvoiceAllocation::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(InvoiceApproval::class)->orderBy('decided_at');
    }

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class);
    }

    public function etimsValidations(): HasMany
    {
        return $this->hasMany(EtimsValidation::class);
    }

    public function allocatedTotal(): float
    {
        return (float) $this->allocations()->sum('allocated_amount');
    }

    public function isBalanced(): bool
    {
        return abs($this->allocatedTotal() - (float) $this->gross_amount) < 0.005;
    }
}
