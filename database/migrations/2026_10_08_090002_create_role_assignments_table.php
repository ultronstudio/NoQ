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
        Schema::create('role_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('role');
            $table->foreignId('virtual_queue_id');
            $table->foreignId('service_point_id')->nullable();
            $table->timestamps();

            $table->unique(
                ['user_id', 'role', 'virtual_queue_id', 'service_point_id'],
                'role_assignments_unique_assignment'
            );
            $table->index(['virtual_queue_id', 'role']);
            $table->index(['service_point_id', 'role']);

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('virtual_queue_id')->references('id')->on('virtual_queues')->cascadeOnDelete();
            $table->foreign('service_point_id')->references('id')->on('service_points')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_assignments');
    }
};
