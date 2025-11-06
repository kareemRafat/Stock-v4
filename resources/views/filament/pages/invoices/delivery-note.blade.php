<div id="delivery-note-area" class="hidden bg-white p-6 rounded-lg shadow-sm ring-1 ring-gray-200">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-center">
        <div class="mb-2">
            <h1 class="text-3xl font-bold text-primary-900 mb-4">فاتورة</h1>
            <div class="text-black text-sm space-y-1">
                <p class="text-base font-medium text-black mb-2">شركة أحمد حسين</p>
                <p>لمواد التعبئة والتغليف</p>
            </div>
            <div class="flex mt-1 text-sm font-medium gap-1">
                <span class="text-sm">موبايل :</span>
                <span>01016011318</span>
            </div>
        </div>

        <div class="mt-4 sm:mt-0 sm:text-right">
            <div
                class="flex items-stretch text-primary-900 px-4 py-1.5 rounded-md mb-2 text-sm font-medium justify-end gap-4">
                <div class="flex items-center flex-col">
                    <span class="mb-2">رقم الفاتورة :</span>
                    <span># {{ $this->getRecord()->invoice_number }}</span>
                </div>
                <div style="width: 1px; height: auto; border-right: 1px solid rgb(111, 111, 111); margin: 0 2px;">
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

    <!-- Bill To Section -->
    <div class="mb-6">
        {{-- <h3 class="text-base font-medium text-gray-700 my-4 ">طبعت اذن الصرف لأمر :</h3> --}}
        <div
            class="bg-gray-50 px-3 py-2 rounded-sm border border-gray-400 my-4 flex flex-col sm:flex-row justify-between items-center">
            <p class="text-black">{{ $this->getRecord()->customer->name ?? '-' }}</p>
            <div class="flex flex-row gap-5">
                <p class="text-black text-sm flex gap-3">
                    <x-heroicon-s-map-pin class="w-4 h-4" />
                    {{ $this->getRecord()->customer->address ?? '---' }}
                </p>
                <div style="width: 1px; height: auto; border-right: 1px solid rgb(111, 111, 111); margin: 0 2px;">
                </div>
                <p class="text-black text-sm flex gap-3">
                    <x-heroicon-s-phone class="w-4 h-4" />
                    {{ $this->getRecord()->customer->phone ?? '---' }}
                </p>

            </div>
        </div>
    </div>
    <div>
        <table class="w-full border border-gray-400">
            <thead class="bg-gray-100">
                <tr>
                    <th class="text-right py-2 px-4 font-medium text-xs border border-gray-400 text-gray-700">مسلسل</th>
                    <th class="text-right py-2 px-4 font-medium text-xs border border-gray-400 text-gray-700">المنتج
                    </th>
                    <th class="text-right py-2 px-4 font-medium text-xs border border-gray-400 text-gray-700">الكمية
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($record->items as $item)
                    <tr>
                        <td class="text-right py-2 px-4 text-xs border border-gray-400 text-gray-700">
                            {{ $loop->iteration }}
                        </td>
                        <td class="text-right py-2 px-4 text-xs border border-gray-400 text-gray-700">
                            {{ $item->product->name ?? '---' }}</td>
                        <td class="text-right py-2 px-4 text-xs border border-gray-400 text-gray-700">
                            {{ $item->quantity }}
                            {{ $item->product->unit ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
