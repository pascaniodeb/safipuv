<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        //Schema::create('accounting_summaries', function (Blueprint $table) {
            //$table->id();
        
            //$table->foreignId('user_id')->constrained()->onDelete('cascade');
            //$table->foreignId('accounting_id')->constrained('accountings')->onDelete('cascade');
        
            // 💱 Divisa del resumen
            //$table->string('currency', 3); // USD, VES, COP
        
            // 📊 Totales por divisa
            //$table->decimal('total_income', 12, 2)->default(0);
            //$table->decimal('total_expense', 12, 2)->default(0);
            //$table->decimal('saldo', 12, 2)->default(0);
    
            // 🗓️ Periodo contable
            //$table->string('month', 7); // Ej: 2025-01
            //$table->enum('period_type', ['mensual', 'trimestral', 'semestral', 'anual']);
            //$table->string('period_label'); // Ej: 2025-01
        
            // 🧾 Descripción libre
            //$table->string('description', 255)->nullable();
        
            // 📍 Geografía
            //$table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            //$table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            //$table->foreignId('sector_id')->nullable()->constrained()->nullOnDelete();
        
            //$table->boolean('is_closed')->default(false);
        
            //$table->timestamps();
        //});
        
    }


    public function down()
    {
        //Schema::dropIfExists('accounting_summaries');
    }
};