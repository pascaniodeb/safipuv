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
        Schema::create('offering_treasury_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offering_id')->constrained('offerings')->onDelete('cascade');
            $table->foreignId('treasury_id')->constrained('treasuries')->onDelete('cascade');
            $table->decimal('percentage', 5, 2); // Porcentaje de distribución
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offering_treasury_distributions');
    }
};