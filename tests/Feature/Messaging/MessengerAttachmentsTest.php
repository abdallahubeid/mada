<?php

use App\Domain\Messaging\Actions\DeleteMessageAction;
use App\Domain\Messaging\Actions\SendMessageAction;
use App\Domain\Messaging\Actions\StartDirectConversationAction;
use App\Domain\Messaging\Exceptions\MessagingException;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Messaging\Models\Message;
use App\Domain\Messaging\Models\MessageAttachment;
use App\Domain\Messaging\Support\MessageAttachmentStorage;
use App\Domain\Tenancy\Actions\SeedDefaultTenantRoles;
use App\Domain\Tenancy\Enums\EmployeeStatus;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantContext;
use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/*
 * Attachments — upload, membership-gated serving, and revocation on delete.
 *
 * Builds its own fixtures rather than borrowing `messagingTenant()` /
 * `staffMember()` from MessengerPhaseOneTest, so this file can be run on its
 * own. Those helpers are plain functions declared inside another test file,
 * which means they only exist when Pest happens to load that file too — fine
 * for a full-suite run, a confusing "undefined function" when you want just
 * these tests.
 */

/** A colleague with a login and an employee record, in the given tenant. */
function attachmentStaff(Tenant $tenant, string $name): User
{
    $user = User::factory()->create(['tenant_id' => $tenant->id, 'name' => $name]);

    Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => EmployeeStatus::Active,
    ]);

    return $user;
}

/** A tenant with an Owner, ready for messaging. */
function attachmentTenant(): array
{
    $tenant = Tenant::factory()->active()->create();
    app(SeedDefaultTenantRoles::class)->handle($tenant);
    app(TenantContext::class)->setTenant($tenant);

    $owner = attachmentStaff($tenant, 'أليس');
    $owner->assignRole(TenantPermissionCatalog::ROLE_OWNER);

    return [$tenant, $owner->fresh()];
}

/**
 * A tenant, two colleagues who share a thread, and a third who does not.
 *
 * @return array{0: Tenant, 1: User, 2: User, 3: User, 4: Conversation}
 */
function attachmentFixture(): array
{
    [$tenant, $alice] = attachmentTenant();

    $bob = attachmentStaff($tenant, 'بوب');
    $outsider = attachmentStaff($tenant, 'زميل خارج المحادثة');

    $thread = app(StartDirectConversationAction::class)->handle($alice, $bob->id);

    return [$tenant, $alice, $bob, $outsider, $thread];
}

beforeEach(function () {
    Storage::fake(MessageAttachmentStorage::DISK);
});

// ---------------------------------------------------------------------------
// Upload
// ---------------------------------------------------------------------------

