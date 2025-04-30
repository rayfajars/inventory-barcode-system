<x-filament-widgets::widget>
    <div class="space-y-4 w-full">
        <div class="p-4  rounded-xl w-full bg-white dark:bg-gray-900 border-gray-500 border">
            <div class="flex flex-row w-full gap-4">
                <div class="w-full">
                    <label class="block text-sm font-medium  mb-2 text-black dark:text-white" >Pilih Product</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select
                            wire:model.live="selectedProduct"
                            class="w-full bg-gray-800 border-gray-700 text-black dark:text-white "
                        >
                            <option value="">Semua Products</option>
                            @foreach(\App\Models\Product::pluck('name', 'id') as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                <div class="w-full">
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Tanggal Mulai</label>
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="date"
                            wire:model.live="startDate"
                        />
                    </x-filament::input.wrapper>
                </div>

                <div class="w-full ">
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Tanggal Akhir</label>
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="date"
                            wire:model.live="endDate"
                        />
                    </x-filament::input.wrapper>
                </div>
            </div>
        </div>

        <div class="flex flex-row gap-4 w-full">
            @foreach ($this->getStats() as $stat)
                <div class="flex-1">
                    {{ $stat }}
                </div>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
