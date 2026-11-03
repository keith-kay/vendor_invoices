<?php

namespace App\Filament\Resources\Claims\Tables;

use App\Enums\ClaimStatus;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ClaimsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('Claim')
                    ->sortable()
                    ->formatStateUsing(fn ($state): string => sprintf('CLM-%04d', $state)),
                TextColumn::make('invoice.invoice_number')->searchable(),
                TextColumn::make('invoice.vendor.name')->label('Vendor'),
                TextColumn::make('owner.name')->label('Owner'),
                TextColumn::make('status')->badge(),
                TextColumn::make('claimed_amount')->numeric(2)->alignEnd(),
                TextColumn::make('recovered_amount')->numeric(2)->alignEnd(),
                TextColumn::make('created_at')->since()->label('Age')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(ClaimStatus::class)->multiple(),
            ])
            ->recordActions([EditAction::make()]);
    }
}