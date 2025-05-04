<?php

namespace App\Filament\Resources\HistoryLogResource\Pages;

use App\Filament\Resources\HistoryLogResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateHistoryLog extends CreateRecord
{
    protected static string $resource = HistoryLogResource::class;
}
