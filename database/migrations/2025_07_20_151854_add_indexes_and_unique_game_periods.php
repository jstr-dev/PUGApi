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
            $table->unique(['match_slapshot_id', 'player_slapshot_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_periods', function (Blueprint $table) {
            $table->dropUnique(['match_slapshot_id', 'player_slapshot_id']);
        });
    }
};
