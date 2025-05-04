<?php

// app/Filament/Resources/ProductResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Models\StockLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use App\Services\HistoryLogService;
use Illuminate\Database\Eloquent\Collection;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Manajemen Inventori';
    protected static ?int $navigationSort = 2;

    protected static array $oldData = [];

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Nama'),
                Forms\Components\TextInput::make('barcode')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->label('Barcode'),
                Forms\Components\TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->minValue(0)
                    ->label('Harga'),
                Forms\Components\TextInput::make('stock')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->disabled()
                    ->minValue(0)
                    ->label('Stok'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Nama'),
                Tables\Columns\TextColumn::make('barcode')
                    ->searchable()
                    ->sortable()
                    ->label('Barcode'),
                Tables\Columns\TextColumn::make('price')
                    ->money('IDR')
                    ->sortable()
                    ->label('Harga'),
                Tables\Columns\TextColumn::make('stock')
                    ->numeric()
                    ->sortable()
                    ->label('Stok'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Dibuat pada'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Diperbarui pada'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('stockIn')
                    ->label('Stok Masuk')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('quantity')
                            ->label('Jumlah')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(1),
                    ])
                    ->action(function (Product $record, array $data): void {
                        $quantity = $data['quantity'];

                        // Update product stock
                        $record->increment('stock', $quantity);

                        // Create individual stock logs
                        for ($i = 0; $i < $quantity; $i++) {
                            \App\Models\StockLog::create([
                                'product_id' => $record->id,
                                'type' => 'in',
                                'quantity' => 1,
                                'user_id' => Auth::id(),
                                'processed_by' => Auth::user()->name,
                            ]);
                        }

                        Notification::make()
                            ->title('Stok berhasil ditambahkan')
                            ->success()
                            ->send();

                        HistoryLogService::logStockChange('stock_in', $record->name, $quantity);
                    }),

                Tables\Actions\Action::make('stockOut')
                    ->label('Stok Keluar')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('danger')
                    ->form([
                        Forms\Components\TextInput::make('quantity')
                            ->label('Jumlah')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(1),
                    ])
                    ->action(function (Product $record, array $data): void {
                        $quantity = $data['quantity'];

                        // Check if stock is sufficient
                        if ($record->stock < $quantity) {
                            Notification::make()
                                ->title('Stok tidak mencukupi')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Update product stock
                        $record->decrement('stock', $quantity);

                        // Create individual stock logs
                        for ($i = 0; $i < $quantity; $i++) {
                            \App\Models\StockLog::create([
                                'product_id' => $record->id,
                                'type' => 'out',
                                'quantity' => 1,
                                'price' => $record->price,
                                'total_price' => $record->price,
                                'user_id' => Auth::id(),
                                'processed_by' => Auth::user()->name,
                            ]);
                        }

                        Notification::make()
                            ->title('Stok berhasil dikurangi')
                            ->success()
                            ->send();

                        HistoryLogService::logStockChange('stock_out', $record->name, $quantity);
                    }),

                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->visible(fn () => Auth::user()->role === 'admin'),

                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->visible(fn () => Auth::user()->role === 'admin')
                    ->modalDescription('Produk yg dihapus tidak akan bisa dikembalikan kembali dan akan menghapus seluruh history dari stok masuk dan stok keluar')
                    ->after(function (Product $record) {
                        HistoryLogService::logProductChange('delete', $record->name);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih')
                        ->visible(fn () => Auth::user()->role === 'admin')
                        ->after(function (Collection $records) {
                            foreach ($records as $record) {
                                HistoryLogService::logProductChange('delete', $record->name);
                            }
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true; // Allow both admin and karyawan to access
    }

    public static function canAccess(): bool
    {
        return true; // Allow both admin and karyawan to access
    }

    public static function logProductChange($oldData, $newData, $action)
    {
        // Implementation of logProductChange method
    }
}
