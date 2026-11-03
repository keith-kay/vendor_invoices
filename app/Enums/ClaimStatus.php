<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ClaimStatus: string implements HasLabel, HasColor
{
    case Open = 'open';
    case UnderInvestigation = 'under_investigation';
    case CreditNotePending = 'credit_note_pending';
    case Closed = 'closed';

    public function getLabel(): string
    {
        return match ($this) {
            self::UnderInvestigation => 'Under investigation',
            self::CreditNotePending => 'Credit note pending',
            default => ucfirst($this->value),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'warning',
            self::UnderInvestigation => 'info',
            self::CreditNotePending => 'primary',
            self::Closed => 'success',
        };
    }
}
