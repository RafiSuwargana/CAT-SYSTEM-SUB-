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
        Schema::create('item_parameters', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->decimal('a', 8, 6)->comment('Discrimination parameter');
            $table->decimal('b', 8, 6)->comment('Difficulty parameter');
            $table->decimal('g', 8, 6)->comment('Guessing parameter');
            $table->decimal('u', 8, 6)->default(1.0)->comment('Upper asymptote');
            $table->timestamps();
            
            $table->index(['b']); // For efficient item selection
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_parameters');
    }
};
