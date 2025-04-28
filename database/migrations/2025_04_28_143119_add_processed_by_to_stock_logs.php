<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stock_logs', function (Blueprint $table) {
            $table->string('processed_by')->nullable()->after('user_id');
        });

        // Update existing records with user names
        $stockLogs = DB::table('stock_logs')
            ->join('users', 'stock_logs.user_id', '=', 'users.id')
            ->select('stock_logs.id', 'users.name')
            ->get();

        foreach ($stockLogs as $log) {
            DB::table('stock_logs')
                ->where('id', $log->id)
                ->update(['processed_by' => $log->name]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_logs', function (Blueprint $table) {
            $table->dropColumn('processed_by');
        });
    }
};
