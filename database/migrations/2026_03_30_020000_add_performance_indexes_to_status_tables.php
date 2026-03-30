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
        Schema::table('service_groups', function (Blueprint $table) {
            $table->index(['order', 'name'], 'service_groups_order_name_index');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->index(['group_id', 'name'], 'services_group_name_index');
            $table->index(['check_enabled', 'last_checked_at'], 'services_check_enabled_last_checked_at_index');
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'incidents_status_created_at_index');
        });

        Schema::table('maintenances', function (Blueprint $table) {
            $table->index(['status', 'scheduled_at'], 'maintenances_status_scheduled_at_index');
        });

        Schema::table('announcements', function (Blueprint $table) {
            $table->index(['is_pinned', 'published_at'], 'announcements_is_pinned_published_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropIndex('announcements_is_pinned_published_at_index');
        });

        Schema::table('maintenances', function (Blueprint $table) {
            $table->dropIndex('maintenances_status_scheduled_at_index');
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->dropIndex('incidents_status_created_at_index');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex('services_group_name_index');
            $table->dropIndex('services_check_enabled_last_checked_at_index');
        });

        Schema::table('service_groups', function (Blueprint $table) {
            $table->dropIndex('service_groups_order_name_index');
        });
    }
};
