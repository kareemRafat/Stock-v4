<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Actions;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('رجوع')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => Session::get('previous_url') ?? CustomerResource::getUrl('index')),
            Actions\DeleteAction::make()
                ->label('حذف')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->hidden(fn () => ! Auth::user() || Auth::user()->role->value !== 'admin')
                ->extraAttributes(['class' => 'font-semibold']),
        ];
    }

    public function mount($record): void
    {
        parent::mount($record);

        $referer = request()->headers->get('referer');

        if ($referer && $referer !== URL::full()) {
            session()->put('previous_url', $referer);
        }
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('المعلومات الأساسية')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('name')
                                ->label('اسم العميل')
                                ->weight('semibold')
                                ->size('md')
                                ->icon('heroicon-o-user')
                                ->color('primary'),

                            TextEntry::make('phone')
                                ->label('رقم الهاتف')
                                ->copyable()
                                ->copyMessage('تم نسخ رقم الهاتف')
                                ->placeholder('لايوجد')
                                ->icon('heroicon-o-phone')
                                ->color('orange')
                                ->weight('semibold'),

                            TextEntry::make('phone2')
                                ->label('رقم احتياطي')
                                ->copyable()
                                ->copyMessage('تم نسخ الرقم الاحتياطي')
                                ->placeholder('لايوجد')
                                ->icon('heroicon-o-phone')
                                ->color('warning'),

                            TextEntry::make('balance')
                                ->label('رصيد العميل')
                                ->state(fn ($record) => $record->calculateBalance())
                                ->placeholder('لا يوجد')
                                ->icon('heroicon-o-banknotes')
                                ->formatStateUsing(function ($state) {
                                    if ($state == 0) {
                                        return '0.00 ج.م (حساب متوازن)';
                                    }

                                    $formatted = number_format(abs($state), 2, '.', ',').' ج.م';

                                    if ($state > 0) {
                                        return "{$formatted} (مديونية على العميل)";
                                    } else {
                                        return "{$formatted} (للعميل)";
                                    }
                                })
                                ->color(function ($state) {
                                    if ($state > 0) {
                                        return 'danger';
                                    }
                                    if ($state < 0) {
                                        return 'success';
                                    }

                                    return 'success';
                                })
                                ->weight('semibold')
                                ->url(fn ($record) => route('filament.admin.resources.customers.wallet', $record))
                                ->openUrlInNewTab(false)
                                ->tooltip('اضغط لعرض حركات المحفظة'),
                        ]),
                    ])
                    ->columnSpanFull()
                    ->collapsible(),
                Section::make('معلومات العنوان')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('address')
                                ->label('العنوان')
                                ->placeholder('لايوجد')
                                ->columnSpanFull()
                                ->size('md')
                                ->weight('semibold')
                                ->color('indigo')
                                ->icon('heroicon-o-map-pin')
                                ->copyable(),

                            TextEntry::make('city')
                                ->label('المدينة')
                                ->weight('semibold')
                                ->placeholder('لايوجد')
                                ->icon('heroicon-o-building-office'),

                            TextEntry::make('governorate')
                                ->label('المحافظة')
                                ->placeholder('لايوجد')
                                ->weight('semibold')
                                ->icon('heroicon-o-globe-alt'),
                        ]),
                    ])
                    ->columnSpanFull()
                    ->collapsible(),
            ]);
    }
}
