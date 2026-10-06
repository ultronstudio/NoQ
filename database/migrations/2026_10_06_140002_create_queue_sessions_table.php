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
        Schema::create('queue_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('virtual_queue_id');
            $table->foreignId('opened_by_user_id');
            $table->foreignId('closed_by_user_id')->nullable();
            $table->string('status');
            $table->unsignedInteger('next_number')->default(1);
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index('virtual_queue_id');
            $table->index(['virtual_queue_id', 'status']);

            $table->foreign('virtual_queue_id')->references('id')->on('virtual_queues')->cascadeOnDelete();
            $table->foreign('opened_by_user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('closed_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_sessions');
    }
};
