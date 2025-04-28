<?php

// app/Exports/StockOutExport.php
namespace App\Exports;

use App\Models\StockLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StockOutExport implements FromCollection, WithHeadings, WithMapping
{
    protected $records;

    public function __construct($records = null)
    {
        $this->records = $records ?? StockLog::where('type', 'out')->get();
    }

    public function collection()
    {
        return $this->records;
    }

    public function headings(): array
    {
        return [
            'Product Name',
            'Barcode',
            'Quantity',
            'Price',
            'Total Price',
            'Date',
        ];
    }

    public function map($row): array
    {
        return [
            $row->product->name,
            $row->product->barcode,
            $row->quantity,
            'Rp ' . number_format($row->product->price, 0, ',', '.'),
            'Rp ' . number_format($row->total_price, 0, ',', '.'),
            $row->created_at->format('d/m/Y H:i:s'),
        ];
    }
}
