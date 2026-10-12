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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_term_id')->constrained()->restrictOnDelete();
            $table->string('bill_number', 40)->unique();
            $table->string('bill_type', 20)->default('ukt');
            $table->string('description', 255)->nullable();
            $table->unsignedBigInteger('amount'); // nominal rupiah (integer, tanpa desimal)
            $table->date('due_date');
            $table->string('status', 20)->default('unpaid');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Mencegah duplikasi jenis tagihan pada periode yang sama.
            $table->unique(['student_id', 'academic_term_id', 'bill_type'], 'bills_unique_type_per_term');
            $table->index(['status', 'due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
