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

class StockIn extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = StockLogResource::class;

    protected static string $view = 'filament.resources.stock-log-resource.pages.stock-in';

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
                    ->label('Jumlah'),
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $stockLog = \App\Models\StockLog::create([
            'product_id' => $data['product_id'],
            'type' => 'in',
            'quantity' => $data['quantity'],
            'user_id' => Auth::id(),
        ]);

        $stockLog->product->increment('stock', $data['quantity']);

        $this->form->fill();

        $this->notify('success', 'Stok masuk berhasil diproses');
    }
}
