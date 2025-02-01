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
            $table->enum('state', [
                'waiting',
                'picking',
                'finished',
            ])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('queue', function (Blueprint $table) {
            \DB::table('queue')->where('state', 'finished')->update(['state' => 'picking']);
            $table->enum('state', [
                'waiting',
                'picking',
            ])->change();
        });
    }
};
