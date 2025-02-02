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
        Schema::create('player_bans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('player_id');
            $table->unsignedBigInteger('admin_id');
            $table->text('reason');
            $table->timestamp('banned_at');
            $table->timestamp('expires_at')->nullable();
            $table->tinyInteger('active')->default(1);

            $table->foreign('player_id')->references('id')->on('players')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('players')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_bans');
    }
};
