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
        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider_event_id', 100)->nullable()->unique();
            $table->string('event_type', 50);
            $table->string('payload_hash', 64)->nullable();
            $table->json('metadata')->nullable(); // metadata aman: tanpa secret/credential
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('event_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_events');
    }
};
