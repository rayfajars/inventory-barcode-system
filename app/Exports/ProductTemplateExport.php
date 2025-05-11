<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class ProductTemplateExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return new Collection(); // Return koleksi kosong
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
}