<?php

namespace App\Filament\Pages;

use App\Models\Product;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class LowStockPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-circle';
    protected static ?string $navigationLabel = 'Peringatan Stok Rendah';
    protected static ?string $title = 'Peringatan Stok Rendah';
    protected static ?int $navigationSort = 99;
    protected static string $view = 'filament.pages.low-stock-page';

    public static function getNavigationBadge(): ?string
    {
        return (string) Product::query()->whereRaw('stock <= stock_limit')->count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Product::query()->whereRaw('stock <= stock_limit'))
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nama Produk')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('barcode')->label('Barcode')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('stock')->label('Stok Saat Ini')->sortable()->badge()->color('danger'),
                Tables\Columns\TextColumn::make('stock_limit')->label('Batas Stok')->sortable()->badge()->color('success'),
            ])
            ->defaultSort('stock', 'asc');
    }
}
