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

class ImportProduct extends Page implements HasForms, Tables\Contracts\HasTable
{
    use InteractsWithForms;
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static ?string $navigationLabel = 'Import Produk Masuk';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.import-product';

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
                $stockLimit = $row[4] ?? 10; // Default to 10 if not provided

                if (!$barcode || !$name || !$price || !$stock) {
                    $results->push([
                        'barcode' => $barcode,
                        'name' => $name,
                        'price' => $price,
                        'stock' => $stock,
                        'stock_limit' => $stockLimit,
                        'status' => 'Gagal',
                        'message' => 'Data tidak lengkap'
                    ]);
                    continue;
                }

                // Check if barcode exists (including soft deleted)
                $existingProduct = Product::withTrashed()->where('barcode', $barcode)->first();
                if ($existingProduct) {
                    try {
                        if ($existingProduct->trashed()) {
                            // If product was soft deleted, restore it and update stock
                            $existingProduct->restore();
                            $existingProduct->update([
                                'stock' => $stock, // Set to new stock value
                                'name' => $name,
                                'price' => $price,
                                'stock_limit' => $stockLimit
                            ]);
                        } else {
                            // If product exists and not deleted, update name, price and increment stock
                            $existingProduct->update([
                                'name' => $name,
                                'price' => $price,
                                'stock_limit' => $stockLimit
                            ]);
                            $existingProduct->increment('stock', $stock);
                        }

                        // Create individual stock logs for each unit
                        for ($i = 0; $i < $stock; $i++) {
                            \App\Models\StockLog::create([
                                'product_id' => $existingProduct->id,
                                'type' => 'in',
                                'quantity' => 1,
                                'price' => $price,
                                'total_price' => $price,
                                'user_id' => Auth::id(),
                                'processed_by' => Auth::user()->name,
                            ]);
                        }

                        $message = $existingProduct->trashed()
                            ? "Produk berhasil di-restore dan stok diupdate"
                            : "Produk berhasil diupdate dan stok ditambahkan";

                        \App\Services\HistoryLogService::logImport('product', "Update produk: {$name} (jumlah: {$stock} unit)");

                        $results->push([
                            'barcode' => $barcode,
                            'name' => $name,
                            'price' => $price,
                            'stock' => $stock,
                            'stock_limit' => $stockLimit,
                            'status' => 'Berhasil',
                            'message' => $message
                        ]);
                        continue;
                    } catch (\Exception $e) {
                        $results->push([
                            'barcode' => $barcode,
                            'name' => $name,
                            'price' => $price,
                            'stock' => $stock,
                            'stock_limit' => $stockLimit,
                            'status' => 'Gagal',
                            'message' => $e->getMessage()
                        ]);
                        continue;
                    }
                }

                try {
                    $product = Product::create([
                        'barcode' => $barcode,
                        'name' => $name,
                        'price' => $price,
                        'stock' => $stock,
                    ]);

                    // Create individual stock logs for each unit
                    if ($stock > 0) {
                        for ($i = 0; $i < $stock; $i++) {
                            \App\Models\StockLog::create([
                                'product_id' => $product->id,
                                'type' => 'in',
                                'quantity' => 1,
                                'price' => $price,
                                'total_price' => $price,
                                'user_id' => Auth::id(),
                                'processed_by' => Auth::user()->name,
                            ]);
                        }
                    }

                    \App\Services\HistoryLogService::logImport('product', "Import produk masuk: {$name} (jumlah: {$stock} unit)");

                    $results->push([
                        'barcode' => $barcode,
                        'name' => $name,
                        'price' => $price,
                        'stock' => $stock,
                        'stock_limit' => $stockLimit,
                        'status' => 'Berhasil',
                        'message' => 'Produk berhasil diimport'
                    ]);
                } catch (\Exception $e) {
                    $results->push([
                        'barcode' => $barcode,
                        'name' => $name,
                        'price' => $price,
                        'stock' => $stock,
                        'stock_limit' => $stockLimit,
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
