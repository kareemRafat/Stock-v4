<x-filament-panels::page>
    <form wire:submit.prevent="save">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button
                type="submit"
                color="success"
                wire:loading.attr="disabled"
                wire:target="save"
            >
                <span wire:loading.remove wire:target="save">
                    حفظ
                </span>
                <span wire:loading wire:target="save">
                    جاري الحفظ...
                </span>
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
