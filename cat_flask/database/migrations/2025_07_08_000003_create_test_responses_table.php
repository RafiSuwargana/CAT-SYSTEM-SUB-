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
        Schema::create('test_responses', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 100);
            $table->string('item_id', 50);
            $table->tinyInteger('answer')->comment('0 = incorrect, 1 = correct');
            $table->decimal('theta_before', 8, 6)->comment('Theta before this response');
            $table->decimal('theta_after', 8, 6)->comment('Theta after this response');
            $table->decimal('se_after', 8, 6)->comment('SE after this response');
            $table->integer('item_order')->comment('Order of item in test');
            $table->decimal('probability', 8, 6)->nullable()->comment('P(answer=1|theta)');
            $table->decimal('information', 8, 6)->nullable()->comment('Item information');
            $table->timestamps();
            
            $table->foreign('session_id')->references('session_id')->on('test_sessions')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('item_parameters');
            
            $table->index(['session_id', 'item_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_responses');
    }
};
