<?php

namespace App\Exports;

use App\Models\StockLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Database\Eloquent\Builder;

class StockSummaryExport implements FromCollection, WithHeadings, WithMapping
{
    protected $query;


    public function __construct(?Builder $query = null)
    {
        $this->query = $query;
    }

    public function collection()
    {
        return $this->query->get();
    }

    public function headings(): array
    {
        return [
            'Nama Produk',
            'Barcode',
            'Harga',
            'Stok Masuk',
            'Stok Keluar',
            'Total Penjualan'
        ];
    }

    public function map($row): array
    {
        return [
            $row->product_name,
            $row->barcode,
            $row->price,
            $row->stock_in,
            $row->stock_out,
            $row->total_penjualan
        ];
    }
}
