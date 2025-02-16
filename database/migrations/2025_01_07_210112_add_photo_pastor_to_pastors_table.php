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
        Schema::table('pastors', function (Blueprint $table) {
            $table->string('photo_pastor')->nullable()->after('email'); // Agrega la columna después del campo 'email'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pastors', function (Blueprint $table) {
            $table->dropColumn('photo_pastor'); // Elimina la columna en caso de revertir
        });
    }
};

