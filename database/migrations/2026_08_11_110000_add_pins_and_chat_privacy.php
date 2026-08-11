<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Messenger Phase 2 — pinned messages and per-user chat privacy.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * PINS LIVE ON `messages`, NOT IN A JOIN TABLE
 *
 * A message is either pinned in its conversation or it is not — there is no
 * per-user pinning, so the relationship is one-to-one with the message and a
 * separate table would only add a join to every thread render. `pinned_by` is
 * kept because a pin is an act someone performed and the bar names them.
 *
 * ON THE PRIVACY COLUMNS
 *
 * These are the enforcement point for the two toggles, and they are opt-OUT
 * (default false = visible) rather than opt-in, matching what people expect
 * from a workplace messenger and what the rest of the product already does.
 *
 * `chat_hide_read_receipts` is deliberately symmetric in effect: a user who
 * hides their read state also stops seeing others'. Anything else lets someone
 * take without giving, which is the design every major messenger converged on
 * after trying the alternative.
 *
 * `last_seen_at` is stored even though live presence is not yet wired, so the
 * column exists for the presence work to populate rather than needing another
 * migration against a table this size.
 * ─────────────────────────────────────────────────────────────────────────
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->timestamp('pinned_at')->nullable()->after('sent_at');
            $table->foreignId('pinned_by')->nullable()->after('pinned_at')
                ->constrained('users')->nullOnDelete();
        });

        Schema::table('messages', function (Blueprint $table) {
            // "The pinned messages of this thread" — a partial concern served
            // by a composite index, since pinned rows are a tiny minority.
            $table->index(['conversation_id', 'pinned_at'], 'messages_conversation_pinned_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('chat_hide_last_seen')->default(false)->after('is_active');
            $table->boolean('chat_hide_read_receipts')->default(false)->after('chat_hide_last_seen');
            $table->timestamp('last_seen_at')->nullable()->after('chat_hide_read_receipts');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_conversation_pinned_index');
            $table->dropConstrainedForeignId('pinned_by');
            $table->dropColumn('pinned_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['chat_hide_last_seen', 'chat_hide_read_receipts', 'last_seen_at']);
        });
    }
};
