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
        Schema::create('service_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('virtual_queue_id');
            $table->string('name', 80);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['virtual_queue_id', 'name']);
            $table->index(['virtual_queue_id', 'is_active']);

            $table->foreign('virtual_queue_id')->references('id')->on('virtual_queues')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_points');
    }
};
