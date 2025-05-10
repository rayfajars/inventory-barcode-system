<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LowStockAlert extends BaseWidget
{
    protected static ?string $heading = 'Peringatan Stok Rendah';
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Product::query()
            ->whereRaw('stock <= stock_limit')
            ->exists();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->whereRaw('stock <= stock_limit')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('barcode')
                    ->label('Barcode')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('stock')
                    ->label('Stok Saat Ini')
                    ->sortable()
                    ->badge()
                    ->color('danger'),
                TextColumn::make('stock_limit')
                    ->label('Batas Stok')
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn (Product $record): string =>
                        "{$record->stock} / {$record->stock_limit}"
                    ),
            ])
            ->defaultSort('stock', 'asc');
    }
}
