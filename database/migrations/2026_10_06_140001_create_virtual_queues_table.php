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
        Schema::create('virtual_queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->char('public_id', 26);
            $table->string('name', 120);
            $table->string('description', 500)->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique('public_id');
            $table->index('user_id');
            $table->index('archived_at');

            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('virtual_queues');
    }
};
