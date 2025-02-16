<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pastors', function (Blueprint $table) {
            $table->foreignId('pastor_type_id')->nullable()->constrained('pastor_types')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('pastors', function (Blueprint $table) {
            $table->dropForeign(['pastor_type_id']);
            $table->dropColumn('pastor_type_id');
        });
    }
};

