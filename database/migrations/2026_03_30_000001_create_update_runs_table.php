<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('update_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('triggered_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('mode', 20);
            $table->string('status', 20);
            $table->string('local_version')->nullable();
            $table->string('target_version')->nullable();
            $table->string('local_commit', 64)->nullable();
            $table->string('target_commit', 64)->nullable();
            $table->json('changed_files')->nullable();
            $table->text('summary')->nullable();
            $table->longText('log_output')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('update_runs');
    }
};
