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
        Schema::create('offering_distribution_subdivisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')->constrained('offering_distributions')->onDelete('cascade');
            $table->string('subdivision_name'); // Ejemplo: "Evangelismo", "Construcción"
            $table->decimal('percentage', 5, 2); // Porcentaje dentro de la tesorería
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offering_distribution_subdivisions');
    }
};