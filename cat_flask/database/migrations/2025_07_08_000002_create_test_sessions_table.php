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
        Schema::create('test_sessions', function (Blueprint $table) {
            $table->string('session_id', 100)->primary();
            $table->decimal('theta', 8, 6)->default(0.0)->comment('Current ability estimate');
            $table->decimal('standard_error', 8, 6)->default(1.0)->comment('Standard error of theta');
            $table->boolean('test_completed')->default(false);
            $table->string('stop_reason')->nullable();
            $table->decimal('final_score', 8, 2)->nullable();
            $table->timestamps();
            
            $table->index(['test_completed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_sessions');
    }
};
