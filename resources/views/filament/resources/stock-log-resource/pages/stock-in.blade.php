<x-filament-panels::page>
    <x-filament-panels::form wire:submit="create">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit">
                Process Stock In
            </x-filament::button>
        </div>
    </x-filament-panels::form>
</x-filament-panels::page>
