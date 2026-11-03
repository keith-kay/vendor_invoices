<?php

namespace App\Filament\Resources\Departments\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DepartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'lg' => 3])
            ->components([
                Section::make('Department')
                    ->description('Who approves invoices allocated to this department.')
                    ->icon('heroicon-o-building-office')
                    ->columnSpan(['lg' => 2])
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->placeholder('e.g. Operations')
                            ->required()
                            ->columnSpanFull(),
                        Select::make('default_approver_id')
                            ->relationship('defaultApprover', 'name')
                            ->label('Default approver')
                            ->placeholder('Pick a user')
                            ->searchable()
                            ->preload(),
                        Select::make('substitute_approver_id')
                            ->relationship('substituteApprover', 'name')
                            ->label('Substitute approver')
                            ->placeholder('Pick a user')
                            ->helperText('Covers when the default approver is away.')
                            ->searchable()
                            ->preload(),
                    ]),
            ]);
    }
}