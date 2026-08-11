<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Emoji reactions on a message.
 *
 * The unique index is the whole design: one row per (message, user, emoji)
 * means reacting is idempotent and un-reacting is a delete, so a double-tap or
 * a replayed socket event cannot inflate a count. A user may apply several
 * different emoji to the same message, which is why `emoji` is part of the key
 * rather than there being one reaction per user.
 *
 * `emoji` is a short string holding the literal character(s), not a shortcode:
 * the column is utf8mb4 and a reaction is data, not presentation, so storing
 * `:+1:` would push rendering rules into every client that reads it. Sized for
 * multi-codepoint sequences — a family emoji or a skin-tone modifier is well
 * over four bytes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('emoji', 32);

            $table->timestamps();

            $table->unique(['message_id', 'user_id', 'emoji'], 'message_reactions_unique');

            // Rendering a page of messages loads every reaction for them.
            $table->index(['message_id', 'emoji'], 'message_reactions_message_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_reactions');
    }
};
