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
        Schema::create('publisher_pair_preferences', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
            $table->unsignedBigInteger('requester_publisher_id'); // BIGINT UNSIGNED NOT NULL
            $table->unsignedBigInteger('preferred_publisher_id'); // BIGINT UNSIGNED NOT NULL
            $table->timestamps(); // created_at e updated_at (TIMESTAMP NULL)

            // Definir chaves estrangeiras
            $table->foreign('requester_publisher_id')
                  ->references('id')
                  ->on('publishers')
                  ->onDelete('cascade');

            $table->foreign('preferred_publisher_id')
                  ->references('id')
                  ->on('publishers')
                  ->onDelete('cascade');

            // Adicionar índice único para evitar duplicidade
            $table->unique(
                ['requester_publisher_id', 'preferred_publisher_id'],
                'publisher_preference_unique'
            );

            // Índices para consultas mais rápidas
            $table->index('requester_publisher_id');
            $table->index('preferred_publisher_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publisher_pair_preferences');
    }
};
