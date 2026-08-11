<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Messenger messages.
 *
 * Created BEFORE conversation_participants on purpose: the participants table
 * holds a foreign key to `messages.id` for the read watermark, so this table
 * has to exist first.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * ON `parent_id` (reply-to)
 *
 * nullOnDelete rather than cascade. Deleting a message that others replied to
 * must not delete their replies — the thread would lose unrelated content
 * because one person removed theirs. An orphaned reply renders as a normal
 * message with a "الرسالة الأصلية محذوفة" stub.
 *
 * ON `metadata`
 *
 * JSON, holding per-type detail: nothing for text, and for system messages the
 * actor and subject of the event ("أضاف فلان فلاناً إلى المجموعة"). File
 * detail lives in `message_attachments` rather than here — a message can carry
 * several files, and a JSON blob cannot be indexed for the media gallery.
 * ─────────────────────────────────────────────────────────────────────────
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();

            /*
             * cascadeOnDelete would erase a departed employee's side of every
             * conversation, leaving the other party a thread of their own
             * replies answering nothing. Users are soft-deleted anyway, so this
             * only fires on a hard delete.
             */
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('type', 16)->default('text');
            $table->text('body')->nullable();

            $table->foreignId('parent_id')->nullable()
                ->constrained('messages')->nullOnDelete();

            $table->json('metadata')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            /*
             * The hot path. Scrollback pages by keyset — `where conversation_id
             * = ? and id < ? order by id desc limit n` — never by OFFSET, which
             * degrades linearly and would be felt on a busy thread within weeks.
             * This index serves that, the unread count, and the thread's most
             * recent message.
             */
            $table->index(['conversation_id', 'id'], 'messages_conversation_keyset_index');

            // Unread counting excludes the reader's own messages.
            $table->index(['conversation_id', 'sender_id', 'id'], 'messages_conversation_sender_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
