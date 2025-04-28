<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\StockLog;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Support\Facades\Auth;

class StockOut extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationLabel = 'Stok Keluar';

    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.stock-out';

    public ?string $barcode = null;
    public bool $isProcessing = false;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function processStockOut($barcode)
    {
        if (!$barcode) {
            $this->barcode = null;
            $this->dispatch('resetInput');
            return;
        }

        $this->isProcessing = true;

        try {
            $product = Product::where('barcode', $barcode)->first();

            if (!$product) {
                Notification::make()
                    ->title('Produk tidak ditemukan')
                    ->danger()
                    ->send();
                $this->dispatch('resetInput');
                return;
            }

            if ($product->stock <= 0) {
                Notification::make()
                    ->title('Stok tidak mencukupi')
                    ->danger()
                    ->send();
                $this->dispatch('resetInput');
                return;
            }

            // Kurangi stok
            $product->decrement('stock');

            // Buat log stok dengan harga
            StockLog::create([
                'product_id' => $product->id,
                'user_id' => Auth::id(),
                'type' => 'out',
                'quantity' => 1,
                'price' => $product->price,
                'total_price' => $product->price,
            ]);

            Notification::make()
                ->title('Berhasil')
                ->body('Stok berhasil dikurangi')
                ->success()
                ->send();
        } finally {
            $this->isProcessing = false;
            $this->barcode = null;
            $this->dispatch('resetInput');
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('barcode')
                    ->label('Barcode')
                    ->placeholder('Scan barcode disini')
                    ->required()
                    ->live()
                    ->disabled()
                    ->afterStateUpdated(function ($state) {
                        if ($state && !$this->isProcessing) {
                            $this->processStockOut($state);
                        }
                    }),
                Placeholder::make('instruction')
                    ->content(new HtmlString('
                        <div class="text-sm text-gray-500">
                            <p>1. Scan barcode menggunakan scanner</p>
                            <p>2. Stok produk akan otomatis berkurang 1</p>
                            <p>3. Produk akan muncul di tabel bawah</p>
                        </div>
                    ')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                StockLog::query()
                    ->join('products', 'stock_logs.product_id', '=', 'products.id')
                    ->join('users', 'stock_logs.user_id', '=', 'users.id')
                    ->select(
                        'products.barcode',
                        'products.name',
                        'users.name as user_name'
                    )
                    ->selectRaw('CONCAT(products.barcode, "-", users.id) as id')
                    ->selectRaw('SUM(stock_logs.quantity) as total_quantity')
                    ->selectRaw('SUM(stock_logs.quantity * stock_logs.price) as total_price')
                    ->where('stock_logs.type', 'out')
                    ->whereDate('stock_logs.created_at', today())
                    ->when(Auth::user()->role !== 'admin', function ($query) {
                        $query->where('stock_logs.user_id', Auth::id());
                    })
                    ->groupBy('products.barcode', 'products.name', 'users.name', 'users.id')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('barcode')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_quantity')
                    ->label('Total Stok Keluar')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total Harga')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user_name')
                    ->label('Diproses Oleh')
                    ->searchable()
                    ->sortable(),
            ])
            ->defaultSort('total_quantity', 'desc')
            ->paginated(false);
    }
}
