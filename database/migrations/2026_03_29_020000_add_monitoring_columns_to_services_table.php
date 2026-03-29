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
            $table->string('check_type')->default('website')->after('group_id')->index();
            $table->boolean('check_enabled')->default(false)->after('check_type')->index();
            $table->unsignedInteger('check_interval_seconds')->default(60)->after('check_enabled');
            $table->unsignedSmallInteger('timeout_seconds')->default(5)->after('check_interval_seconds');
            $table->string('target_url')->nullable()->after('timeout_seconds');
            $table->string('target_host')->nullable()->after('target_url');
            $table->unsignedInteger('target_port')->nullable()->after('target_host');
            $table->unsignedSmallInteger('expected_status_code')->nullable()->after('target_port');
            $table->boolean('verify_ssl')->default(true)->after('expected_status_code');
            $table->unsignedInteger('latency_degraded_ms')->nullable()->after('verify_ssl');
            $table->unsignedInteger('latency_down_ms')->nullable()->after('latency_degraded_ms');
            $table->string('database_driver')->nullable()->after('latency_down_ms');
            $table->string('database_host')->nullable()->after('database_driver');
            $table->unsignedInteger('database_port')->nullable()->after('database_host');
            $table->string('database_name')->nullable()->after('database_port');
            $table->string('database_username')->nullable()->after('database_name');
            $table->text('database_password')->nullable()->after('database_username');
            $table->text('database_query')->nullable()->after('database_password');
            $table->timestamp('last_checked_at')->nullable()->after('database_query')->index();
            $table->unsignedInteger('last_response_time_ms')->nullable()->after('last_checked_at');
            $table->string('last_check_message')->nullable()->after('last_response_time_ms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex(['check_type']);
            $table->dropIndex(['check_enabled']);
            $table->dropIndex(['last_checked_at']);

            $table->dropColumn([
                'check_type',
                'check_enabled',
                'check_interval_seconds',
                'timeout_seconds',
                'target_url',
                'target_host',
                'target_port',
                'expected_status_code',
                'verify_ssl',
                'latency_degraded_ms',
                'latency_down_ms',
                'database_driver',
                'database_host',
                'database_port',
                'database_name',
                'database_username',
                'database_password',
                'database_query',
                'last_checked_at',
                'last_response_time_ms',
                'last_check_message',
            ]);
        });
    }
};
