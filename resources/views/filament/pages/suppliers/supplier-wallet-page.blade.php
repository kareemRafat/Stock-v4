<x-filament-panels::page>
    <div class="text-lg font-semibold mb-4">
        اسم المورد : <span class="text-primary-600"> {{ $supplier->name }}</span>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
