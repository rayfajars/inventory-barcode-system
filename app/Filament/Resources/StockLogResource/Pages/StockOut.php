<?php

namespace App\Filament\Resources\StockLogResource\Pages;

use App\Filament\Resources\StockLogResource;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput\Mask;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Auth;

class StockOut extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = StockLogResource::class;

    protected static string $view = 'filament.resources.stock-log-resource.pages.stock-out';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('barcode')
                    ->label('Pindai Barcode')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, $set) {
                        if ($state) {
                            $product = \App\Models\Product::where('barcode', $state)->first();
                            if ($product) {
                                $set('product_id', $product->id);
                                $set('quantity', 1);
                                $set('price', $product->price);
                            }
                        }
                    }),
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required()
                    ->live()
                    ->label('Produk'),
                TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->default(1)
                    ->live()
                    ->label('Jumlah')
                    ->afterStateUpdated(function ($state, $get, $set) {
                        if ($state) {
                            $product = \App\Models\Product::find($get('product_id'));
                            if ($product) {
                                $set('total_price', $product->price * $state);
                            }
                        }
                    }),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->live()
                    ->label('Harga'),
                TextInput::make('total_price')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->disabled()
                    ->label('Total Harga'),
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $product = \App\Models\Product::find($data['product_id']);

        if ($product->stock < $data['quantity']) {
            $this->notify('error', 'Stok tidak mencukupi');
            return;
        }

        $stockLog = \App\Models\StockLog::create([
            'product_id' => $data['product_id'],
            'type' => 'out',
            'quantity' => $data['quantity'],
            'price' => $data['price'],
            'total_price' => $data['total_price'],
            'user_id' => Auth::id(),
            'processed_by' => Auth::user()->name,
        ]);

        $product->decrement('stock', $data['quantity']);

        $this->form->fill();

        $this->notify('success', 'Stok keluar berhasil diproses');
    }
}
