<?php

namespace App\Filament\Resources\HistoryLogResource\Pages;

use App\Filament\Resources\HistoryLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHistoryLog extends EditRecord
{
    protected static string $resource = HistoryLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
