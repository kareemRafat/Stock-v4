<?php

namespace App\Filament\Resources\ReturnInvoices\Pages;

use App\Filament\Resources\ReturnInvoices\ReturnInvoiceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReturnInvoice extends EditRecord
{
    protected static string $resource = ReturnInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
