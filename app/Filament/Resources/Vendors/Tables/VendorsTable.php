<?php

namespace App\Filament\Resources\Vendors\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VendorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('kra_pin')->label('KRA PIN')->searchable(),
                TextColumn::make('vendor_code')->searchable(),
                TextColumn::make('invoices_count')->counts('invoices')->label('Invoices'),
            ])
            ->recordActions([EditAction::make()]);
    }
}
