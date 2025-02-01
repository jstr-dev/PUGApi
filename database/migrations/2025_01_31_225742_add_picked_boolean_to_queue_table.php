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
            $table->unsignedBigInteger('currently_picking_id')->nullable()->after('current_game_id');

            $table->foreign('currently_picking_id')->references('id')->on('players')->onDelete('cascade');
        });

        Schema::table('queue_users', function (Blueprint $table) {
            $table->tinyInteger('team')->after('player_id')->nullable();
            $table->boolean('is_captain')->default(false)->after('team');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('queue', function (Blueprint $table) {
            $table->dropForeign('queue_currently_picking_id_foreign');
            $table->dropColumn('currently_picking_id');
        });

        Schema::table('queue_users', function (Blueprint $table) {
            $table->dropColumn('is_captain');
            $table->dropColumn('team');
        });
    }
};
