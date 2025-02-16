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
        Schema::create('offering_report_distributions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('offering_report_id');
            $table->unsignedBigInteger('offering_id');
            $table->unsignedBigInteger('treasury_id');
            $table->decimal('percentage', 5, 2);
            $table->decimal('amount_bs', 12, 2);
            $table->timestamps();
        
            // Claves foráneas
            $table->foreign('offering_report_id')->references('id')->on('offering_reports')->onDelete('cascade');
            $table->foreign('offering_id')->references('id')->on('offerings')->onDelete('cascade');
            $table->foreign('treasury_id')->references('id')->on('treasuries')->onDelete('cascade');
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offering_report_distributions');
    }
};