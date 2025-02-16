<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('treasury_allocations', function (Blueprint $table) {
            $table->string('month', 7)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('treasury_allocations', function (Blueprint $table) {
            $table->string('month', 7)->nullable(false)->change();
        });
    }
};