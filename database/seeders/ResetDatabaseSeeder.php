<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks
        Schema::disableForeignKeyConstraints();

        // Get all tables except users and migrations
        $tables = DB::select('SELECT name FROM sqlite_master WHERE type="table" AND name NOT IN ("users", "migrations", "sqlite_sequence")');

        // Truncate all tables
        foreach ($tables as $table) {
            DB::table($table->name)->truncate();
        }

        // Enable foreign key checks
        Schema::enableForeignKeyConstraints();

        $this->command->info('Database has been reset successfully!');
    }
}
