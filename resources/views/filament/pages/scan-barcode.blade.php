<x-filament-panels::page>
    <div class="max-w-xl mx-auto">
        <div>
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-2xl font-bold mb-4">{{ __('Barcode Scanner') }}</h2>
                <p class="mb-4">{{ __('Use a barcode scanner or manually enter a product barcode to update inventory.') }}</p>

                <form wire:submit.prevent="processBarcode" class="space-y-6">
                    {{ $this->form }}

                    <div class="flex justify-end">
                        <x-filament::button type="submit" wire:loading.attr="disabled">
                            <span wire:loading.remove>{{ __('Process') }}</span>
                            <span wire:loading>{{ __('Processing...') }}</span>
                        </x-filament::button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-xl font-bold mb-4">{{ __('Scanner Instructions') }}</h3>
                <ol class="list-decimal pl-5 space-y-2">
                    <li>{{ __('Select operation type (Barang Masuk or Barang Keluar)') }}</li>
                    <li>{{ __('Connect your barcode scanner to your device') }}</li>
                    <li>{{ __('Focus on the barcode input field') }}</li>
                    <li>{{ __('Scan a product barcode to automatically submit the form') }}</li>
                    <li>{{ __('You can also type the barcode manually and click Process') }}</li>
                </ol>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const barcodeInput = document.querySelector('[name="data[barcode]"]');
            if (barcodeInput) {
                barcodeInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        @this.processBarcode();
                    }
                });
                barcodeInput.focus();
            }
        });
    </script>
    @endpush
</x-filament-panels::page>
