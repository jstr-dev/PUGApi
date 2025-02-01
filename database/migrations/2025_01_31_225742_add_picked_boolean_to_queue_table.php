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
            $table->enum('team_picking', ['home', 'away'])->nullable()->after('state');
        });

        Schema::table('queue_users', function (Blueprint $table) {
            $table->enum('team', ['home', 'away'])->after('player_id')->nullable();
            $table->boolean('is_captain')->default(false)->after('team');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('queue', function (Blueprint $table) {
            $table->dropColumn('team_picking');
        });

        Schema::table('queue_users', function (Blueprint $table) {
            $table->dropColumn('is_captain');
            $table->dropColumn('team');
        });
    }
};
