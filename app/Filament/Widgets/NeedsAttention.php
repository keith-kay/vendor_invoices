<?php

namespace App\Filament\Widgets;

use App\Enums\ClaimStatus;
use App\Enums\InvoiceStatus;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class NeedsAttention extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $draftStatuses = [
            InvoiceStatus::Registered->value,
            InvoiceStatus::Matched->value,
            InvoiceStatus::Allocated->value,
        ];

        return $table
            ->heading('Needs attention')
            ->query(fn (): Builder => Invoice::query()
                ->with('vendor')
                ->withExists(['claims as has_open_claim' => fn (Builder $q) => $q
                    ->where('status', '!=', ClaimStatus::Closed->value)])
                ->where(function (Builder $q) use ($draftStatuses) {
                    $q->where('status', InvoiceStatus::Rejected->value)
                        ->orWhere(fn (Builder $q) => $q
                            ->whereIn('status', $draftStatuses)
                            ->where('created_at', '<', now()->subDays(3)))
                        ->orWhereHas('claims', fn (Builder $c) => $c
                            ->where('status', '!=', ClaimStatus::Closed->value));
                })
                ->latest('updated_at'))
            ->columns([
                TextColumn::make('invoice_number')->label('Invoice'),
                TextColumn::make('vendor.name')->label('Vendor'),
                TextColumn::make('status')->badge(),
                TextColumn::make('reason')
                    ->state(fn (Invoice $record): string => match (true) {
                        $record->status === InvoiceStatus::Rejected => 'Rejected, needs correction',
                        (bool) $record->has_open_claim => 'Open claim',
                        default => 'Not submitted for 3+ days',
                    }),
                TextColumn::make('created_at')->since()->label('Registered'),
            ])
            ->recordUrl(fn (Invoice $record): string => InvoiceResource::getUrl('edit', ['record' => $record]))
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Nothing needs attention');
    }
}