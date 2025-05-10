<?php

namespace App\Filament\Pages;

use App\Models\Product;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use App\Services\HistoryLogService;

class ImportProductOut extends Page implements HasForms, Tables\Contracts\HasTable
{
    use InteractsWithForms;
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';
    protected static ?string $navigationLabel = 'Import Produk Keluar';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.import-product-out';

    public ?array $data = [];
    public Collection $importResults;

    public function mount(): void
    {
        $this->form->fill();
        $this->importResults = collect();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('file')
                    ->label('File Excel')
                    ->required()
                    ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                    ->maxSize(5120) // 5MB
                    ->directory('temp')
                    ->visibility('private')
                    ->downloadable()
                    ->preserveFilenames()
                    ->disk('local'),
            ])
            ->statePath('data');
    }

    public function import(): void
    {
        $data = $this->form->getState();

        try {
            $filePath = Storage::disk('local')->path($data['file']);

            if (!file_exists($filePath)) {
                Notification::make()
                    ->title('Error')
                    ->body('File tidak ditemukan')
                    ->danger()
                    ->send();
                return;
            }

            $results = collect();
            $rows = Excel::toArray([], $filePath)[0];

            // Skip header row
            array_shift($rows);

            foreach ($rows as $row) {
                $barcode = $row[0] ?? null;
                $name = $row[1] ?? null;
                $price = $row[2] ?? null;
                $stock = $row[3] ?? null;

                if (!$barcode || !$name || !$price || !$stock) {
                    $results->push([
                        'barcode' => $barcode,
                        'name' => $name,
                        'price' => $price,
                        'stock' => $stock,
                        'status' => 'Gagal',
                        'message' => 'Data tidak lengkap'
                    ]);
                    continue;
                }

                // Cari produk berdasarkan barcode
                $product = Product::where('barcode', $barcode)->first();
                if (!$product) {
                    $results->push([
                        'barcode' => $barcode,
                        'name' => $name,
                        'price' => $price,
                        'stock' => $stock,
                        'status' => 'Gagal',
                        'message' => 'Produk tidak ditemukan'
                    ]);
                    continue;
                }

                // Cek stok cukup
                if ($product->stock < $stock) {
                    $results->push([
                        'barcode' => $barcode,
                        'name' => $product->name,
                        'price' => $product->price,
                        'stock' => $stock,
                        'status' => 'Gagal',
                        'message' => 'Stok tidak mencukupi'
                    ]);
                    continue;
                }

                try {
                    // Kurangi stok
                    $product->stock -= $stock;
                    $product->save();

                    // Buat log stok keluar per unit
                    if ($stock > 0) {
                        for ($i = 0; $i < $stock; $i++) {
                            \App\Models\StockLog::create([
                                'product_id' => $product->id,
                                'type' => 'out',
                                'quantity' => 1,
                                'price' => $product->price,
                                'total_price' => $product->price,
                                'user_id' => Auth::id(),
                                'processed_by' => Auth::user()->name,
                            ]);
                        }
                    }

                    HistoryLogService::logImport('product', "Import produk keluar: {$product->name} (jumlah: {$stock} unit)");

                    $results->push([
                        'barcode' => $barcode,
                        'name' => $product->name,
                        'price' => $product->price,
                        'stock' => $stock,
                        'status' => 'Berhasil',
                        'message' => 'Stok berhasil dikurangi'
                    ]);
                } catch (\Exception $e) {
                    $results->push([
                        'barcode' => $barcode,
                        'name' => $product->name,
                        'price' => $product->price,
                        'stock' => $stock,
                        'status' => 'Gagal',
                        'message' => $e->getMessage()
                    ]);
                }
            }

            $this->importResults = $results;

            // Clean up the temporary file
            Storage::disk('local')->delete($data['file']);

            $this->form->fill();

            Notification::make()
                ->title('Import selesai')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                \App\Models\Product::query()
            )
            ->columns([
                Tables\Columns\TextColumn::make('barcode')
                    ->label('Barcode')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Harga')
                    ->money('IDR'),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stok')
                    ->numeric(),
            ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        return $user && $user->role === 'admin';
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && $user->role === 'admin';
    }
}
