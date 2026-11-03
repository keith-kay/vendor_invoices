<?php

namespace App\Filament\Widgets;

use App\Enums\InvoiceStatus as S;
use App\Models\Invoice;
use Filament\Widgets\ChartWidget;

class InvoicesByStage extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Invoices by stage';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $counts = Invoice::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // label => statuses counted in that bar
        $stages = [
            'Registered' => [S::Registered, S::Matched, S::Allocated],
            'Receiver approval' => [S::PendingReceiver],
            'Accrual confirmation' => [S::PendingCs],
            'Finance approval' => [S::PendingFinance],
            'GBS posting' => [S::PendingPosting],
            'Ready for payment' => [S::ReadyForPayment],
            'Paid' => [S::Paid],
            'Rejected' => [S::Rejected],
        ];

        $data = array_map(
            fn (array $statuses): int => (int) collect($statuses)->sum(fn (S $s) => $counts[$s->value] ?? 0),
            array_values($stages),
        );

        return [
            'datasets' => [['label' => 'Invoices', 'data' => $data]],
            'labels' => array_keys($stages),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}