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
        Schema::create('treasury_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offering_report_id')->constrained()->onDelete('cascade');
            $table->foreignId('treasury_id')->constrained()->onDelete('cascade');
            $table->foreignId('offering_category_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 12, 2); // Monto distribuido
            $table->decimal('percentage', 5, 2); // Porcentaje aplicado
            $table->string('month', 7)->default(date('Y-m')); // ✅ Establece un valor por defecto
            $table->string('remarks')->nullable(); // Observaciones o comentarios (opcional)
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treasury_allocations');
    }
};