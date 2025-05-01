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
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms;

class StatsOverview extends BaseWidget
{
    protected static string $view = 'filament.widgets.stats-overview-widget-with-filters';

    protected int | string | array $columnSpan = 'full';

    // Add properties for filters
    public $startDate;
    public $endDate;
    public $selectedProduct = '';  // Empty string for "All Products"
    public $selectedUser = '';     // Add this line for user filter

    public function mount()
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    protected function getStats(): array
    {
        $user = Auth::user();
        $isAdmin = $user->role === 'admin';

        $stats = [];

        // Only show total stock for admin
        if ($isAdmin) {
            $totalStock = Product::when($this->selectedProduct, function ($query) {
                    $query->where('id', $this->selectedProduct);
                })->sum('stock');

            $stats[] = Stat::make('Total Product', $totalStock)
                ->description('Sisa produk yang tersedia')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary');
        }

        // Base query for stock logs within date range
        $stockLogQuery = StockLog::query();

        if ($this->startDate) {
            $stockLogQuery->whereDate('created_at', '>=', Carbon::parse($this->startDate));
        }

        if ($this->endDate) {
            $stockLogQuery->whereDate('created_at', '<=', Carbon::parse($this->endDate));
        }

        if ($this->selectedProduct) {
            $stockLogQuery->where('product_id', $this->selectedProduct);
        }

        // If not admin, only show user's processed records
        if ($isAdmin && $this->selectedUser) {
            $stockLogQuery->where('user_id', $this->selectedUser);
        } elseif (!$isAdmin) {
            // If not admin, only show user's processed records
            $stockLogQuery->where('user_id', $user->id);
        }

        // Calculate previous period for comparison
        $currentPeriodDays = Carbon::parse($this->startDate)->diffInDays(Carbon::parse($this->endDate)) + 1;
        $previousPeriodStart = Carbon::parse($this->startDate)->subDays($currentPeriodDays);
        $previousPeriodEnd = Carbon::parse($this->startDate)->subDay();

        // Get previous period data
        $previousQuery = (clone $stockLogQuery)
            ->whereDate('created_at', '>=', $previousPeriodStart)
            ->whereDate('created_at', '<=', $previousPeriodEnd);

        // Current period calculations
        $totalStockIn = (clone $stockLogQuery)
            ->where('type', 'in')
            ->sum('quantity');

        $totalStockOut = (clone $stockLogQuery)
            ->where('type', 'out')
            ->sum('quantity');

        $totalSales = (clone $stockLogQuery)
            ->where('type', 'out')
            ->sum(DB::raw('quantity * price'));

        // Previous period calculations
        $previousSales = (clone $previousQuery)
            ->where('type', 'out')
            ->sum(DB::raw('quantity * price'));

        // Calculate percentage changes
        $salesChange = $previousSales != 0
            ? round((($totalSales - $previousSales) / $previousSales) * 100)
            : 0;

        // Format the sales change indicator
        // $salesChangeColor = $salesChange >= 0 ? 'success' : 'danger';
        // $salesChangeIcon = $salesChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
        // $salesChangeText = abs($salesChange) . 'k ' . ($salesChange >= 0 ? 'increase' : 'decrease');

        $stats = array_merge($stats, [

            Stat::make('Stok masuk', $totalStockIn)
                ->description('Total stok masuk')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('success')
                ->extraAttributes([
                    'class' => 'ring-1 ring-gray-800',
                ]),
            Stat::make('Stok keluar', $totalStockOut)
                ->description('Total stok keluar')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('danger')
                ->extraAttributes([
                    'class' => 'ring-1 ring-gray-800',
                ]),
                Stat::make('Total Penjualan', 'Rp ' . number_format($totalSales, 0, ',', '.'))
                // ->description($salesChangeText)
                // ->descriptionIcon($salesChangeIcon)
                // ->color($salesChangeColor)
                ->description('Total penjualan')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('info')
                ->extraAttributes([
                    'class' => 'ring-1 ring-gray-800',
                ]),
        ]);

        return $stats;
    }
}
