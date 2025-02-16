<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pastors', function (Blueprint $table) {
            $table->foreignId('church_id')->nullable()->constrained('churches')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('pastors', function (Blueprint $table) {
            $table->dropForeign(['church_id']);
            $table->dropColumn('church_id');
        });
    }
};

