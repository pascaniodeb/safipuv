<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('accounting_transactions', function (Blueprint $table) {
            $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('set null')->after('user_id');
            $table->foreignId('district_id')->nullable()->constrained('districts')->onDelete('set null')->after('region_id');
            $table->foreignId('sector_id')->nullable()->constrained('sectors')->onDelete('set null')->after('district_id');
        });
    }

    public function down()
    {
        Schema::table('accounting_transactions', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropForeign(['district_id']);
            $table->dropForeign(['sector_id']);
            $table->dropColumn(['region_id', 'district_id', 'sector_id']);
        });
    }
};