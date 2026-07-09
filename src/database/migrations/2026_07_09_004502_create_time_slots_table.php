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
        Schema::create('time_slots', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
            $table->string('name', 30); // VARCHAR(30) NOT NULL
            $table->time('start_time'); // TIME NOT NULL
            $table->time('end_time'); // TIME NOT NULL
            $table->boolean('is_active')->default(true); // BOOLEAN NOT NULL DEFAULT TRUE
            $table->timestamps(); // created_at e updated_at (TIMESTAMP NULL)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_slots');
    }
};
