<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Models\Invoice;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        // Header and allocations are read-only once the invoice has been submitted.
        $locked = fn (?Invoice $record): bool => $record !== null && ! $record->status->isEditable();

        return $schema->columns(['default' => 1, 'lg' => 2])->components([
            // ---- Row 1: how to start (full width) ----
            Section::make('How do you want to start?')
                ->icon('heroicon-o-arrow-up-tray')
                ->columnSpanFull()
                ->disabled($locked)
                ->schema([
                    ToggleButtons::make('entry_mode')
                        ->hiddenLabel()
                        ->options([
                            'upload' => 'Upload the invoice file',
                            'manual' => 'Enter the details manually',
                        ])
                        ->icons([
                            'upload' => 'heroicon-o-arrow-up-tray',
                            'manual' => 'heroicon-o-pencil-square',
                        ])
                        ->default('upload')
                        ->inline()
                        ->live()
                        ->dehydrated(false),
                    FileUpload::make('attachments')
                        ->hiddenLabel()
                        ->multiple()
                        ->directory('invoices')
                        ->acceptedFileTypes(['application/pdf', 'image/*'])
                        ->maxSize(10240)
                        ->helperText('PDF or image, up to 10 MB each. Reading the details from the file is not connected yet, so fill them in below.')
                        ->downloadable()
                        ->openable()
                        ->visible(fn (Get $get): bool => $get('entry_mode') !== 'manual'),
                ]),

            // ---- Row 2: details and amounts, half width each ----
            Section::make('Invoice details')
                ->description('Who sent it and how to identify it.')
                ->icon('heroicon-o-document-text')
                ->disabled($locked)
                ->columns(2)
                ->schema([
                    Select::make('vendor_id')
                        ->relationship('vendor', 'name')
                        ->placeholder('Search or pick a vendor')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpanFull()
                        ->createOptionForm([
                            TextInput::make('name')->required(),
                            TextInput::make('kra_pin')->label('KRA PIN')->unique('vendors', 'kra_pin'),
                            TextInput::make('vendor_code')->unique('vendors', 'vendor_code'),
                        ]),
                    TextInput::make('invoice_number')
                        ->placeholder('As printed on the invoice')
                        ->required()
                        ->unique(
                            ignoreRecord: true,
                            modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('vendor_id', $get('vendor_id')),
                        )
                        ->validationMessages(['unique' => 'This vendor already has an invoice with that number.']),
                    DatePicker::make('invoice_date')->required(),
                ]),

            Section::make('Amounts')
                ->description('What the invoice is worth.')
                ->icon('heroicon-o-banknotes')
                ->disabled($locked)
                ->columns(2)
                ->schema([
                    Select::make('currency')
                        ->options(['KES' => 'KES', 'USD' => 'USD', 'EUR' => 'EUR'])
                        ->default('KES')
                        ->required()
                        ->live()
                        ->columnSpanFull(),
                    TextInput::make('gross_amount')
                        ->prefix(fn (Get $get): string => $get('currency') ?: 'KES')
                        ->numeric()
                        ->step('0.01')
                        ->placeholder('0.00')
                        ->required()
                        ->minValue(0.01)
                        ->live(onBlur: true),
                    TextInput::make('tax_amount')
                        ->prefix(fn (Get $get): string => $get('currency') ?: 'KES')
                        ->numeric()
                        ->step('0.01')
                        ->default(0),
                ]),

            // ---- Row 3: job allocation (full width) ----
            Section::make('Job allocation')
                ->description('Split the invoice across CargoWise jobs. The total must match the invoice before it can be submitted.')
                ->icon('heroicon-o-rectangle-stack')
                ->columnSpanFull()
                ->disabled($locked)
                ->schema([
                    Repeater::make('allocations')
                        ->relationship()
                        ->hiddenLabel()
                        ->addActionLabel('Add job')
                        ->live()
                        ->hint(function (Get $get): string {
                            [$allocated, $gross] = self::totals($get);

                            if ($gross <= 0) {
                                return 'Enter the gross amount first';
                            }

                            $diff = round($gross - $allocated, 2);

                            return match (true) {
                                $diff == 0.0 => 'Balanced',
                                $diff > 0 => 'Allocated ' . number_format($allocated, 2) . ' of ' . number_format($gross, 2)
                                    . ' - ' . number_format($diff, 2) . ' still to allocate',
                                default => 'Over-allocated by ' . number_format(abs($diff), 2),
                            };
                        })
                        ->hintColor(function (Get $get): string {
                            [$allocated, $gross] = self::totals($get);

                            if ($gross <= 0) {
                                return 'gray';
                            }

                            return round($gross - $allocated, 2) == 0.0 ? 'success' : 'warning';
                        })
                        ->table([
                            TableColumn::make('CargoWise job')->markAsRequired()->width('24%'),
                            TableColumn::make('Cost type')->width('20%'),
                            TableColumn::make('Department')->markAsRequired()->width('28%'),
                            TableColumn::make('Amount')->markAsRequired()->width('28%'),
                        ])
                        ->schema([
                            TextInput::make('cargowise_job_ref')
                                ->label('CargoWise job')
                                ->placeholder('JOB-1001')
                                ->required()
                                ->dehydrateStateUsing(fn (?string $state): ?string => $state === null ? null : strtoupper(trim($state))),
                            TextInput::make('cost_type')->placeholder('Freight'),
                            Select::make('department_id')
                                ->relationship('department', 'name')
                                ->placeholder('Pick one')
                                ->searchable()
                                ->preload()
                                ->required(),
                            TextInput::make('allocated_amount')
                                ->numeric()
                                ->step('0.01')
                                ->placeholder('0.00')
                                ->required()
                                ->minValue(0.01)
                                ->live(onBlur: true),
                        ]),
                ]),
        ]);
    }

    /** @return array{0: float, 1: float} allocated total and gross amount */
    private static function totals(Get $get): array
    {
        $allocated = collect($get('allocations') ?? [])
            ->sum(fn ($row) => (float) ($row['allocated_amount'] ?? 0));

        return [$allocated, (float) ($get('gross_amount') ?? 0)];
    }
}