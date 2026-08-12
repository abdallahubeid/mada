<?php

namespace App\Domain\Messaging\Support;

use App\Domain\Messaging\Models\Conversation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Writes conversation attachments to the private disk.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * THE STORED NAME IS NEVER THE UPLOADED NAME
 *
 * `original_name` is attacker-controlled and is only ever echoed back as a
 * label. The name on disk is a fresh ULID plus an extension guessed from the
 * file's own contents, so a caller cannot choose where the file lands, cannot
 * collide with an existing file, and cannot smuggle `../` into a path.
 *
 * SVG IS DELIBERATELY NOT AN IMAGE
 *
 * An SVG is a document that can carry script. Served inline from this origin
 * it would run as first-party JavaScript with the viewer's session — a stored
 * XSS delivered through the chat. It is not in IMAGE_MIMES, so it can only be
 * uploaded as a document, and documents are only ever sent with an attachment
 * disposition. See ConversationController::previewAttachment.
 * ─────────────────────────────────────────────────────────────────────────
 */
final class MessageAttachmentStorage
{
    public const DISK = 'chat';

    public const MAX_FILES = 5;

    /** 10 MB per file, in kilobytes — the unit Laravel's `max:` rule uses. */
    public const MAX_KILOBYTES = 10240;

    /**
     * Raster formats only, and the ONLY mime types ever served inline.
     *
     * @var list<string>
     */
    public const IMAGE_MIMES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    /**
     * Accepted extensions, as Laravel's `mimes:` rule expects them.
     *
     * Extension-based rather than a free-for-all: the rule validates the
     * file's real type against the extension, so this is a whitelist of
     * formats rather than a check on the name.
     */
    public const ALLOWED_EXTENSIONS = 'jpeg,jpg,png,gif,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,rar,7z,mp4,webm,mp3,wav,ogg';

    /**
     * The same whitelist, shaped for a file input's `accept` attribute.
     *
     * Derived from ALLOWED_EXTENSIONS rather than written out again, so the
     * dialog's filter and the server's `mimes:` rule cannot drift apart. It is
     * a convenience for the picker only — `accept` is trivially bypassed and
     * decides nothing.
     */
    public static function acceptAttribute(): string
    {
        return '.'.str_replace(',', ',.', self::ALLOWED_EXTENSIONS);
    }

    /**
     * Persist the uploaded files and describe the rows to create.
     *
     * Returns descriptors rather than creating rows itself: the caller writes
     * them inside the same transaction as the message, so a message and its
     * attachment rows can never half-exist.
     *
     * @param  list<UploadedFile>  $files
     * @return list<array<string, mixed>>
     */
    public function put(Conversation $conversation, array $files): array
    {
        $descriptors = [];

        foreach ($files as $file) {
            $mime = $file->getMimeType();

            // Nested per tenant AND per conversation so a stray directory
            // listing is still bounded by the privacy boundary, and so a
            // tenant's files can be removed as a unit.
            $directory = "tenants/{$conversation->tenant_id}/conversations/{$conversation->id}";

            $extension = $file->extension() ?: $file->getClientOriginalExtension();
            $name = (string) Str::ulid();

            if ($extension !== '') {
                $name .= '.'.$extension;
            }

            $path = $file->storeAs($directory, $name, ['disk' => self::DISK]);

            $descriptors[] = [
                'tenant_id' => $conversation->tenant_id,
                'conversation_id' => $conversation->id,
                'disk' => self::DISK,
                'path' => $path,
                // Trimmed to the column width and stripped of any directory
                // component: this is a label, never a path.
                'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255),
                'mime_type' => $mime,
                'size_bytes' => $file->getSize() ?: 0,
                'kind' => self::kindFor($mime),
            ];
        }

        return $descriptors;
    }

    /**
     * Remove files written by a `put()` whose transaction then failed.
     *
     * @param  list<array<string, mixed>>  $descriptors
     */
    public function discard(array $descriptors): void
    {
        foreach ($descriptors as $descriptor) {
            Storage::disk($descriptor['disk'])->delete($descriptor['path']);
        }
    }

    /**
     * Which of the media drawer's two tabs this file belongs in.
     */
    public static function kindFor(?string $mime): string
    {
        return in_array($mime, self::IMAGE_MIMES, true) ? 'image' : 'document';
    }

    /**
     * Whether this file may be sent to the browser inline.
     *
     * Checked against the STORED mime at download time rather than trusting
     * `kind`, so a row edited to claim `image` still cannot get an
     * `text/html` payload rendered in this origin.
     */
    public static function isInlineSafe(?string $mime): bool
    {
        return in_array($mime, self::IMAGE_MIMES, true);
    }
}
