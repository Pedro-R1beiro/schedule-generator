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
        Schema::create('publishers', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
            $table->string('name', 150); // VARCHAR(150) NOT NULL
            $table->string('phone', 20)->nullable(); // VARCHAR(20) NULL
            $table->boolean('is_active')->default(true); // BOOLEAN NOT NULL DEFAULT TRUE
            $table->boolean('is_manual')->default(false); // BOOLEAN NOT NULL DEFAULT FALSE
            $table->integer('monthly_limit')->default(4); // INT NOT NULL DEFAULT 4
            $table->integer('weekly_limit')->default(2); // INT NOT NULL DEFAULT 2
            $table->boolean('is_pioneer')->default(false); // BOOLEAN NOT NULL DEFAULT FALSE
            $table->enum('gender', ['M', 'F']); // ENUM('M', 'F') NOT NULL
            $table->unsignedTinyInteger('start_day')->default(1); // TINYINT UNSIGNED NOT NULL DEFAULT 1
            $table->timestamps(); // created_at e updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publishers');
    }
};
