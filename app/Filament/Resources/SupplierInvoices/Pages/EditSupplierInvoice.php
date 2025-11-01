<?php

namespace App\Filament\Resources\SupplierInvoices\Pages;

use App\Filament\Resources\SupplierInvoices\SupplierInvoiceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditSupplierInvoice extends EditRecord
{
    protected static string $resource = SupplierInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn () => ! Auth::user() || Auth::user()->role->value !== 'admin'),
        ];
    }
}
