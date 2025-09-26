<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use Filament\Actions;
use App\Models\Invoice;
use Illuminate\Support\Facades\Session;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\ReturnInvoices\ReturnInvoiceResource;
use App\Filament\Resources\ReturnInvoices\Pages\CreateReturnInvoice;

class ViewReturnInvoice extends ViewRecord
{
    protected static string $resource = ReturnInvoiceResource::class;

    protected static ?string $title = 'عرض فاتورة المرتجع';

    protected string $view = 'filament.pages.ReturnInvoices.view-return-invoice';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('رجوع')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(function () {
                    return ReturnInvoiceResource::getUrl('index');
                }),
        ];
    }
}
