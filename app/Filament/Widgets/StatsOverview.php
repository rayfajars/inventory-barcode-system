<?php

// app/Filament/Widgets/StatsOverview.php
namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\StockLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today();

        $totalStock = Product::sum('stock');
        $todayStockIn = StockLog::where('type', 'in')
            ->whereDate('created_at', $today)
            ->sum('quantity');
        $todayStockOut = StockLog::where('type', 'out')
            ->whereDate('created_at', $today)
            ->sum('quantity');
        $todaySales = StockLog::where('type', 'out')
            ->whereDate('created_at', $today)
            ->sum(DB::raw('quantity * price'));

        $todayProductsSold = StockLog::where('type', 'out')
            ->whereDate('created_at', $today)
            ->count();

        return [
            Stat::make('Total Stock', $totalStock)
                ->description('Total current stock')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),
            Stat::make('Today Stock In', $todayStockIn)
                ->description('Products added today')
                ->descriptionIcon('heroicon-m-arrow-down-tray')
                ->color('success'),
            Stat::make('Today Stock Out', $todayStockOut)
                ->description('Products sold today')
                ->descriptionIcon('heroicon-m-arrow-up-tray')
                ->color('danger'),
            Stat::make('Today Sales', 'Rp ' . number_format($todaySales, 0, ',', '.'))
                ->description("{$todayProductsSold} products sold")
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),
        ];
    }
}
