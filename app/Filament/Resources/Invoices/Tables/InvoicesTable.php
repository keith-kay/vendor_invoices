<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\Invoices\Actions\InvoiceActions;
use App\Models\Invoice;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('invoice_number')->searchable()->sortable(),
                TextColumn::make('vendor.name')->searchable()->sortable(),
                TextColumn::make('gross_amount')
                    ->money(fn (Invoice $record): string => $record->currency)
                    ->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('allocations_count')->counts('allocations')->label('Jobs'),
                IconColumn::make('claim_required')->boolean()->label('Claim'),
                TextColumn::make('updated_at')->since()->label('Last change')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(InvoiceStatus::class)->multiple(),
                SelectFilter::make('vendor_id')->relationship('vendor', 'name')->label('Vendor'),
                TernaryFilter::make('claim_required')->label('Has claim'),
            ])
            ->recordActions([
                ActionGroup::make(InvoiceActions::all()),
                EditAction::make()->label('Open'),
            ]);
    }
}