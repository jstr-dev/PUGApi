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
        Schema::table('queue', function (Blueprint $table) {
            $table->integer('player_count')->default(8);
            $table->json('picking_order')->default(json_encode(
                ['home', 'away', 'away', 'home', 'home', 'away']
            ));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('queue', function (Blueprint $table) {
            $table->dropColumn('player_count');
            $table->dropColumn('picking_order');
        });
    }
};
