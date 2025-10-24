<div id="delivery-note-area" class="hidden bg-white p-6 rounded-lg shadow-sm ring-1 ring-gray-200">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start mb-4">
        <div class="mb-4">
            <h1 class="text-3xl font-bold text-primary-600 mb-4">إذن صرف</h1>
            <div class="text-gray-500 text-sm space-y-1">
                <p class="text-base font-medium text-gray-700">شركة أحمد حسين</p>
                <p>لمواد التعبئة والتغليف</p>
            </div>
        </div>

        <div class="mt-4 sm:mt-0 sm:text-right">
            <div
                class="flex items-stretch text-primary-900 px-4 py-1.5 rounded-md mb-3 text-sm font-medium justify-end gap-4">

                <!-- Invoice Number -->
                <div class="flex items-center flex-col">
                    <span class="mb-2">رقم الفاتورة :</span>
                    <span> # {{ $record->invoice_number }}</span>
                </div>

                <!-- Vertical Separator -->
                <div style="width: 1px; height: auto; border-right: 1px solid rgb(111, 111, 111); margin: 0 2px;">
                </div>

                <!-- Date -->
                <div class="flex items-center flex-col">
                    <span class="mr-1 mb-2">التاريخ:</span>
                    <span>{{ $record->createdDate }}</span>
                    <span dir="ltr">{{ $this->record->createdTime }}</span>
                </div>

            </div>
            <div class="flex px-4 mt-4 pt-4 pb-2 mb-2 text-sm font-medium justify-end gap-4">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 font-bold">
                    <path fill-rule="evenodd"
                        d="M1.5 4.5a3 3 0 0 1 3-3h1.372c.86 0 1.61.586 1.819 1.42l1.105 4.423a1.875 1.875 0 0 1-.694 1.955l-1.293.97c-.135.101-.164.249-.126.352a11.285 11.285 0 0 0 6.697 6.697c.103.038.25.009.352-.126l.97-1.293a1.875 1.875 0 0 1 1.955-.694l4.423 1.105c.834.209 1.42.959 1.42 1.82V19.5a3 3 0 0 1-3 3h-2.25C8.552 22.5 1.5 15.448 1.5 6.75V4.5Z"
                        clip-rule="evenodd" />
                </svg>
                <p>01016011318</p>
                {{-- <span> - </span>
                <p>01030231321</p> --}}
            </div>
        </div>

    </div>
    <!-- Separator -->
    <hr class="my-6 border-gray-400">

    <!-- Bill To Section -->
    <div class="mb-6">
        <h3 class="text-base font-medium text-gray-700 my-4 ">طبعت اذن الصرف لأمر :</h3>
        <div class="bg-gray-50 p-3 rounded-md border border-gray-400 my-4 flex flex-col sm:flex-row justify-between">
            <p class="font-medium text-gray-700">{{ $record->customer->name ?? '-' }}</p>
            <p class="text-gray-700 text-sm">{{ $record->customer->address ?? '---' }}</p>
        </div>
    </div>
    <div>
        <table class="w-full border border-gray-400">
            <thead class="bg-gray-100">
                <tr>
                    <th class="text-right py-2 px-4 font-medium text-sm border border-gray-400 text-gray-700">مسلسل</th>
                    <th class="text-right py-2 px-4 font-medium text-sm border border-gray-400 text-gray-700">المنتج</th>
                    <th class="text-right py-2 px-4 font-medium text-sm border border-gray-400 text-gray-700">الكمية</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($record->items as $item)
                    <tr>
                        <td class="text-right py-2 px-4 text-sm border border-gray-400 text-gray-700">
                            {{ $loop->iteration }}
                        </td>
                        <td class="text-right py-2 px-4 text-sm border border-gray-400 text-gray-700">
                            {{ $item->product->name ?? '---' }}</td>
                        <td class="text-right py-2 px-4 text-sm border border-gray-400 text-gray-700">
                            {{ $item->quantity }}
                            {{ $item->product->unit ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
