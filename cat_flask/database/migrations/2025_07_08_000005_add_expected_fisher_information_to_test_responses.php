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
        Schema::table('test_responses', function (Blueprint $table) {
            $table->decimal('expected_fisher_information', 8, 6)->nullable()->after('information')->comment('Expected Fisher Information (EFI)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('test_responses', function (Blueprint $table) {
            $table->dropColumn('expected_fisher_information');
        });
    }
};
