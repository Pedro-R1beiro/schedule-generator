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
        Schema::create('weekdays', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
            $table->string('name', 20); // VARCHAR(20) NOT NULL
            $table->unsignedTinyInteger('display_order'); // TINYINT UNSIGNED NOT NULL
            $table->timestamps(); // created_at e updated_at (TIMESTAMP NULL)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekdays');
    }
};
