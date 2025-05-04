<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('history_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action'); // The type of action (create, update, delete, etc.)
            $table->string('module'); // The module/feature being affected (product, user, stock, etc.)
            $table->text('description'); // Detailed description of the action
            $table->foreignId('user_id')->constrained('users'); // Who performed the action
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('history_logs');
    }
};
