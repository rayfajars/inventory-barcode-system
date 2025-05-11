<?php

// app/Filament/Widgets/StatsOverview.php
namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\StockLog;
use App\Models\User;
use Filament\Widgets\TableWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Livewire\Attributes\On;

class StatsOverview extends TableWidget
{
    protected static string $view = 'filament.widgets.stats-overview-widget-with-filters';
    protected static ?string $heading = 'Log Stok';

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

    #[On('filter-updated')]
    public function updateFilters($filters)
    {
        $this->startDate = $filters['startDate'] ?? $this->startDate;
        $this->endDate = $filters['endDate'] ?? $this->endDate;
        $this->selectedProduct = $filters['selectedProduct'] ?? $this->selectedProduct;
        $this->selectedUser = $filters['selectedUser'] ?? $this->selectedUser;
    }

    public function updatedStartDate()
    {
        $this->dispatch('filter-updated', [
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'selectedProduct' => $this->selectedProduct,
            'selectedUser' => $this->selectedUser,
        ]);
    }

    public function updatedEndDate()
    {
        $this->dispatch('filter-updated', [
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'selectedProduct' => $this->selectedProduct,
            'selectedUser' => $this->selectedUser,
        ]);
    }

    public function updatedSelectedProduct()
    {
        $this->dispatch('filter-updated', [
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'selectedProduct' => $this->selectedProduct,
            'selectedUser' => $this->selectedUser,
        ]);
    }

    public function updatedSelectedUser()
    {
        $this->dispatch('filter-updated', [
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'selectedProduct' => $this->selectedProduct,
            'selectedUser' => $this->selectedUser,
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getStockLogsQuery())
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->timezone('Asia/Jakarta')
                    ->sortable(),
                TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.barcode')
                    ->label('Barcode')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'in' => 'success',
                        'out' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'in' => 'Masuk',
                        'out' => 'Keluar',
                    }),
                TextColumn::make('quantity')
                    ->label('Jumlah')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('price')
                    ->label('Harga')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Diproses Oleh')
                    ->searchable()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->striped();
    }

    protected function getStockLogsQuery()
    {
        $user = Auth::user();
        $isAdmin = $user->role === 'admin';

        $query = StockLog::query()
            ->with(['product', 'user']);

        if ($this->startDate) {
            $query->whereDate('created_at', '>=', Carbon::parse($this->startDate));
        }

        if ($this->endDate) {
            $query->whereDate('created_at', '<=', Carbon::parse($this->endDate));
        }

        if ($this->selectedProduct) {
            $query->where('product_id', $this->selectedProduct);
        }

        if ($isAdmin && $this->selectedUser) {
            $query->where('user_id', $this->selectedUser);
        } elseif (!$isAdmin) {
            $query->where('user_id', $user->id);
        }

        return $query;
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
