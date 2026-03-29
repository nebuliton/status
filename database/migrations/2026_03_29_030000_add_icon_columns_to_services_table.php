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
        Schema::table('services', function (Blueprint $table) {
            $table->string('icon_source')->default('auto')->after('check_type');
            $table->string('icon_name')->nullable()->after('icon_source');
            $table->string('icon_path')->nullable()->after('icon_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn([
                'icon_source',
                'icon_name',
                'icon_path',
            ]);
        });
    }
};
