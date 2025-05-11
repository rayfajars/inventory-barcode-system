<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Database\Eloquent\Builder;

class ProductsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $query;

    public function __construct(?Builder $query = null)
    {
        $this->query = $query;
    }

    public function collection()
    {
        if ($this->query) {
            return $this->query->get();
        }
        return Product::select('name', 'barcode', 'price', 'stock', 'stock_limit')->get();
    }

    public function headings(): array
    {
        return [
            'Nama',
            'Barcode',
            'Harga',
            'Stok',
            'Batas Stok',
        ];
    }

    public function map($product): array
    {
        return [
            $product->name,
            $product->barcode,
            $product->price,
            0, // Set stok ke 0
            $product->stock_limit,
        ];
    }
}