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
        Schema::create('used_items', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 100);
            $table->string('item_id', 50);
            $table->timestamps();
            
            $table->foreign('session_id')->references('session_id')->on('test_sessions')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('item_parameters');
            
            $table->unique(['session_id', 'item_id']); // Prevent duplicate item usage
            $table->index(['session_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('used_items');
    }
};
