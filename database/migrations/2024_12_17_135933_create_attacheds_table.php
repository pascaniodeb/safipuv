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
        Schema::create('attached', function (Blueprint $table) {
            $table->id(); // ID opcional, según tus necesidades
            $table->unsignedBigInteger('region_id'); // FK hacia regions
            $table->unsignedBigInteger('state_id'); // FK hacia states
            $table->timestamps(); // Opcional: para crear las columnas created_at y updated_at

            // Relación de clave foránea
            $table->foreign('region_id')->references('id')->on('regions')->onDelete('cascade');
            $table->foreign('state_id')->references('id')->on('states')->onDelete('cascade');

            // Índice único para evitar duplicados en la relación
            $table->unique(['region_id', 'state_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attacheds');
    }
};
