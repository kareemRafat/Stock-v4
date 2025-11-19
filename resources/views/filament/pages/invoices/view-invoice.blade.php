<x-filament-panels::page>
    <div class="py-4">
        <!-- Print Buttons Section -->
        <div class="flex justify-end mb-4 no-print gap-4">
            <!-- Print Button for ezn el saft -->
            <button onclick="printDeliveryNote()"
                class="flex items-center text-sm font-semibold text-white px-4 py-1 rounded-md shadow hover:bg-primary-700 transition duration-200 gap-2"
                style="background-color: #0f766e;">
                <x-heroicon-o-document-check class="h-5 w-5" />
                <span>طباعة إذن الصرف</span>
            </button>
            <!-- Print Button -->
            <button onclick="printInvoice()"
                class="flex items-center text-sm font-semibold text-white px-4 py-2 rounded-md shadow hover:bg-primary-700 transition duration-200 gap-2"
                style="background-color: #6860ff;">
                <x-heroicon-o-printer class="h-5 w-5" />
                <span>طباعة الفاتورة</span>
            </button>
        </div>

        <div id="print-area" class="bg-white p-6 rounded-lg shadow-sm ring-1 ring-gray-200">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-center">
                <div class="mb-2">
                    <h1 class="text-3xl font-bold text-primary-900 mb-4">فاتورة</h1>
                    <div class="text-black text-sm space-y-1">
                        <p class="text-base font-medium text-black mb-2">شركة أحمد حسين</p>
                        <p>لمواد التعبئة والتغليف</p>
                    </div>
                    <div class="flex mt-1 text-sm font-medium gap-1">
                        {{-- <x-heroicon-s-phone class="w-4 h-4" /> --}}
                        <span class="text-sm">موبايل :</span>
                        <span>01016011318</span>

                    </div>
                </div>

                <div class="mt-4 sm:mt-0 sm:text-right">
                    <div
                        class="flex items-stretch text-black px-4 py-1.5 rounded-md mb-2 text-sm font-medium justify-end gap-4">
                        <div class="flex items-center flex-col">
                            <span class="mb-2">رقم الفاتورة :</span>
                            <span># {{ $this->getRecord()->invoice_number }}</span>
                        </div>
                        <div
                            style="width: 1px; height: auto; border-right: 1px solid rgb(111, 111, 111); margin: 0 2px;">
                        </div>
                        <div class="flex items-center flex-col">
                            <span class="mr-1 mb-2">التاريخ:</span>
                            <span>{{ $this->getRecord()->createdDate }}</span>
                            <span dir="ltr">{{ $this->getRecord()->createdTime }}</span>
                        </div>
                    </div>

                </div>
            </div>

            <hr class="mb-4 mt-2 border-gray-500">

            <!-- Customer -->
            <div class="mb-6">
                {{-- <h3 class="text-base font-medium text-black my-4">طبعت الفاتورة لأمر :</h3> --}}
                <div
                    class="bg-gray-50 px-3 py-2 rounded-sm border border-gray-400 my-4 flex flex-col sm:flex-row justify-between items-center">
                    <p class="text-black">{{ $this->getRecord()->customer->name ?? '-' }}</p>
                    <div class="flex flex-row gap-5">
                        <p class="text-black text-sm flex gap-3">
                            <x-heroicon-s-map-pin class="w-4 h-4" />
                            {{ $this->getRecord()->customer->address ?? '---' }}
                        </p>
                        <div
                            style="width: 1px; height: auto; border-right: 1px solid rgb(111, 111, 111); margin: 0 2px;">
                        </div>
                        <p class="text-black text-sm flex gap-3">
                            <x-heroicon-s-phone class="w-4 h-4" />
                            {{ $this->getRecord()->customer->phone ?? '---' }}
                        </p>

                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="mb-6 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="text-right py-2 px-4 font-medium text-black text-sm print:text-xs border border-gray-400">
                                مسلسل</th>
                            <th
                                class="text-right py-2 px-4 font-medium text-black text-sm print:text-xs border border-gray-400">
                                المنتج</th>
                            <th
                                class="text-center py-2 px-4 font-medium text-black text-sm print:text-xs border border-gray-400">
                                الكمية</th>
                            <th
                                class="text-right py-2 px-4 font-medium text-black text-sm print:text-xs border border-gray-400">
                                الخصم</th>
                            <th
                                class="text-right py-2 px-4 font-medium text-black text-sm print:text-xs border border-gray-400">
                                السعر للوحدة</th>
                            <th
                                class="text-right py-2 px-4 font-medium text-black text-sm print:text-xs border border-gray-400">
                                الإجمالي</th>
                        </tr>
                    </thead>

                    <tbody>
                        @php
                            $isWholesale = $this->getRecord()->price_type === 'wholesale';
                            $totalBeforeSale = 0;
                            $totalDiscounts = 0;
                            $totalAfterDiscount = 0;
                        @endphp

                        @foreach ($this->getRecord()->items as $item)
                            @php
                                $product = $item->product;

                                $wholesalePrice = $item->wholesale_price ?? 0;
                                $retailPrice = $item->retail_price ?? 0;
                                $discountPercent = $item->discount ?? 0;

                                // السعر قبل الخصم
                                $priceBeforeDiscount = $isWholesale
                                    ? $wholesalePrice / (1 - $discountPercent / 100)
                                    : $retailPrice;

                                // السعر بعد الخصم (الفعلي)
                                $unitPrice = $isWholesale ? $wholesalePrice : $retailPrice;

                                // الإجماليات
                                $lineTotalBefore = $priceBeforeDiscount * $item->quantity;
                                $lineDiscount = $isWholesale ? $lineTotalBefore - $unitPrice * $item->quantity : 0;
                                $lineTotal = $unitPrice * $item->quantity;

                                $totalBeforeSale += $lineTotalBefore;
                                $totalDiscounts += $lineDiscount;
                                $totalAfterDiscount += $lineTotal;
                            @endphp

                            <tr class="border-t border-gray-400">
                                <td
                                    class="py-2 px-4 text-right text-black text-sm print:text-xs border border-gray-400">
                                    {{ $loop->iteration }}
                                </td>
                                <td class="py-2 px-4 text-black text-sm print:text-xs border border-gray-400">
                                    {{ $product->name ?? '---' }}
                                </td>
                                <td
                                    class="py-2 px-4 text-center text-black text-sm print:text-xs border border-gray-400">
                                    {{ $item->quantity }} {{ $product->unit ?? '' }}
                                </td>
                                <td
                                    class="py-2 px-4 text-right text-black text-sm print:text-xs border border-gray-400">
                                    {{ $isWholesale ? number_format($discountPercent) . ' %' : '-' }}
                                </td>
                                <td
                                    class="py-2 px-4 text-right text-black text-sm print:text-xs border border-gray-400">
                                    {{ number_format($priceBeforeDiscount, 2) }}
                                </td>
                                <td
                                    class="py-2 px-4 text-right font-medium text-black text-sm print:text-xs border border-gray-400">
                                    {{ number_format($lineTotal, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @include('filament.pages.invoices.delivery-note')

            <!-- Totals -->
            <div class="flex w-full justify-between mt-4 font-semibold">
                <div class="w-full space-y-2">
                    <div class="flex justify-between py-1 px-2">
                        <span class="text-sm text-black">الإجمالي قبل الخصم:</span>
                        <span class="font-medium text-sm text-black">{{ number_format($totalBeforeSale, 2) }}</span>
                    </div>

                    <div class="flex justify-between px-2">
                        <span class="text-sm text-black">إجمالي الخصومات:</span>
                        <span class="font-medium text-sm text-black">
                            {{ $isWholesale ? number_format($totalDiscounts, 2) : '-' }}
                        </span>
                    </div>

                    <div class="flex justify-between px-2">
                        <span class="text-sm text-black">المديونية السابقة :</span>
                        <span class="font-medium text-sm text-black">
                            {{ number_format($this->getRecord()->previous_debt, 2) }}
                        </span>
                    </div>

                    <hr class="border-gray-400">

                    @if ($this->getRecord()->special_discount > 0)
                        <div class="flex flex-col gap-2 py-3 px-4 rounded-md">
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-black">خصم خاص :</span>
                                <span class="text-sm font-medium text-black">
                                    {{ number_format($this->getRecord()->special_discount, 2) }} ج.م
                                </span>
                            </div>

                            <div class="flex justify-between py-3">
                                <span class="text-sm font-medium text-black">الإجمالي :</span>
                                <span class="text-sm font-medium text-black">
                                    {{ number_format(($isWholesale ? $totalAfterDiscount : $totalBeforeSale) - $this->getRecord()->special_discount + $this->getRecord()->previous_debt, 2) }}
                                    ج.م
                                </span>
                            </div>
                        </div>
                    @else
                        <div class="flex justify-between py-1 px-4 rounded-md">
                            <span class="text-base font-medium text-black">الإجمالي :</span>
                            <span class="text-base font-medium text-black">
                                {{ number_format(($isWholesale ? $totalAfterDiscount : $totalBeforeSale)  + $this->getRecord()->previous_debt, 2) }}
                                ج.م
                            </span>
                        </div>
                    @endif
                </div>
            </div>


        </div>
    </div>

    <script>
        function printInvoice() {
            const printContents = document.getElementById('print-area').innerHTML;
            const originalContents = document.body.innerHTML;
            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
        }

        function printDeliveryNote() {
            const deliveryNoteArea = document.getElementById('delivery-note-area');
            if (!deliveryNoteArea) {
                alert('منطقة إذن الصرف غير موجودة');
                return;
            }
            const printContents = deliveryNoteArea.innerHTML;
            const originalContents = document.body.innerHTML;
            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
        }
    </script>
</x-filament-panels::page>
