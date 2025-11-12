<?php

namespace App\Filament\Resources\Customers\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Customers\CustomerResource;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->modalDescription(' هل أنت متأكد من القيام بهذه العملية ؟ سيتم حذف جميع سجلات الارصدة للعميل')
                ->hidden(fn() => ! Auth::user()->isAdmin()),
            Action::make('back')
                ->label('رجوع')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn() => CustomerResource::getUrl('index')),
        ];
    }
}
