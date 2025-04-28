<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;

trait LogStockFilter
{
    public function scopeFilterByUserRole(Builder $query)
    {
        $user = Filament::auth()->user();

        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }
}