test('sending a message with a file writes it to the private disk and records it', function () {
    [$tenant, $alice, , , $thread] = attachmentFixture();

    $this->actingAs($alice)
        ->post(route('tenant.messenger.send', $thread->id), [
            'body' => 'التقرير مرفق',
            'attachments' => [UploadedFile::fake()->create('report.pdf', 120, 'application/pdf')],
        ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('attachments.0.kind', 'document')
        ->assertJsonPath('attachments.0.name', 'report.pdf');

    $attachment = MessageAttachment::query()->firstOrFail();

    expect($attachment->disk)->toBe(MessageAttachmentStorage::DISK)
        ->and($attachment->conversation_id)->toBe($thread->id)
        ->and($attachment->tenant_id)->toBe($tenant->id)
        ->and($attachment->kind)->toBe('document');

    Storage::disk(MessageAttachmentStorage::DISK)->assertExists($attachment->path);

    // The name on disk is ours, not the uploader's — `original_name` is only
    // ever a label, so a crafted filename cannot steer the write.
    expect($attachment->path)->not->toContain('report.pdf')
        ->and($attachment->path)->toStartWith("tenants/{$tenant->id}/conversations/{$thread->id}/");
});

test('an image is classified as an image and a document is not', function () {
    [, $alice, , , $thread] = attachmentFixture();

    app(SendMessageAction::class)->handle($thread, $alice, '', null, [
        UploadedFile::fake()->create('photo.png', 40, 'image/png'),
        UploadedFile::fake()->create('notes.txt', 2, 'text/plain'),
    ]);

    $kinds = MessageAttachment::query()->pluck('kind', 'original_name');

    expect($kinds['photo.png'])->toBe('image')
        ->and($kinds['notes.txt'])->toBe('document');
});

test('a file with no caption is a valid message', function () {
    [, $alice, , , $thread] = attachmentFixture();

    $message = app(SendMessageAction::class)->handle($thread, $alice, '', null, [
        UploadedFile::fake()->create('holiday.jpg', 30, 'image/jpeg'),
    ]);

    // The old rule rejected an empty body outright, which would have made
    // "send a photo" impossible.
    expect($message->body)->toBe('')
        ->and($message->attachments()->count())->toBe(1);
});

test('a message with neither text nor a file is still rejected', function () {
    [, $alice, , , $thread] = attachmentFixture();

    expect(fn () => app(SendMessageAction::class)->handle($thread, $alice, '   '))
        ->toThrow(MessagingException::class);
});

test('the send endpoint rejects an oversized file, a banned type, and too many files', function () {
    [, $alice, , , $thread] = attachmentFixture();

    $oversized = MessageAttachmentStorage::MAX_KILOBYTES + 1;

    $tooBig = $this->actingAs($alice)
        ->post(route('tenant.messenger.send', $thread->id), [
            'body' => 'كبير',
            'attachments' => [UploadedFile::fake()->create('huge.pdf', $oversized, 'application/pdf')],
        ], ['Accept' => 'application/json'])
        ->assertStatus(422);

    // Read the bag directly: the per-file rule reports under the literal key
    // "attachments.0", which the dotted-path assertion helpers try to walk
    // as a nested structure.
    expect($tooBig->json('errors'))->toHaveKey('attachments.0');

    // An executable renamed to .pdf: `mimes:` validates the real type, so the
    // extension in the name buys nothing.
    $this->actingAs($alice)
        ->post(route('tenant.messenger.send', $thread->id), [
            'body' => 'خبيث',
            'attachments' => [UploadedFile::fake()->create('payload.exe', 8, 'application/x-msdownload')],
        ], ['Accept' => 'application/json'])
        ->assertStatus(422);

    $tooMany = collect(range(1, MessageAttachmentStorage::MAX_FILES + 1))
        ->map(fn (int $i) => UploadedFile::fake()->create("file-{$i}.pdf", 4, 'application/pdf'))
        ->all();

    $tooManyResponse = $this->actingAs($alice)
        ->post(route('tenant.messenger.send', $thread->id), [
            'body' => 'كثير',
            'attachments' => $tooMany,
        ], ['Accept' => 'application/json'])
        ->assertStatus(422);

    expect($tooManyResponse->json('errors'))->toHaveKey('attachments');

    // Nothing was written by any of the three: validation runs before the
    // action, so a rejected upload never reaches the disk.
    expect(MessageAttachment::query()->count())->toBe(0)
        ->and(Storage::disk(MessageAttachmentStorage::DISK)->allFiles())->toBeEmpty();
});

// ---------------------------------------------------------------------------
// Download authorisation — the whole point of the private disk
// ---------------------------------------------------------------------------

test('a participant can download an attachment and receives the original filename', function () {
    [, $alice, $bob, , $thread] = attachmentFixture();

    app(SendMessageAction::class)->handle($thread, $alice, 'المرفق', null, [
        UploadedFile::fake()->create('الميزانية.pdf', 20, 'application/pdf'),
    ]);

    $attachment = MessageAttachment::query()->firstOrFail();

    // The RECIPIENT, not the uploader: both sides of the thread may read it.
    $response = $this->actingAs($bob)
        ->get(route('tenant.messenger.attachments.download', $attachment->id))
        ->assertOk();

    expect($response->headers->get('content-disposition'))->toContain('attachment');
});

test('a colleague who is not in the thread gets 404 from both serving routes', function () {
    [, $alice, , $outsider, $thread] = attachmentFixture();

    app(SendMessageAction::class)->handle($thread, $alice, 'سري', null, [
        UploadedFile::fake()->create('private.png', 25, 'image/png'),
    ]);

    $attachment = MessageAttachment::query()->firstOrFail();

    /*
     * 404 rather than 403, matching the rest of the messenger: a 403 confirms
     * the attachment exists, which is itself a disclosure about a thread the
     * caller is not in.
     */
    $this->actingAs($outsider)
        ->get(route('tenant.messenger.attachments.download', $attachment->id))
        ->assertNotFound();

    $this->actingAs($outsider)
        ->get(route('tenant.messenger.attachments.preview', $attachment->id))
        ->assertNotFound();
});

test('an owner of the tenant cannot read a thread they are not in', function () {
    [$tenant, $alice, , , $thread] = attachmentFixture();

    app(SendMessageAction::class)->handle($thread, $alice, 'خاص', null, [
        UploadedFile::fake()->create('payslip.pdf', 10, 'application/pdf'),
    ]);

    $attachment = MessageAttachment::query()->firstOrFail();

    // A second Owner — the role `Gate::before` grants every ability. Access is
    // a participant lookup precisely so that grant cannot reach in here.
    $nosyOwner = attachmentStaff($tenant, 'مالك آخر');
    app(TenantContext::class)->setTenant($tenant);
    $nosyOwner->assignRole(TenantPermissionCatalog::ROLE_OWNER);

    $this->actingAs($nosyOwner->fresh())
        ->get(route('tenant.messenger.attachments.download', $attachment->id))
        ->assertNotFound();
});

test('an attachment belonging to another tenant is invisible', function () {
    [, $alice, , , $thread] = attachmentFixture();

    app(SendMessageAction::class)->handle($thread, $alice, 'ملف', null, [
        UploadedFile::fake()->create('internal.pdf', 10, 'application/pdf'),
    ]);

    $attachment = MessageAttachment::query()->firstOrFail();

    // A whole separate company.
    [, $stranger] = attachmentTenant();

    $this->actingAs($stranger)
        ->get(route('tenant.messenger.attachments.download', $attachment->id))
        ->assertNotFound();
});

test('guests are redirected rather than served', function () {
    [, $alice, , , $thread] = attachmentFixture();

    app(SendMessageAction::class)->handle($thread, $alice, 'ملف', null, [
        UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
    ]);

    $attachment = MessageAttachment::query()->firstOrFail();

    $this->get(route('tenant.messenger.attachments.download', $attachment->id))
        ->assertRedirect(route('login'));
});

// ---------------------------------------------------------------------------
// Inline serving is a security decision, not a presentation one
// ---------------------------------------------------------------------------

test('an image previews inline behind nosniff and a locked-down CSP', function () {
    [, $alice, , , $thread] = attachmentFixture();

    app(SendMessageAction::class)->handle($thread, $alice, '', null, [
        UploadedFile::fake()->create('chart.png', 20, 'image/png'),
    ]);

    $attachment = MessageAttachment::query()->firstOrFail();

    $response = $this->actingAs($alice)
        ->get(route('tenant.messenger.attachments.preview', $attachment->id))
        ->assertOk();

    expect($response->headers->get('content-disposition'))->toContain('inline')
        ->and($response->headers->get('x-content-type-options'))->toBe('nosniff')
        ->and($response->headers->get('content-security-policy'))->toContain("default-src 'none'");
});

test('a non-image is forced to download even when asked for inline', function () {
    [, $alice, , , $thread] = attachmentFixture();

    app(SendMessageAction::class)->handle($thread, $alice, '', null, [
        UploadedFile::fake()->create('contract.pdf', 12, 'application/pdf'),
    ]);

    $attachment = MessageAttachment::query()->firstOrFail();

    /*
     * The preview route is reachable for any id, so it must not be the thing
     * that decides safety. It re-checks the STORED mime and falls back to a
     * download — otherwise a row claiming `kind: image` over an HTML payload
     * would render as first-party script in this origin.
     */
    $response = $this->actingAs($alice)
        ->get(route('tenant.messenger.attachments.preview', $attachment->id))
        ->assertOk();

    expect($response->headers->get('content-disposition'))->toContain('attachment');
});

test('a row that lies about its kind is still not served inline', function () {
    [, $alice, , , $thread] = attachmentFixture();

    app(SendMessageAction::class)->handle($thread, $alice, '', null, [
        UploadedFile::fake()->create('trap.txt', 2, 'text/html'),
    ]);

    $attachment = MessageAttachment::query()->firstOrFail();
    $attachment->forceFill(['kind' => 'image'])->save();

    $response = $this->actingAs($alice)
        ->get(route('tenant.messenger.attachments.preview', $attachment->id))
        ->assertOk();

    expect($response->headers->get('content-disposition'))->toContain('attachment');
});

// ---------------------------------------------------------------------------
// Deletion revokes access
// ---------------------------------------------------------------------------

test('deleting a message soft-deletes its attachments and revokes downloads', function () {
    [, $alice, $bob, , $thread] = attachmentFixture();

    $message = app(SendMessageAction::class)->handle($thread, $alice, 'مرفق مؤقت', null, [
        UploadedFile::fake()->create('draft.pdf', 15, 'application/pdf'),
    ]);

    $attachment = MessageAttachment::query()->firstOrFail();

    $this->actingAs($bob)
        ->get(route('tenant.messenger.attachments.download', $attachment->id))
        ->assertOk();

    app(DeleteMessageAction::class)->handle($message, $alice);

    // `query()->find()`, not `fresh()`: fresh() drops the model's scopes and
    // would happily return the soft-deleted row, proving nothing.
    expect(MessageAttachment::query()->find($attachment->id))->toBeNull()
        ->and(MessageAttachment::withTrashed()->find($attachment->id)->deleted_at)->not->toBeNull();

    // Reachability is revoked for everyone, including the party who could
    // read it a moment ago.
    $this->actingAs($bob)
        ->get(route('tenant.messenger.attachments.download', $attachment->id))
        ->assertNotFound();

    $this->actingAs($alice)
        ->get(route('tenant.messenger.attachments.preview', $attachment->id))
        ->assertNotFound();

    // The bytes survive: soft deletion is for retention, matching the message
    // row itself. Only reachability changes.
    Storage::disk(MessageAttachmentStorage::DISK)->assertExists($attachment->path);
});

// ---------------------------------------------------------------------------
// The disk itself
// ---------------------------------------------------------------------------

test('the chat disk is private, serves no route, and exposes no url', function () {
    $config = config('filesystems.disks.'.MessageAttachmentStorage::DISK);

    expect($config)->not->toBeNull()
        // `serve => true` would register the framework's storage route, which
        // authorises with a SIGNED URL — bearer-style, and blind to
        // conversation membership.
        ->and($config['serve'] ?? false)->toBeFalse()
        // No `url` means Storage::url() throws instead of quietly handing back
        // a working public link for someone to render into a view.
        ->and($config)->not->toHaveKey('url')
        ->and($config['visibility'] ?? 'private')->toBe('private');

    expect(collect(app('router')->getRoutes())->contains(
        fn ($route) => $route->getName() === 'storage.'.MessageAttachmentStorage::DISK
    ))->toBeFalse();
});

test('a non-participant never reaches the disk at all', function () {
    [, , , $outsider, $thread] = attachmentFixture();

    // The membership check is the FIRST thing the action does, so this throws
    // before a single byte is written — the cheap rejection, and the reason
    // the sweep below is a second line of defence rather than the only one.
    expect(fn () => app(SendMessageAction::class)->handle($thread, $outsider, 'تسلل', null, [
        UploadedFile::fake()->create('sneaky.pdf', 10, 'application/pdf'),
    ]))->toThrow(MessagingException::class);

    expect(Storage::disk(MessageAttachmentStorage::DISK)->allFiles())->toBeEmpty();
});

test('a transaction that fails after the upload sweeps the orphaned file', function () {
    [, $alice, , , $thread] = attachmentFixture();

    /*
     * Fails INSIDE the transaction, after the bytes have already been written
     * — the only window in which an orphan can exist. Without the catch in
     * SendMessageAction every failed send would leave a file on disk that no
     * row references and nothing will ever clean up.
     */
    Message::creating(function () {
        throw new RuntimeException('اختبار: فشل بعد رفع الملف');
    });

    try {
        expect(fn () => app(SendMessageAction::class)->handle($thread, $alice, 'مرفق', null, [
            UploadedFile::fake()->create('orphan.pdf', 10, 'application/pdf'),
        ]))->toThrow(RuntimeException::class);
    } finally {
        // Model event listeners are global; leaving this bound would break
        // every test that runs after this one in the same process.
        Message::flushEventListeners();
    }

    expect(Storage::disk(MessageAttachmentStorage::DISK)->allFiles())->toBeEmpty()
        ->and(MessageAttachment::withTrashed()->count())->toBe(0);
});
