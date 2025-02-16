<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('church_pastor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->onDelete('cascade'); // Iglesia
            $table->foreignId('pastor_id')->constrained()->onDelete('cascade'); // Pastor
            $table->unsignedTinyInteger('pastor_type_id'); // Rol del pastor (1: Titular, 2: Adjunto, etc.)
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('church_pastor');
    }
};