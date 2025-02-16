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
        Schema::table('pastor_levels', function (Blueprint $table) {
            $table->boolean('is_vip')->default(false); // Define si el nivel es VIP
        });
    }

    public function down()
    {
        Schema::table('pastor_levels', function (Blueprint $table) {
            $table->dropColumn('is_vip');
        });
    }

};