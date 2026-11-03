<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvoiceStatus: string implements HasLabel, HasColor
{
    case Registered = 'registered';
    case Matched = 'matched';
    case Allocated = 'allocated';
    case PendingReceiver = 'pending_receiver';
    case PendingCs = 'pending_cs';
    case PendingFinance = 'pending_finance';
    case PendingPosting = 'pending_posting';
    case ReadyForPayment = 'ready_for_payment';
    case Paid = 'paid';
    case Rejected = 'rejected';
    case OnHold = 'on_hold';

    public function getLabel(): string
    {
        return match ($this) {
            self::PendingReceiver => 'Pending receiver approval',
            self::PendingCs => 'Pending accrual confirmation',
            self::PendingFinance => 'Pending finance approval',
            self::PendingPosting => 'Pending GBS posting',
            self::ReadyForPayment => 'Ready for payment',
            self::OnHold => 'On hold',
            default => ucfirst($this->value),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Paid => 'success',
            self::Rejected => 'danger',
            self::OnHold => 'warning',
            self::ReadyForPayment => 'info',
            self::Registered, self::Matched, self::Allocated => 'gray',
            default => 'primary',
        };
    }

    /** Invoice header and allocations can only be changed in these states. */
    public function isEditable(): bool
    {
        return in_array($this, [self::Registered, self::Matched, self::Allocated, self::Rejected], true);
    }
}
