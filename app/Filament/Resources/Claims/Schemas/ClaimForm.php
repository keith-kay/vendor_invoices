<?php

namespace App\Filament\Resources\Claims\Schemas;

use App\Enums\ClaimStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ClaimForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('invoice_id')
                ->relationship('invoice', 'invoice_number')
                ->disabled(),
            Select::make('owner_id')
                ->relationship('owner', 'name')
                ->searchable()
                ->preload()
                ->label('Owner'),
            Select::make('status')->options(ClaimStatus::class)->required(),
            TextInput::make('credit_note_ref'),
            TextInput::make('claimed_amount')->numeric()->required(),
            TextInput::make('recovered_amount')->numeric()->required(),
            Textarea::make('notes')->columnSpanFull(),
            DateTimePicker::make('closed_at'),
        ]);
    }
}