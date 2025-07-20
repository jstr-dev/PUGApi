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
        Schema::dropIfExists('game_period_ids');
        Schema::table('game_periods', function (Blueprint $table) {
            $table->renameColumn('slapshot_id', 'match_slapshot_id');
            $table->string('player_slapshot_id')->after('match_slapshot_id')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_periods', function (Blueprint $table) {
            $table->dropIndex(['game_periods_player_slapshot_id_index']);
            $table->renameColumn('match_slapshot_id', 'slapshot_id');
            $table->dropColumn('player_slapshot_id');
        });
    }
};
