<x-filament-panels::page>
    <div class="text-lg font-semibold mb-4">
          اسم العميل : <span class="text-primary-600"> {{ $customer->name }}</span>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
