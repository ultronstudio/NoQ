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
        Schema::create('queue_entry_status_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('queue_entry_id');
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('actor');
            $table->foreignId('changed_by_user_id')->nullable();
            $table->foreignId('service_point_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['queue_entry_id', 'created_at']);
            $table->index('changed_by_user_id');

            $table->foreign('queue_entry_id')->references('id')->on('queue_entries')->cascadeOnDelete();
            $table->foreign('changed_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('service_point_id')->references('id')->on('service_points')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_entry_status_changes');
    }
};
