<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('game_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('game_lobby_id')->index();
            $table->unsignedBigInteger('slapshot_id')->index();
            $table->enum('team', ['home', 'away']);

            foreach (['score', 'wins', 'losses', 'goals', 'shots', 'shutouts', 'shutouts_against', 'was_mercy_ruled', 'conceded_goals', 'post_hits', 'games_played', 'blocks', 'faceoffs_won', 'faceoffs_lost', 'has_mercy_ruled', 'contributed_goals', 'possession_time_sec', 'saves', 'primary_assists', 'secondary_assists', 'assists', 'turnovers', 'takeaways'] as $column) {
                $table->unsignedInteger($column)->default(0);
            }

            $table->timestamps();
            $table->foreign('game_lobby_id')->references('id')->on('game_lobbies')->onDelete('cascade');
        });

        Schema::create('game_period_ids', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('game_lobby_id')->index();
            $table->json('ids');
            $table->foreign('game_lobby_id')->references('id')->on('game_lobbies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_periods');
        Schema::dropIfExists('game_period_ids');
    }
};
