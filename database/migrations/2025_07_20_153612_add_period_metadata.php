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
        Schema::table('game_periods', function (Blueprint $table) {
            $table->integer('period_number')->default(1);
        });

        Schema::table('game_lobbies', function (Blueprint $table) {
            $table->integer('period_count')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_periods', function (Blueprint $table) {
            $table->dropColumn('period_number');
        });

        Schema::table('game_lobbies', function (Blueprint $table) {
            $table->dropColumn('period_count');
        });
    }
};
