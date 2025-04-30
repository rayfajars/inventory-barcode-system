<x-filament-widgets::widget>
    <div class="space-y-4 w-full">
        <div class="p-4  rounded-xl w-full bg-white dark:bg-gray-900">
            <div class="flex flex-row w-full gap-4">
                <div class="w-full">
                    <label class="block text-sm font-medium  mb-2 text-black dark:text-white" >Business customers only</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select
                            wire:model.live="selectedProduct"
                            class="w-full bg-gray-800 border-gray-700 text-black dark:text-white "
                        >
                            <option value="">All Products</option>
                            @foreach(\App\Models\Product::pluck('name', 'id') as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Tanggal Mulai</label>
                    <x-filament::input
                        type="date"
                        wire:model.live="startDate"
                        class="w-full bg-gray-800 border-gray-700 text-black dark:text-white"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Tanggal Akhir</label>
                    <x-filament::input
                        type="date"
                        wire:model.live="endDate"
                        class="w-full bg-gray-800 border-gray-700 text-black dark:text-white"
                    />
                </div>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4">
            @foreach ($this->getStats() as $stat)
                {{ $stat }}
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
