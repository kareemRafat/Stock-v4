<x-filament-panels::page>
    <div class="text-lg font-semibold">
        اسم العميل : <span class="text-primary-600"> {{ $customer->name }}</span>
    </div>

    {{-- total customer balance --}}
    <div class="border-t border-gray-200 dark:border-gray-500 pt-4 text-base">
        <div>
            <span class="font-medium">إجمالي الرصيد: </span>
            <span
                class="{{ $customer->balance > 0 ? 'text-rose-600' : ($customer->balance == 0 ? 'text-gray-500' : 'text-success-600') }}  font-semibold">
                {{ number_format(abs($customer->balance), 2) }} ج.م
            </span>
        </div>

        <div class="text-gray-500 dark:text-gray-400 mt-1 text-sm">
            @if ($customer->balance > 0)
                <span>العميل <strong class="text-rose-600">مدين</strong> بمبلغ {{ number_format($customer->balance, 2) }}
                    ج.م
                    (مستحق لك).</span>
            @elseif ($customer->balance < 0)
                <span>لدى العميل <strong class="text-success-600">رصيد دائن</strong> بقيمة
                    {{ number_format(abs($customer->balance), 2) }} ج.م.</span>
            @else
                <span>رصيد العميل متوازن (لا يوجد مديونية أو رصيد دائن).</span>
            @endif
        </div>
    </div>

    {{ $this->table }}


</x-filament-panels::page>
