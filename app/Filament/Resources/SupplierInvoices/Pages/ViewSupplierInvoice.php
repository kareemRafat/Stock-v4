<?php

namespace App\Filament\Resources\SupplierInvoices\Pages;

use App\Filament\Resources\SupplierInvoices\SupplierInvoiceResource;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewSupplierInvoice extends ViewRecord
{
    protected static string $resource = SupplierInvoiceResource::class;

    protected static ?string $title = 'عرض فاتورة المورد';

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('تفاصيل الفاتورة')
                    ->schema([
                        TextEntry::make('invoice_number')
                            ->label('رقم الفاتورة')
                            ->weight('semibold'),
                        TextEntry::make('supplier.name')
                            ->label('اسم المورد')
                            ->color('rose')
                            ->weight('semibold'),
                        TextEntry::make('total_amount')
                            ->label('إجمالي الفاتورة')
                            ->suffix(' جنيه')
                            ->weight('semibold'),
                        TextEntry::make('invoice_date')
                            ->label('تاريخ الفاتورة')
                            ->date('d/m/Y')
                            ->color('purple')
                            ->weight('semibold'),
                    ])
                    ->columnSpanFull()
                    ->columns(2)
                    ->collapsible(),
                Section::make('الأصناف')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('تفاصيل الأصناف')
                            ->schema([
                                TextEntry::make('product.name')
                                    ->label('اسم المنتج')
                                    ->color('indigo'),

                                TextEntry::make('quantity')
                                    ->label('الكمية')
                                    ->numeric()
                                    ->formatStateUsing(fn ($state) => (int) $state.''),

                                TextEntry::make('cost_price')
                                    ->label('سعر المورد للوحدة')
                                    ->suffix(' جنيه')
                                    ->numeric()
                                    ->formatStateUsing(fn ($state) => (int) $state.''),

                                TextEntry::make('wholesale_price')
                                    ->label('سعر  الجملة بعد الخصم')
                                    ->suffix(' جنيه')
                                    ->numeric()
                                    ->state(function ($record) {
                                        $price = $record->wholesale_price ?? 0;
                                        $discount = $record->product->discount ?? 0;

                                        $final = $price - ($price * ($discount / 100));

                                        return round($final, 2);
                                    })
                                    ->formatStateUsing(fn ($state) => (int) $state.''),

                                TextEntry::make('retail_price')
                                    ->label('سعر البيع للقطاعي')
                                    ->suffix(' جنيه')
                                    ->numeric()
                                    ->formatStateUsing(fn ($state) => (int) $state.''),

                                TextEntry::make('subtotal')
                                    ->label('الإجمالي')
                                    ->suffix(' جنيه')
                                    ->numeric()
                                    ->formatStateUsing(fn ($state) => (int) $state.''),
                            ])
                            ->columns(6)
                            ->grid(1),
                    ])
                    ->columnSpanFull()
                    ->collapsible(),
            ]);
    }

    /**
     * Add header actions.
     */
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make()
                ->label('إضافة فاتورة جديدة')
                ->icon('heroicon-o-plus-circle')
                ->url(route('filament.admin.resources.supplier-invoices.create')), // adjust panel if needed
            \Filament\Actions\Action::make('back')
                ->label('رجوع')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                // Refresh the table on index page
                ->extraAttributes(['wire:navigate' => true]) // very important
                ->url(SupplierInvoiceResource::getUrl('index')),
        ];
    }
}
