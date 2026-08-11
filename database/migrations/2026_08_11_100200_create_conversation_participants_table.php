<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membership of a conversation — and the sole authority on who may read it.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * THIS TABLE IS THE PRIVACY BOUNDARY
 *
 * Per the agreed policy, employee-to-employee messages are private: an Owner,
 * HR Manager or platform Super Admin has NO read access to a conversation
 * they are not a participant in. There is deliberately no permission that
 * grants blanket visibility, and no `Gate::before` bypass applies — the
 * channel authorizer and every query check for a row in THIS table, not for a
 * role.
 *
 * That is why membership is a table rather than an inferred property: a role
 * can be granted, but a participant row has to be created by joining the
 * conversation, which leaves a record.
 *
 * ON `last_read_message_id` (the read watermark)
 *
 * Read state is one row per participant, not one per message. Opening a
 * 200-message thread writes a single column instead of 200 rows, and the
 * unread count becomes `count(messages) where id > watermark` against an
 * index. The trade is losing per-message read timestamps, which a one-to-one
 * chat never displays.
 * ─────────────────────────────────────────────────────────────────────────
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('role', 16)->default('member');

            /*
             * nullOnDelete, not cascade: deleting the message someone had last
             * read must not delete their membership. The watermark simply
             * falls back to "nothing read", which over-reports unread rather
             * than silently hiding messages.
             */
            $table->foreignId('last_read_message_id')->nullable()
                ->constrained('messages')->nullOnDelete();
            $table->timestamp('last_read_at')->nullable();

            $table->timestamp('muted_at')->nullable();
            $table->timestamp('joined_at')->nullable();

            $table->timestamps();

            // One membership per person per thread.
            $table->unique(['conversation_id', 'user_id'], 'conversation_participants_unique');

            // "My conversations" — the thread-list query, and the channel
            // authorization lookup on every socket subscribe.
            $table->index(['user_id', 'tenant_id'], 'conversation_participants_user_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_participants');
    }
};
