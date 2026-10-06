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
        Schema::create('queue_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('queue_session_id');
            $table->foreignId('service_point_id')->nullable();
            $table->char('tracking_token_hash', 64);
            $table->unsignedInteger('number');
            $table->string('status');
            $table->timestamps();

            $table->unique(['queue_session_id', 'number']);
            $table->unique('tracking_token_hash');
            $table->index(['queue_session_id', 'status', 'number']);

            $table->foreign('queue_session_id')->references('id')->on('queue_sessions')->cascadeOnDelete();
            $table->foreign('service_point_id')->references('id')->on('service_points')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_entries');
    }
};
