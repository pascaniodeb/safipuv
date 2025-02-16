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
        Schema::create('offering_treasury_subdivisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')->constrained('offering_treasury_distributions')->onDelete('cascade');
            $table->string('name');
            $table->decimal('percentage', 5, 2); // Porcentaje de la subdivisión
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offering_treasury_subdivisions');
    }
};