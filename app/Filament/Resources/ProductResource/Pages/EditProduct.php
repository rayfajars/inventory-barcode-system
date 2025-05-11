<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Services\HistoryLogService;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected array $oldData = [];

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function beforeSave(): void
    {
        $this->oldData = $this->record->toArray();
    }

    protected function afterSave(): void
    {
        $newData = $this->record->fresh()->toArray();
        $changes = [];

        if ($this->oldData['name'] !== $newData['name']) {
            $changes[] = "Nama: {$this->oldData['name']} → {$newData['name']}";
        }
        if ($this->oldData['barcode'] !== $newData['barcode']) {
            $changes[] = "Barcode: {$this->oldData['barcode']} → {$newData['barcode']}";
        }
        if ($this->oldData['price'] != $newData['price']) {
            $changes[] = "Harga: Rp" . number_format($this->oldData['price'], 0, ',', '.') . " → Rp" . number_format($newData['price'], 0, ',', '.');
        }

        if (!empty($changes)) {
            HistoryLogService::log(
                'update',
                'product',
                "Produk diubah: {$newData['name']} (" . implode(', ', $changes) . ")"
            );
        }
    }
}
