<?php

namespace App\Filament\Widgets;

use App\Enums\ClaimStatus;
use App\Enums\InvoiceStatus;
use App\Models\Claim;
use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InvoiceStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $values = fn (InvoiceStatus ...$statuses): array => array_map(fn ($s) => $s->value, $statuses);

        $draft = Invoice::whereIn('status', $values(
            InvoiceStatus::Registered, InvoiceStatus::Matched, InvoiceStatus::Allocated,
        ))->count();

        $inApproval = Invoice::whereIn('status', $values(
            InvoiceStatus::PendingReceiver, InvoiceStatus::PendingCs,
            InvoiceStatus::PendingFinance, InvoiceStatus::PendingPosting,
        ))->count();

        $openClaims = Claim::where('status', '!=', ClaimStatus::Closed->value)->count();

        $ready = Invoice::where('status', InvoiceStatus::ReadyForPayment->value)->count();

        return [
            Stat::make('Not yet submitted', $draft)->description('Registered, awaiting submission'),
            Stat::make('In approval', $inApproval)->description('Receiver, accruals, finance, posting'),
            Stat::make('Open claims', $openClaims)->color('warning'),
            Stat::make('Ready for payment', $ready)->color('success'),
        ];
    }
}