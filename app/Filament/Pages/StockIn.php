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
use App\Services\HistoryLogService;

class StockIn extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $navigationLabel = 'Stok Masuk';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.stock-in';

    public ?string $barcode = null;
    public bool $isProcessing = false;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function processStockIn($barcode)
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

            // Update product stock
            $product->increment('stock');

            // Create stock log
            StockLog::create([
                'product_id' => $product->id,
                'user_id' => Auth::id(),
                'processed_by' => Auth::user()->name,
                'type' => 'in',
                'quantity' => 1,
            ]);

            HistoryLogService::logStockChange('stock_in', $product->name, 1);

            Notification::make()
                ->title('Berhasil')
                ->body('Stok berhasil ditambahkan')
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
                            $this->processStockIn($state);
                        }
                    }),
                Placeholder::make('instruction')
                    ->content(new HtmlString('
                        <div class="text-sm text-gray-500">
                            <p>1. Scan barcode menggunakan scanner</p>
                            <p>2. Stok produk akan otomatis bertambah 1</p>
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
                        'users.name as user_name',
                        'users.id as user_id'
                    )
                    ->selectRaw('CONCAT(products.barcode, "-", users.id) as id')
                    ->selectRaw('SUM(stock_logs.quantity) as total_quantity')
                    ->where('stock_logs.type', 'in')
                    ->whereDate('stock_logs.created_at', today())
                    ->when(Auth::user()->role !== 'admin', function ($query) {
                        $query->where('stock_logs.user_id', Auth::id());
                    })
                    ->when(Auth::user()->role === 'admin', function ($query) {
                        $query->where('users.role', 'karyawan');
                    })
                    ->groupBy('stock_logs.user_id', 'products.barcode', 'products.name', 'users.name', 'users.id')
                    ->orderBy('stock_logs.user_id')
                    ->orderBy('products.barcode')
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
                    ->label('Total Stok Masuk')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user_name')
                    ->label('Diproses Oleh')
                    ->searchable()
                    ->sortable(),
            ])
            ->defaultSort('user_name', 'asc')
            ->paginated(false);
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        return $user && $user->role === 'karyawan';
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && $user->role === 'karyawan';
    }
}
