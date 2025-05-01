<?php

// app/Filament/Resources/StockLogResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\StockLogResource\Pages;
use App\Models\StockLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Exports\StockOutExport;
use App\Exports\StockInExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Response;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class StockLogResource extends Resource
{
    protected static ?string $model = StockLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?int $navigationSort = 3;
    protected static ?string $modelLabel = 'Log Stok';
    protected static ?string $pluralModelLabel = 'Log Stok';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('barcode')
                    ->label('Pindai Barcode')
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($state) {
                            $product = \App\Models\Product::where('barcode', $state)->first();
                            if ($product) {
                                $set('product_id', $product->id);
                            }
                        }
                    }),
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($state) {
                            $product = \App\Models\Product::find($state);
                            if ($product) {
                                $set('quantity', 1);
                            }
                        }
                    }),
                Forms\Components\Select::make('type')
                    ->options([
                        'in' => 'Stok Masuk',
                        'out' => 'Stok Keluar',
                    ])
                    ->required()
                    ->live(),
                Forms\Components\TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->default(1)
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        if ($get('type') === 'out' && $state) {
                            $product = \App\Models\Product::find($get('product_id'));
                            if ($product) {
                                $set('total_price', $product->price * $state);
                            }
                        }
                    }),
                Forms\Components\TextInput::make('total_price')
                    ->numeric()
                    ->prefix('Rp')
                    ->visible(fn (Forms\Get $get) => $get('type') === 'out'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                $user = Auth::user();
                if ($user->role !== 'admin') {
                    $query->where('user_id', $user->id);
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
              ->dateTime('d/m/Y H:i')
                ->sortable()
                ->label('Tanggal'),
                Tables\Columns\TextColumn::make('product.name')
                    ->searchable()
                    ->sortable()
                    ->label('Nama Produk'),
                Tables\Columns\TextColumn::make('product.barcode')
                    ->searchable()
                    ->sortable()
                    ->label('Barcode'),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'in' => 'success',
                        'out' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'in' => 'Stok Masuk',
                        'out' => 'Stok Keluar',
                    })
                    ->label('Tipe'),
                Tables\Columns\TextColumn::make('quantity')
                    ->numeric()
                    ->sortable()
                    ->label('Jumlah'),
                Tables\Columns\TextColumn::make('price')
                    ->money('IDR')
                    ->sortable()
                    ->label('Harga'),
                Tables\Columns\TextColumn::make('processed_by')
                    ->label('Diproses Oleh')
                    ->searchable()
                    ->sortable(),

            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'in' => 'Stok Masuk',
                        'out' => 'Stok Keluar',
                    ])
                    ->label('Tipe'),
                Tables\Filters\SelectFilter::make('product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Produk'),
                Tables\Filters\SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Diproses Oleh')
                    ->visible(fn () => Auth::user()->role === 'admin'),
                Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Tanggal Mulai'),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Tanggal Selesai'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['start_date'],
                                fn ($query, $date) => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['end_date'],
                                fn ($query, $date) => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->label('Tanggal'),
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('Ekspor Stok Keluar')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function (Tables\Actions\Action $action) {
                        $table = $action->getTable();

                        // Get the base query with all filters applied
                        $query = $table->getQuery();

                        // Force the type to be 'out' for stock out export
                        $query->where('type', 'out');

                        $records = $query->get();

                        return Excel::download(
                            new StockOutExport($records),
                            'stock-out-report.xlsx'
                        );
                    }),
                Tables\Actions\Action::make('exportIn')
                    ->label('Ekspor Stok Masuk')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function (Tables\Actions\Action $action) {
                        $table = $action->getTable();

                        // Get the base query with all filters applied
                        $query = $table->getQuery();

                        // Force the type to be 'in' for stock in export
                        $query->where('type', 'in');

                        $records = $query->get();

                        return Excel::download(
                            new StockInExport($records),
                            'stock-in-report.xlsx'
                        );
                    }),
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
            'index' => Pages\ListStockLogs::route('/'),
            'stock-in' => Pages\StockIn::route('/stock-in'),
            'stock-out' => Pages\StockOut::route('/stock-out'),
        ];
    }
}
