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
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('steam_id');
            $table->string('discord_id');
            $table->string('slapshot_id');
            $table->timestamps();
        });

        Schema::create('queue', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('discord_channel_id');
            $table->timestamps();
        });

        Schema::create('queue_users', function (Blueprint $table) {
            $table->string('queue_id');
            $table->unsignedBigInteger('player_id');
            $table->timestamps();

            $table->foreign('player_id')->references('id')->on('players')->onDelete('cascade');
            $table->foreign('queue_id')->references('id')->on('queue')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_users');
        Schema::dropIfExists('queue');
        Schema::dropIfExists('players');
    }
};
