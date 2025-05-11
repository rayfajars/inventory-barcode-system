<?php

namespace App\Exports;

use App\Models\StockLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Database\Eloquent\Builder;

class StockInExport implements FromCollection, WithHeadings, WithMapping
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
        return StockLog::where('type', 'in')->get();
    }

    public function headings(): array
    {
        return [
            'Nama Produk',
            'Barcode',
            'Stock',
            'Harga',
            'Tipe',
            'Processed By',
            'Tanggal',
        ];
    }

    public function map($row): array
    {
        return [
            $row->product->name,
            $row->product->barcode,
            $row->quantity,
            'Rp ' . number_format($row->product->price, 0, ',', '.'),
            $row->type === 'in' ? 'Masuk' : 'Keluar',
            $row->user->name,
            $row->created_at->format('d/m/Y H:i:s'),
        ];
    }
}
