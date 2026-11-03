<?php

namespace App\Filament\Resources\Vendors\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VendorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'lg' => 3])
            ->components([
                Section::make('Vendor details')
                    ->description('Used to match invoices and check eTIMS later.')
                    ->icon('heroicon-o-building-storefront')
                    ->columnSpan(['lg' => 2])
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->placeholder('Registered business name')
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('kra_pin')
                            ->label('KRA PIN')
                            ->placeholder('P000000000A')
                            ->maxLength(11)
                            ->unique(ignoreRecord: true),
                        TextInput::make('vendor_code')
                            ->placeholder('Internal vendor code')
                            ->unique(ignoreRecord: true),
                    ]),
            ]);
    }
}