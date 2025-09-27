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
            <div class="flex flex-col sm:flex-row justify-between items-start">
                <div class="mb-4">
                    <h1 class="text-3xl font-bold text-primary-600 mb-4">فاتورة</h1>
                    <div class="text-gray-500 text-sm space-y-1">
                        <p class="text-base font-medium text-gray-600 mb-4">شركة أحمد حسين</p>
                        <p>لمواد التعبئة والتغليف</p>
                    </div>
                </div>

                <div class="mt-4 sm:mt-0 sm:text-right">
                    <div
                        class="flex items-stretch text-primary-900 px-4 py-1.5 rounded-md mb-2 text-sm font-medium justify-end gap-4">
                        <!-- Invoice Number -->
                        <div class="flex items-center flex-col">
                            <span class="mb-2">رقم الفاتورة :</span>
                            <span># {{ $this->getRecord()->invoice_number }}</span>
                        </div>

                        <!-- Vertical Separator -->
                        <div
                            style="width: 1px; height: auto; border-right: 1px solid rgb(111, 111, 111); margin: 0 2px;">
                        </div>

                        <!-- Date -->
                        <div class="flex items-center flex-col">
                            <span class="mr-1 mb-2">التاريخ:</span>
                            <span>{{ $this->getRecord()->createdDate }}</span>
                            <span dir="ltr">{{ $this->getRecord()->createdTime }}</span>
                        </div>
                    </div>
                    <div class="flex px-4 pb-2 mb-2 text-sm font-medium justify-end gap-4">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                            class="w-4 h-4 font-bold">
                            <path fill-rule="evenodd"
                                d="M1.5 4.5a3 3 0 0 1 3-3h1.372c.86 0 1.61.586 1.819 1.42l1.105 4.423a1.875 1.875 0 0 1-.694 1.955l-1.293.97c-.135.101-.164.249-.126.352a11.285 11.285 0 0 0 6.697 6.697c.103.038.25.009.352-.126l.97-1.293a1.875 1.875 0 0 1 1.955-.694l4.423 1.105c.834.209 1.42.959 1.42 1.82V19.5a3 3 0 0 1-3 3h-2.25C8.552 22.5 1.5 15.448 1.5 6.75V4.5Z"
                                clip-rule="evenodd" />
                        </svg>
                        <p>01030231321</p>
                        <span> - </span>
                        <p>01030231321</p>
                    </div>
                </div>
            </div>

            <!-- Separator -->
            <hr class="my-4 border-gray-500">

            <!-- Bill To Section -->
            <div class="mb-6">
                <h3 class="text-base font-medium text-gray-600 my-4">طبعت الفاتورة لأمر :</h3>
                <div
                    class="bg-gray-50 p-3 rounded-md border border-gray-400 my-4 flex flex-col sm:flex-row justify-between">
                    <p class="font-medium text-gray-600">{{ $this->getRecord()->customer->name ?? '-' }}</p>
                    <p class="text-gray-600 text-sm">{{ $this->getRecord()->customer->address ?? '---' }}</p>
                </div>
            </div>

            <!-- Items Table for Invoice -->
            <div class="mb-6">
                <div class="overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="text-right py-2 px-4 font-medium text-gray-600 text-sm border border-gray-400">
                                    مسلسل</th>
                                <th
                                    class="text-right py-2 px-4 font-medium text-gray-600 text-sm border border-gray-400">
                                    المنتج</th>
                                <th
                                    class="text-center py-2 px-4 font-medium text-gray-600 text-sm border border-gray-400">
                                    الكمية</th>
                                <th
                                    class="text-right py-2 px-4 font-medium text-gray-600 text-sm border border-gray-400">
                                    الخصم</th>
                                <th
                                    class="text-right py-2 px-4 font-medium text-gray-600 text-sm border border-gray-400">
                                    السعر</th>
                                <th
                                    class="text-right py-2 px-4 font-medium text-gray-600 text-sm border border-gray-400">
                                    الإجمالي</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $totalBeforeSale = 0;
                                $totalDiscounts = 0;
                            @endphp
                            @foreach ($this->getRecord()->items as $item)
                                @php
                                    // السعر الأساسي (قبل الخصم)
                                    $lineTotal = $item->price * $item->quantity;
                                    $totalBeforeSale += $lineTotal;

                                    // قيمة الخصم لو موجود
                                    $discountAmount =
                                        $item->product->discount > 0
                                            ? ($item->price * $item->quantity * $item->product->discount) / 100
                                            : 0;

                                    $totalDiscounts += $discountAmount;
                                @endphp

                                <tr class="border-t border-gray-400">
                                    <td class="py-2 px-4 text-right text-gray-500 text-sm border border-gray-400">
                                        {{ $loop->iteration }}
                                    </td>
                                    <td class="py-2 px-4 text-gray-600 text-sm border border-gray-400">
                                        {{ $item->product->name ?? '---' }}
                                    </td>
                                    <td class="py-2 px-4 text-center text-gray-500 text-sm border border-gray-400">
                                        {{ $item->quantity }} {{ $item->product->unit ?? '---' }}
                                    </td>
                                    <td class="py-2 px-4 text-right text-gray-500 text-sm border border-gray-400">
                                        {{ $item->product->discount > 0 ? $item->product->discount . ' %' : '---' }}
                                    </td>
                                    <td class="py-2 px-4 text-right text-gray-500 text-sm border border-gray-400">
                                        {{-- السعر قبل الخصم --}}
                                        {{ number_format($item->price, 2) }}
                                    </td>
                                    <td
                                        class="py-2 px-4 text-right font-medium text-gray-600 text-sm border border-gray-400">
                                        {{-- الإجمالي بعد الخصم --}}
                                        {{ number_format($lineTotal - $discountAmount, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Items Table for Ezn el sarf -->
            @include('filament.pages.invoices.delivery-note')

            <!-- Totals -->
            <div class="flex w-full justify-between mt-2 font-semibold">
                <div class="w-full">
                    <div class="space-y-2">
                        <div class="flex justify-between py-2 px-2">
                            <span class="text-sm text-black">الإجمالي:</span>
                            <span
                                class="font-medium text-sm text-black">{{ number_format($totalBeforeSale, 2) }}</span>
                        </div>
                        <div class="flex justify-between py-2 px-2">
                            <span class="text-sm text-black">الخصومات:</span>
                            <span class="font-medium text-sm text-black">{{ number_format($totalDiscounts, 2) }}</span>
                        </div>
                        <hr class="border-gray-400">
                        <div class="flex justify-between py-3 px-4 rounded-md">
                            <span class="text-base font-medium text-black">الإجمالي بعد الخصم:</span>
                            <span class="text-base font-medium text-black">
                                {{ number_format(
                                    $totalBeforeSale - $totalDiscounts,
                                    2,
                                ) }}
                                ج.م
                            </span>
                        </div>
                    </div>
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
