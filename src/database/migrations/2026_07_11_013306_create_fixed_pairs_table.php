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
        Schema::create('fixed_pairs', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
            $table->unsignedBigInteger('publisher_one_id'); // BIGINT UNSIGNED NOT NULL
            $table->unsignedBigInteger('publisher_two_id'); // BIGINT UNSIGNED NOT NULL
            $table->unsignedBigInteger('weekday_time_slot_id'); // BIGINT UNSIGNED NOT NULL
            $table->timestamps(); // created_at e updated_at (TIMESTAMP NULL)

            // Definir chaves estrangeiras
            $table->foreign('publisher_one_id')
                  ->references('id')
                  ->on('publishers')
                  ->onDelete('cascade');

            $table->foreign('publisher_two_id')
                  ->references('id')
                  ->on('publishers')
                  ->onDelete('cascade');

            $table->foreign('weekday_time_slot_id')
                  ->references('id')
                  ->on('weekday_time_slots')
                  ->onDelete('cascade');

            // Adicionar índice único para evitar duplicidade
            $table->unique(
                ['publisher_one_id', 'publisher_two_id', 'weekday_time_slot_id'],
                'fixed_pairs_unique'
            );

            // Índices para consultas mais rápidas
            $table->index('publisher_one_id');
            $table->index('publisher_two_id');
            $table->index('weekday_time_slot_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixed_pairs');
    }
};
