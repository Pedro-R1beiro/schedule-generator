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
        Schema::table('publishers', function (Blueprint $table) {
            $table->enum('pairing_preference_mode', ['ONLY', 'PRIORITY'])
                  ->default('ONLY')
                  ->after('start_day'); // Coloca após a coluna start_day
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publishers', function (Blueprint $table) {
            $table->dropColumn('pairing_preference_mode');
        });
    }
};
