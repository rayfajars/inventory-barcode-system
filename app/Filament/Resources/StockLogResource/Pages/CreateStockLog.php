<?php

namespace App\Filament\Resources\StockLogResource\Pages;

use App\Filament\Resources\StockLogResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateStockLog extends CreateRecord
{
    protected static string $resource = StockLogResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->product->increment('stock', $this->record->quantity);
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
