<x-filament-panels::page>
    <div x-data="{ barcode: '', lastKeyTime: 0 }" x-init="
        window.addEventListener('keydown', function(e) {
            const currentTime = new Date().getTime();
            if (currentTime - lastKeyTime > 100) {
                barcode = '';
            }
            lastKeyTime = currentTime;

            if (e.key === 'Enter') {
                if (barcode) {
                    $wire.set('barcode', barcode);
                    barcode = '';
                }
            } else {
                barcode += e.key;
            }
        });
    ">
        <div class="space-y-6">
            <div class="p-6 bg-white rounded-xl shadow">
                <div class="text-center">
                    <div class="text-lg font-medium text-gray-900">Pemindai Barcode</div>
                    <div class="mt-2 text-sm text-gray-500">
                        Tempatkan kursor di area ini dan scan barcode produk
                    </div>
                </div>
                {{ $this->form }}
            </div>

            <div class="p-6 bg-white rounded-xl shadow">
                <div class="mb-4 text-lg font-medium text-gray-900">Daftar Stok Masuk Hari Ini</div>
                {{ $this->table }}
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:initialized', function () {
            Livewire.on('resetInput', () => {
                document.querySelector('input[name=\'data[barcode]\']').value = '';
            });


        });
    </script>
</x-filament-panels::page>
