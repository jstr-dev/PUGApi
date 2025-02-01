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
        Schema::table('queue', function (Blueprint $table) {
            $table->enum('state', ['waiting', 'picking'])->after('discord_channel_id')->default('waiting');
            $table->unsignedBigInteger('current_game_id')->nullable()->after('state');

            $table->foreign('current_game_id')->references('id')->on('game_lobbies')->onDelete('cascade');
        });

        Schema::table('game_lobbies', function (Blueprint $table) {
            $table->enum('state', ['waiting', 'playing', 'finished'])->after('password')->default('waiting');
        });

        Schema::create('game_players', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('game_lobby_id')->index();
            $table->unsignedBigInteger('player_id');
            $table->tinyInteger('team')->default(0);
            $table->boolean('is_captain')->default(false);
            $table->timestamps();

            $table->foreign('game_lobby_id')->references('id')->on('game_lobbies')->onDelete('cascade');
            $table->foreign('player_id')->references('id')->on('players')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_players');

        Schema::table('game_lobbies', function (Blueprint $table) {
            $table->dropColumn('state');
        });

        Schema::table('queue', function (Blueprint $table) {
            $table->dropColumn('state');
            $table->dropForeign('queue_current_game_id_foreign');
            $table->dropColumn('current_game_id');
        });
    }
};
