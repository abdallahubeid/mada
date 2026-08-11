<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Files shared inside a conversation.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * A TABLE, NOT A JSON COLUMN ON `messages`
 *
 * The shared-media drawer needs "every image in this conversation, newest
 * first" and "every document in this conversation". Both are index scans here
 * and full table scans with JSON extraction if the same data lives in
 * `messages.metadata`. One message can also carry several files.
 *
 * NOT `HasImages`
 *
 * The existing polymorphic image system serves marketing logos and avatars —
 * public assets on the `custom` disk, served directly by the web server from
 * `public/`. Chat attachments are the opposite: private, tenant-scoped, and
 * only readable by the participants of one conversation. They must sit on a
 * non-public disk behind a controller that re-checks membership on every
 * download, so a leaked URL is worthless to anyone outside the thread.
 *
 * `disk` is stored per row rather than read from config at download time, so
 * changing the default disk later cannot orphan files already written.
 * ─────────────────────────────────────────────────────────────────────────
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();

            $table->string('disk', 32)->default('local');
            $table->string('path');

            // The uploader's filename, kept separate from `path`: it is
            // attacker-controlled and is only ever echoed back as a label.
            $table->string('original_name');
            $table->string('mime_type', 128)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);

            // 'image' | 'document' — the two tabs of the media drawer.
            $table->string('kind', 16)->default('document');

            $table->timestamps();
            $table->softDeletes();

            // The media drawer, per kind, newest first.
            $table->index(['conversation_id', 'kind', 'id'], 'message_attachments_gallery_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_attachments');
    }
};
