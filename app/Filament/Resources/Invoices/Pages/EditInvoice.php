<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\Actions\InvoiceActions;
use App\Filament\Resources\Invoices\InvoiceResource;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    // No delete action on purpose: invoices are audit records.
    protected function getHeaderActions(): array
    {
        return InvoiceActions::all();
    }

    protected function getFormActions(): array
    {
        return $this->getRecord()->status->isEditable()
            ? parent::getFormActions()
            : [$this->getCancelFormAction()];
    }
}