<?php

namespace App\Filament\Resources\SupplierInvoices\Pages;

use App\Filament\Resources\SupplierInvoices\SupplierInvoiceResource;
use Filament\Resources\Pages\EditRecord;

class EditSupplierInvoice extends EditRecord
{
    protected static string $resource = SupplierInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
