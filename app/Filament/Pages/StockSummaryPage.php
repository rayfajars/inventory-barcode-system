<?php

namespace App\Filament\Pages;

use App\Models\StockLog;
use App\Exports\StockSummaryExport;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\ExportBulkAction;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;

class StockSummaryPage extends Page implements \Filament\Tables\Contracts\HasTable
{
    use \Filament\Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Rekap Stok';
    protected static ?string $title = 'Rekap Stok';
    protected static string $view = 'filament.pages.stock-summary-page';

    // Tambahkan method ini untuk mengontrol visibility berdasarkan role
    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && $user->role === 'admin';
    }

    public function table(Table $table): Table
    {
        $query = StockLog::select(
            'product_id',
            'products.name as product_name',
            'products.barcode',
            'stock_logs.price',
            DB::raw('CONCAT(products.barcode, "-", stock_logs.price) as record_key'),
            DB::raw('SUM(CASE WHEN type = "in" THEN quantity ELSE 0 END) as stock_in'),
            DB::raw('SUM(CASE WHEN type = "out" THEN quantity ELSE 0 END) as stock_out'),
            DB::raw('SUM(CASE WHEN type = "out" THEN quantity * stock_logs.price ELSE 0 END) as total_penjualan')
        )
        ->join('products', 'stock_logs.product_id', '=', 'products.id')
        ->groupBy('product_id', 'products.name', 'products.barcode', 'stock_logs.price')
        ->orderBy('products.barcode')
        ->orderBy('stock_logs.price');

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Nama Produk')
                    ->searchable(),
                Tables\Columns\TextColumn::make('barcode')
                    ->label('Barcode')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Harga')
                    ->money('IDR'),
                Tables\Columns\TextColumn::make('stock_in')
                    ->label('Stok Masuk'),
                Tables\Columns\TextColumn::make('stock_out')
                    ->label('Stok Keluar'),
                Tables\Columns\TextColumn::make('total_penjualan')
                    ->label('Total Penjualan')
                    ->money('IDR'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('barcode')
                    ->options(
                        StockLog::join('products', 'stock_logs.product_id', '=', 'products.id')
                            ->select('products.barcode')
                            ->distinct()
                            ->pluck('products.barcode', 'products.barcode')
                    ),
                Tables\Filters\SelectFilter::make('product_name')
                    ->options(
                        StockLog::join('products', 'stock_logs.product_id', '=', 'products.id')
                            ->select('products.name')
                            ->distinct()
                            ->pluck('products.name', 'products.name')
                    ),
            ])
            ->headerActions([
                // ExportAction::make()
                //     ->label('Export Excel')
                //     // ->exporter(StockSummaryExport::class)
                //     ->icon('heroicon-o-arrow-down-tray')
                //     ->action(function ($livewire) {
                //         // Get the filtered query from the table
                //         $query = $livewire->getFilteredTableQuery();

                //         return Excel::download(
                //             new StockSummaryExport($query),
                //             'products_export.xlsx'
                //         );
                //     }),

                Tables\Actions\Action::make('export')
                ->label('Export Produk')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function ($livewire) {
                    // Get the filtered query from the table
                    $query = $livewire->getFilteredTableQuery();

                    return Excel::download(
                        new StockSummaryExport($query),
                        'rekap_product.xlsx'
                    );
                }),
            ]);
    }

    public function getTableRecordKey($record): string
    {
        return $record->record_key ?? $record->product_id;
    }
}
