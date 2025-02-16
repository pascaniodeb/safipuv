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
        Schema::create('offering_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offering_category_id')->constrained()->onDelete('cascade');
            $table->foreignId('source_treasury_id')->constrained('treasuries')->onDelete('cascade'); // Tesorería de origen
            $table->foreignId('target_treasury_id')->constrained('treasuries')->onDelete('cascade'); // Tesorería destino
            $table->decimal('percentage', 5, 2); // Porcentaje de distribución
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offering_distributions');
    }
};