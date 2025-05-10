<?php

namespace App\Exports;

use App\Models\StockLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StockInExport implements FromCollection, WithHeadings, WithMapping
{
    protected $records;

    public function __construct($records)
    {
        $this->records = $records;
    }

    public function collection()
    {
        return $this->records;
    }

    public function headings(): array
    {
        return [
            'Barcode',
            'Nama Produk',
            'Harga',
            'Stok',
            'Batas Stok',
        ];
    }

    public function map($row): array
    {
        return [
            $row->product->barcode,
            $row->product->name,
            $row->product->price,
            $row->quantity,
            $row->product->stock_limit,
        ];
    }
}
