<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Archiving and hiding a conversation — both PER PARTICIPANT.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * NEITHER OF THESE TOUCHES THE CONVERSATION ITSELF
 *
 * "حذف المحادثة" cannot mean deleting the row. A thread is shared: destroying
 * it would erase the other person's copy of a conversation they never agreed
 * to lose — in a workplace tool that is someone deleting evidence from a
 * colleague's mailbox.
 *
 * So both columns live on the participant row and mean "for me":
 *
 *   archived_at — drop out of my main list, stay reachable under Archived.
 *   hidden_at   — remove from my list entirely; a new message un-hides it,
 *                 because a thread that stayed hidden while the other person
 *                 kept writing would silently lose me messages.
 *
 * The other participant's row is untouched by either, and no history is
 * destroyed. Genuine destruction of a thread is a retention-policy question,
 * not a button.
 * ─────────────────────────────────────────────────────────────────────────
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('muted_at');
            $table->timestamp('hidden_at')->nullable()->after('archived_at');

            // The thread list filters on these for the current user on every
            // messenger page load.
            $table->index(['user_id', 'archived_at', 'hidden_at'], 'conversation_participants_shelf_index');
        });
    }

    public function down(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->dropIndex('conversation_participants_shelf_index');
            $table->dropColumn(['archived_at', 'hidden_at']);
        });
    }
};
