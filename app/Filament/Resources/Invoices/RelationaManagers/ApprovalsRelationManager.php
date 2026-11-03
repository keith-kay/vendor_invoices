<?php

namespace App\Filament\Resources\Invoices\RelationManagers;

use BackedEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ApprovalsRelationManager extends RelationManager
{
    protected static string $relationship = 'approvals';

    protected static ?string $title = 'History';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        $pretty = fn ($state): string => str($state instanceof BackedEnum ? $state->value : (string) $state)
            ->replace('_', ' ')
            ->headline()
            ->toString();

        return $table
            ->recordTitleAttribute('decision')
            ->modifyQueryUsing(fn (Builder $query) => $query->reorder('decided_at', 'desc'))
            ->columns([
                TextColumn::make('decided_at')->dateTime('d M Y H:i')->label('When'),
                TextColumn::make('user.name')->label('Who')->placeholder('System'),
                TextColumn::make('stage')->badge()->formatStateUsing($pretty),
                TextColumn::make('decision')->badge()->formatStateUsing($pretty),
                TextColumn::make('comment')->wrap()->limit(120),
            ]);
    }
}