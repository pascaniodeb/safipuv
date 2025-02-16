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
        Schema::create('regions', function (Blueprint $table) {
            $table->id(); // No necesitas especificar 'id' ni unsigned()
            $table->string('name', 80); // No es necesario nullable() si lo vas a usar
            $table->integer('number')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps(); // Ya incluye created_at y updated_at como timestamps nullable()
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};
