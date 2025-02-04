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
        Schema::create('player_statistics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('player_id');
            $table->string('queue_id');
            $table->integer('elo');
            $table->timestamps();

            $table->index(['player_id', 'queue_id']);

            $table->foreign('player_id')->references('id')->on('players')->onDelete('cascade');
            $table->foreign('queue_id')->references('id')->on('queue')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_statistics');
    }
};
