<?php

namespace App\Filament\Resources\HistoryLogResource\Pages;

use App\Filament\Resources\HistoryLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHistoryLogs extends ListRecords
{
    protected static string $resource = HistoryLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No actions needed for history logs
        ];
    }
}
