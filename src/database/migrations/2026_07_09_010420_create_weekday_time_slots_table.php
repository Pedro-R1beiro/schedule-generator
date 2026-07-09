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
        Schema::create('weekday_time_slots', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
            $table->unsignedBigInteger('weekday_id'); // BIGINT UNSIGNED NOT NULL
            $table->unsignedBigInteger('time_slot_id'); // BIGINT UNSIGNED NOT NULL
            $table->timestamps(); // created_at e updated_at (TIMESTAMP NULL)

            // Definir chaves estrangeiras
            $table->foreign('weekday_id')
                  ->references('id')
                  ->on('weekdays')
                  ->onDelete('cascade'); // Remove relacionamentos se o dia for deletado

            $table->foreign('time_slot_id')
                  ->references('id')
                  ->on('time_slots')
                  ->onDelete('cascade'); // Remove relacionamentos se o horário for deletado

            // Adicionar índice único para evitar duplicidade
            $table->unique(['weekday_id', 'time_slot_id'], 'weekday_time_slot_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekday_time_slots');
    }
};
