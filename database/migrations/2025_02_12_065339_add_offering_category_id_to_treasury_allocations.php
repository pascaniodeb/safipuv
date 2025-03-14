<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('treasury_allocations', function (Blueprint $table) {
            $table->unsignedBigInteger('offering_category_id')->after('treasury_id')->nullable();
        });
    }

    public function down()
    {
        Schema::table('treasury_allocations', function (Blueprint $table) {
            $table->dropColumn('offering_category_id');
        });
    }
};