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
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inviter_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('accepted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('invitee_email');
            $table->string('role');
            $table->string('token', 80)->unique();
            $table->string('status')->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'invitee_email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
