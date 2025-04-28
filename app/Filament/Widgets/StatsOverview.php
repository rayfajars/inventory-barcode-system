<?php

// app/Filament/Widgets/StatsOverview.php
namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\StockLog;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today();
        $user = Auth::user();
        $isAdmin = $user->role === 'admin';

        $stats = [];

        // Only show total stock for admin
        if ($isAdmin) {
            $totalStock = Product::sum('stock');
            $stats[] = Stat::make('Total Stock', $totalStock)
                ->description('Total current stock')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary');
        }

        // Base query for today's stock logs
        $stockLogQuery = StockLog::whereDate('created_at', $today);

        // If not admin, only show user's processed records
        if (!$isAdmin) {
            $stockLogQuery->where('user_id', $user->id);
        }

        $todayStockIn = (clone $stockLogQuery)
            ->where('type', 'in')
            ->sum('quantity');

        $todayStockOut = (clone $stockLogQuery)
            ->where('type', 'out')
            ->sum('quantity');

        $todaySales = (clone $stockLogQuery)
            ->where('type', 'out')
            ->sum(DB::raw('quantity * price'));

        $todayProductsSold = (clone $stockLogQuery)
            ->where('type', 'out')
            ->count();

        $stats = array_merge($stats, [
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
        ]);

        return $stats;
    }
}
