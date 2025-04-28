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
            'Product Name',
            'Barcode',
            'Quantity',
            'Processed By',
            'Date',
        ];
    }

    public function map($row): array
    {
        return [
            $row->product->name,
            $row->product->barcode,
            $row->quantity,
            $row->user->name,
            $row->created_at->format('d/m/Y H:i:s'),
        ];
    }
}
