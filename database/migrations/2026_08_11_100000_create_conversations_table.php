<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Internal messenger — conversation threads (direct and group).
 *
 * ─────────────────────────────────────────────────────────────────────────
 * ON `tenant_id` BEING HERE AT ALL
 *
 * It is derivable: every participant is a user, and every user has a tenant.
 * It is denormalised onto this table and onto every other table in this
 * feature anyway, because that is what lets `BelongsToTenant` apply its global
 * scope uniformly (ADR-02). Isolation that depends on remembering a join is
 * isolation that eventually leaks; isolation that lives in a global scope on
 * every table cannot be forgotten at a call site.
 *
 * ON `participants_hash`
 *
 * A unique index on (conversation, user) in the participants table stops one
 * user joining a thread twice. It does NOT stop two DIRECT threads existing
 * between the same pair — if both people press "message" simultaneously, both
 * requests find no existing thread and both create one, and the pair ends up
 * with a split history that neither can fully see.
 *
 * The hash is a deterministic fingerprint of the sorted participant ids, so
 * the database refuses the second thread rather than the application trying to
 * win a race it cannot see. Null for groups, which may legitimately repeat the
 * same membership.
 * ─────────────────────────────────────────────────────────────────────────
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            $table->string('type', 16)->default('direct');
            $table->string('title')->nullable();
            $table->string('avatar_path')->nullable();

            // The creator matters for groups: only Managers and Owners may
            // create them (BR-1003), and the audit trail needs to name who did.
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            /*
             * Denormalised so the thread list can order without touching the
             * messages table. Nullable because a group exists from the moment
             * it is created, before anyone has said anything.
             */
            $table->timestamp('last_message_at')->nullable();

            $table->string('participants_hash', 64)->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Thread list: "my tenant's conversations, most recent first".
            $table->index(['tenant_id', 'last_message_at'], 'conversations_tenant_recent_index');

            // One direct thread per pair, enforced by the database.
            $table->unique(['tenant_id', 'participants_hash'], 'conversations_tenant_pair_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
