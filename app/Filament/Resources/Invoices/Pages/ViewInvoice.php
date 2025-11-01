<?php

namespace App\Filament\Resources\Invoices\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\ReturnInvoices\Pages\CreateReturnInvoice;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected static ?string $title = 'عرض الفاتورة';

    protected string $view = 'filament.pages.invoices.view-invoice';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create_return')
                ->label('عمل مرتجع')
                ->color('rose')
                ->icon('heroicon-s-arrow-path')
                ->url(fn($record) => CreateReturnInvoice::getUrl([
                    // go to create ReturnInvoice page
                    'original_invoice' => $record->id,
                ]))
                ->openUrlInNewTab(false)
                ->disabled(fn($record) => !$record->hasReturnableItems())
                ->tooltip(
                    fn($record) =>
                    $record->hasReturnableItems()
                        ? 'إنشاء مرتجع للفاتورة'
                        : 'لا توجد منتجات متاحة للاسترجاع'
                ),

            Actions\Action::make('back')
                ->label('رجوع')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                // to refresh the full page in filament
                ->extraAttributes(['wire:navigate' => true]) // very important
                ->url(InvoiceResource::getUrl('index'))
        ];
    }

    public function mount(int | string $record): void
    {
        parent::mount($record);

        // Eager load the relationships
        $this->record->load(['items.product']);
    }
}
