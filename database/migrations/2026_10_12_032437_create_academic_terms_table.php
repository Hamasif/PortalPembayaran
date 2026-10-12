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
        Schema::create('academic_terms', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year', 9); // contoh: 2025/2026
            $table->string('semester', 10);     // ganjil | genap
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 15)->default('upcoming');
            $table->timestamps();

            $table->unique(['academic_year', 'semester']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_terms');
    }
};
