<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tenant portal contact inbox — threads grouped by sender_email + messages with receipts.
     */
    public function up(): void
    {
        Schema::create('tenant_contact_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('sender_name');
            $table->string('sender_email');
            $table->string('subject');
            $table->string('status', 16)->default('open')->index();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'sender_email']);
            $table->index(['tenant_id', 'status', 'last_message_at']);
        });

        Schema::create('tenant_contact_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('tenant_contact_thread_id')->constrained('tenant_contact_threads')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sender_role', 16);
            $table->string('sender_name');
            $table->text('body');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_contact_thread_id', 'created_at'], 'tcm_thread_created_idx');
            $table->index(['tenant_id', 'delivered_at'], 'tcm_tenant_delivered_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_contact_messages');
        Schema::dropIfExists('tenant_contact_threads');
    }
};
