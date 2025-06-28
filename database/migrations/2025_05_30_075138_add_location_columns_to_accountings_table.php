<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('accountings', function (Blueprint $table) {
            $table->unsignedBigInteger('region_id')->nullable()->after('treasury_id');
            $table->unsignedBigInteger('district_id')->nullable()->after('region_id');
            $table->unsignedBigInteger('sector_id')->nullable()->after('district_id');
        });
    }

    public function down(): void
    {
        Schema::table('accountings', function (Blueprint $table) {
            $table->dropColumn(['region_id', 'district_id', 'sector_id']);
        });
    }
};