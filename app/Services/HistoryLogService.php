<?php

namespace App\Services;

use App\Models\HistoryLog;
use Illuminate\Support\Facades\Auth;

class HistoryLogService
{
    public static function log(
        string $action,
        string $module,
        string $description
    ): HistoryLog {
        return HistoryLog::create([
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'user_id' => Auth::id(),
        ]);
    }

    public static function logProductChange($action, $productName)
    {
        return self::log(
            $action,
            'product',
            "Produk {$action}: {$productName}"
        );
    }

    public static function logStockChange($action, $productName, $quantity)
    {
        return self::log(
            $action,
            'stock',
            "Stok {$action}: {$productName} ({$quantity} unit)"
        );
    }

    public static function logUserChange($action, $userName)
    {
        return self::log(
            $action,
            'user',
            "Pengguna {$action}: {$userName}"
        );
    }

    public static function logImport($module, $description)
    {
        return self::log(
            'import',
            $module,
            $description
        );
    }
}
