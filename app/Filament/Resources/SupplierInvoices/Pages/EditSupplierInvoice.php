<?php

namespace App\Filament\Resources\SupplierInvoices\Pages;

use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\SupplierInvoices\SupplierInvoiceResource;

class EditSupplierInvoice extends EditRecord
{
    protected static string $resource = SupplierInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn() => !Auth::user() || Auth::user()->role->value !== 'admin'),
        ];
    }
}
