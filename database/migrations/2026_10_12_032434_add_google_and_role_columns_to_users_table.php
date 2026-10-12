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
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
            $table->string('google_id')->nullable()->after('name');
            $table->string('avatar', 500)->nullable()->after('email');
            $table->string('role', 30)->default('student')->after('avatar');
            $table->string('account_status', 30)->default('pending_verification')->after('role');
            $table->unique('google_id');
            $table->index(['role', 'account_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->dropIndex(['role', 'account_status']);
            $table->dropColumn(['google_id', 'avatar', 'role', 'account_status']);
            $table->string('password')->nullable(false)->change();
        });
    }
};
