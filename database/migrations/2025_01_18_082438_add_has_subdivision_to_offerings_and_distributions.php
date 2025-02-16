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
        Schema::table('offerings', function (Blueprint $table) {
            $table->boolean('has_subdivision')->default(false); // Indica si la ofrenda tiene subdivisiones
        });

        Schema::table('offering_treasury_distributions', function (Blueprint $table) {
            $table->boolean('has_subdivision')->default(false); // Indica si la distribución tiene subdivisiones
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('offerings', function (Blueprint $table) {
            $table->dropColumn('has_subdivision');
        });

        Schema::table('offering_treasury_distributions', function (Blueprint $table) {
            $table->dropColumn('has_subdivision');
        });
    }
};