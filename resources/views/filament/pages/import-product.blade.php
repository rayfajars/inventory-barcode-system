<x-filament-panels::page>
    <div class="space-y-6">
        <div class="p-6 bg-white rounded-lg shadow">
            <h2 class="text-lg font-medium">Import Produk</h2>

            <p class="mt-1 text-sm text-gray-600">
                Upload file Excel dengan format: Barcode, Nama, Harga, Stok
            </p>
            <h1 class="text-sm text-blue-600">Hanya untuk Produk Baru</h1>

            <form wire:submit="import" class="mt-4">
                {{ $this->form }}

                <div class="mt-4">
                    <x-filament::button type="submit">
                        Import
                    </x-filament::button>
                </div>
            </form>
        </div>

        @if($importResults->isNotEmpty())
            <div class="p-6 bg-white rounded-lg shadow">
                <h2 class="text-lg font-medium">Hasil Import</h2>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-6 py-3">Barcode</th>
                                <th class="px-6 py-3">Nama</th>
                                <th class="px-6 py-3">Harga</th>
                                <th class="px-6 py-3">Stok</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3">Pesan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($importResults as $result)
                                <tr class="bg-white border-b">
                                    <td class="px-6 py-4">{{ $result['barcode'] }}</td>
                                    <td class="px-6 py-4">{{ $result['name'] }}</td>
                                    <td class="px-6 py-4">Rp {{ number_format($result['price'], 0, ',', '.') }}</td>
                                    <td class="px-6 py-4">{{ $result['stock'] }}</td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $result['status'] === 'Berhasil' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $result['status'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">{{ $result['message'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
