<x-filament-panels::page>
    <div class="text-lg font-semibold mb-4">
        اسم المورد: <span class="text-primary-600">{{ $supplier->name }}</span>
    </div>

    {{-- total balance --}}
    <div class="border-t border-gray-200 dark:border-gray-500 pt-4 text-base">
        <div>
            <span class="font-medium">
                @if ($supplier->balance > 0)
                    إجمالي المديونية:
                @elseif ($supplier->balance < 0)
                    إجمالي المستحق:
                @else
                    إجمالي الرصيد:
                @endif
            </span>
            <span
                class="{{ $supplier->balance > 0 ? 'text-rose-600' : ($supplier->balance == 0 ? 'text-gray-500' : 'text-success-600') }} font-semibold">
                {{ number_format(abs($supplier->balance), 2) }} ج.م
            </span>
        </div>

        <div class="text-gray-500 dark:text-gray-400 mt-1 text-sm">
            @if ($supplier->balance > 0)
                <span>أنت <strong class="text-rose-600">مدين</strong> للمورد بمبلغ
                    {{ number_format($supplier->balance, 2) }} ج.م (مستحق عليك).</span>
            @elseif ($supplier->balance < 0)
                <span>المورد <strong class="text-success-600">مدين</strong> لك بمبلغ
                    {{ number_format(abs($supplier->balance), 2) }} ج.م (مستحق لك).</span>
            @else
                <span>رصيد المورد متوازن (لا توجد مديونية).</span>
            @endif
        </div>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
