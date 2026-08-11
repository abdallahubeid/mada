<?php

use App\Domain\Messaging\Actions\CreateGroupConversationAction;
use App\Domain\Messaging\Actions\DeleteMessageAction;
use App\Domain\Messaging\Actions\PinMessageAction;
use App\Domain\Messaging\Actions\SendMessageAction;
use App\Domain\Messaging\Actions\StartDirectConversationAction;
use App\Domain\Messaging\Actions\ToggleReactionAction;
use App\Domain\Messaging\Enums\ConversationType;
use App\Domain\Messaging\Enums\MessageType;
use App\Domain\Messaging\Exceptions\MessagingException;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Messaging\Models\Message;
use App\Domain\Messaging\Models\MessageReaction;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantContext;
use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * Phase 2 — groups, reactions, pins and the privacy toggles.
 *
 * Shares `messagingTenant()` and `staffMember()` with MessengerPhaseOneTest;
 * Pest loads both files into the same suite.
 */

/** A staff member holding a specific tenant role. */
function staffWithRole(Tenant $tenant, string $name, string $role): User
{
    $user = staffMember($tenant, $name);
    app(TenantContext::class)->setTenant($tenant);
    $user->assignRole($role);

    return $user->fresh();
}

// ---------------------------------------------------------------------------
// Group creation privilege
// ---------------------------------------------------------------------------

test('a manager can create a group and is its admin', function () {
    [$tenant, $owner] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);

    $manager = staffWithRole($tenant, 'مدير', TenantPermissionCatalog::ROLE_HR_MANAGER);
    $member = staffMember($tenant, 'موظف');

    $group = app(CreateGroupConversationAction::class)->handle($manager, 'فريق التوظيف', [$member->id]);

    expect($group->type)->toBe(ConversationType::Group)
        ->and($group->title)->toBe('فريق التوظيف')
        ->and($group->participants()->count())->toBe(2);

    // The creator is always in the group, and always its admin — a group whose
    // creator could omit themselves would fabricate a thread between others.
    $creatorSide = $group->participants()->where('user_id', $manager->id)->first();
    expect($creatorSide)->not->toBeNull()
        ->and($creatorSide->role)->toBe('admin');
});

test('a regular employee cannot create a group', function () {
    [$tenant, $owner] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);

    $employee = staffWithRole($tenant, 'موظف عادي', TenantPermissionCatalog::ROLE_EMPLOYEE);
    $other = staffMember($tenant, 'زميل');

    expect(fn () => app(CreateGroupConversationAction::class)->handle($employee, 'مجموعة', [$other->id]))
        ->toThrow(MessagingException::class);
});

test('the group route refuses an employee at the middleware', function () {
    [$tenant, $owner] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);

    $employee = staffWithRole($tenant, 'موظف عادي', TenantPermissionCatalog::ROLE_EMPLOYEE);
    $other = staffMember($tenant, 'زميل');

    $this->actingAs($employee)
        ->post(route('tenant.messenger.groups.store'), [
            'title' => 'محاولة',
            'members' => [$other->id],
        ])
        ->assertForbidden();
});

test('a group cannot smuggle in a member from another tenant', function () {
    [$tenantA, $ownerA] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenantA->id, 'user_id' => $ownerA->id]);
    $manager = staffWithRole($tenantA, 'مدير', TenantPermissionCatalog::ROLE_HR_MANAGER);

    [$tenantB] = messagingTenant();
    $outsider = staffMember($tenantB, 'غريب');

    app(TenantContext::class)->setTenant($tenantA);

    // Member ids arrive from a request body; the picker is not a control.
    expect(fn () => app(CreateGroupConversationAction::class)->handle($manager, 'مجموعة', [$outsider->id]))
        ->toThrow(MessagingException::class);
});

test('an employee added to a group participates normally', function () {
    [$tenant, $owner] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);

    $manager = staffWithRole($tenant, 'مدير', TenantPermissionCatalog::ROLE_HR_MANAGER);
    $employee = staffWithRole($tenant, 'موظف', TenantPermissionCatalog::ROLE_EMPLOYEE);

    $group = app(CreateGroupConversationAction::class)->handle($manager, 'فريق', [$employee->id]);

    app(SendMessageAction::class)->handle($group, $employee, 'أهلاً بالفريق');

    $this->actingAs($employee)->get(route('tenant.messenger.show', $group->id))
        ->assertOk()
        ->assertSee('أهلاً بالفريق', false);
});

// ---------------------------------------------------------------------------
// Reactions
// ---------------------------------------------------------------------------

test('reacting is idempotent and toggles off on repeat', function () {
    [$tenant, $owner] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);
    $alice = staffMember($tenant, 'أليس');
    $bob = staffMember($tenant, 'بوب');

    $thread = app(StartDirectConversationAction::class)->handle($alice, $bob->id);
    $message = app(SendMessageAction::class)->handle($thread, $alice, 'رسالة');

    $action = app(ToggleReactionAction::class);

    expect($action->handle($message, $bob, '👍'))->toBeTrue()
        ->and(MessageReaction::query()->count())->toBe(1);

    // A double-tap or a replayed request must not inflate the count.
    expect($action->handle($message, $bob, '👍'))->toBeFalse()
        ->and(MessageReaction::query()->count())->toBe(0);
});

test('only whitelisted emoji are accepted as reactions', function () {
    [$tenant, $owner] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);
    $alice = staffMember($tenant, 'أليس');
    $bob = staffMember($tenant, 'بوب');

    $thread = app(StartDirectConversationAction::class)->handle($alice, $bob->id);
    $message = app(SendMessageAction::class)->handle($thread, $alice, 'رسالة');

    // Free-form input would render into every participant's thread.
    expect(fn () => app(ToggleReactionAction::class)->handle($message, $bob, '<script>alert(1)</script>'))
        ->toThrow(MessagingException::class);
});

test('a non participant cannot react to a message', function () {
    [$tenant, $owner] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);
    $alice = staffMember($tenant, 'أليس');
    $bob = staffMember($tenant, 'بوب');

    $thread = app(StartDirectConversationAction::class)->handle($alice, $bob->id);
    $message = app(SendMessageAction::class)->handle($thread, $alice, 'رسالة');

    // 404, not 403 — a 403 would confirm the message exists.
    $this->actingAs($owner)
        ->postJson(route('tenant.messenger.react', $message->id), ['emoji' => '👍'])
        ->assertNotFound();

    expect(MessageReaction::query()->count())->toBe(0);
});

// ---------------------------------------------------------------------------
// Pins
// ---------------------------------------------------------------------------

test('pinning a second message replaces the first', function () {
    [$tenant, $owner] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);
    $alice = staffMember($tenant, 'أليس');
    $bob = staffMember($tenant, 'بوب');

    $thread = app(StartDirectConversationAction::class)->handle($alice, $bob->id);
    $first = app(SendMessageAction::class)->handle($thread, $alice, 'الأولى');
    $second = app(SendMessageAction::class)->handle($thread, $alice, 'الثانية');

    $action = app(PinMessageAction::class);
    $action->handle($first, $alice);
    $action->handle($second, $bob);

    // One pin per conversation: "pin this instead" is what the gesture means.
    expect($first->fresh()->pinned_at)->toBeNull()
        ->and($second->fresh()->pinned_at)->not->toBeNull()
        ->and(Message::query()->whereNotNull('pinned_at')->count())->toBe(1);
});

test('pinning toggles off and survives a reload', function () {
    [$tenant, $owner] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);
    $alice = staffMember($tenant, 'أليس');
    $bob = staffMember($tenant, 'بوب');

    $thread = app(StartDirectConversationAction::class)->handle($alice, $bob->id);
    $message = app(SendMessageAction::class)->handle($thread, $alice, 'مهم');

    $action = app(PinMessageAction::class);

    expect($action->handle($message, $alice))->toBeTrue()
        ->and(Message::query()->find($message->id)->pinned_by)->toBe($alice->id);

    expect($action->handle($message->fresh(), $alice))->toBeFalse()
        ->and(Message::query()->find($message->id)->pinned_at)->toBeNull();
});

test('a system message cannot be pinned', function () {
    [$tenant, $owner] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);
    $manager = staffWithRole($tenant, 'مدير', TenantPermissionCatalog::ROLE_HR_MANAGER);
    $member = staffMember($tenant, 'عضو');

    $group = app(CreateGroupConversationAction::class)->handle($manager, 'فريق', [$member->id]);
    $system = $group->messages()->where('type', MessageType::System->value)->firstOrFail();

    // Otherwise "أنشأ فلان المجموعة" sits at the top of the thread forever.
    expect(fn () => app(PinMessageAction::class)->handle($system, $manager))
        ->toThrow(MessagingException::class);
});

// ---------------------------------------------------------------------------
// Privacy toggles
// ---------------------------------------------------------------------------

test('a user can hide their last seen and read receipts', function () {
    [$tenant, $owner] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);
    // fresh(), because the defaults are DB-level: Eloquent's create() does not
    // re-read the row, so a just-created instance has no value for a column it
    // did not itself set. Null is falsy, so enforcement behaves correctly
    // either way — but the assertion here is about what was persisted.
    $alice = staffMember($tenant, 'أليس')->fresh();

    expect($alice->chat_hide_last_seen)->toBeFalse()
        ->and($alice->chat_hide_read_receipts)->toBeFalse();

    $this->actingAs($alice)->put(route('tenant.messenger.privacy.update'), [
        'chat_hide_last_seen' => '1',
        'chat_hide_read_receipts' => '1',
    ])->assertRedirect();

    $alice->refresh();

    expect($alice->chat_hide_last_seen)->toBeTrue()
        ->and($alice->chat_hide_read_receipts)->toBeTrue();
});

test('unchecking a privacy toggle turns it back off', function () {
    [$tenant, $owner] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);
    $alice = staffMember($tenant, 'أليس');

    $alice->forceFill(['chat_hide_last_seen' => true])->save();

    /*
     * An unchecked checkbox sends no key at all. Reading the key directly
     * would leave the toggle stuck on forever — the form could never turn it
     * back off.
     */
    $this->actingAs($alice)->put(route('tenant.messenger.privacy.update'), [])->assertRedirect();

    expect($alice->refresh()->chat_hide_last_seen)->toBeFalse();
});

// ---------------------------------------------------------------------------
// Lone emoji rendering
// ---------------------------------------------------------------------------

test('a single emoji message is flagged for oversized rendering', function () {
    [$tenant, $owner] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);
    $alice = staffMember($tenant, 'أليس');
    $bob = staffMember($tenant, 'بوب');

    $thread = app(StartDirectConversationAction::class)->handle($alice, $bob->id);

    $emoji = app(SendMessageAction::class)->handle($thread, $alice, '🎉');
    $text = app(SendMessageAction::class)->handle($thread, $alice, 'مرحباً 🎉');

    expect($emoji->isLoneEmoji())->toBeTrue()
        ->and($text->isLoneEmoji())->toBeFalse();
});

// ---------------------------------------------------------------------------
// The controls actually reach the DOM
// ---------------------------------------------------------------------------

test('the thread renders every composer and message control', function () {
    [$tenant, $owner] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);

    $manager = staffWithRole($tenant, 'مدير', TenantPermissionCatalog::ROLE_HR_MANAGER);
    $bob = staffMember($tenant, 'بوب');

    $thread = app(StartDirectConversationAction::class)->handle($manager, $bob->id);
    app(SendMessageAction::class)->handle($thread, $manager, 'رسالة');

    /*
     * These were all built server-side in Phase 2 and none of them were
     * rendered — the backend existed and the screen had no controls at all.
     * Asserting on the markup is what catches that gap, which no action-level
     * test could.
     */
    $this->actingAs($manager)->get(route('tenant.messenger.show', $thread->id))
        ->assertOk()
        ->assertSee('data-testid="messenger-emoji"', false)
        ->assertSee('data-testid="messenger-attach"', false)
        ->assertSee('data-testid="messenger-privacy"', false)
        ->assertSee('data-testid="messenger-new-group"', false)
        ->assertSee('تثبيت الرسالة', false)
        ->assertSee('👍', false);
});

test('an employee never sees the group creation trigger', function () {
    [$tenant, $owner] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);

    $employee = staffWithRole($tenant, 'موظف', TenantPermissionCatalog::ROLE_EMPLOYEE);
    $bob = staffMember($tenant, 'بوب');

    $thread = app(StartDirectConversationAction::class)->handle($employee, $bob->id);

    // The route already refuses them; not rendering the control means they are
    // never offered an action they would be denied.
    $this->actingAs($employee)->get(route('tenant.messenger.show', $thread->id))
        ->assertOk()
        ->assertDontSee('data-testid="messenger-new-group"', false);
});

test('a pinned message renders the pinned bar', function () {
    [$tenant, $owner] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);
    $alice = staffMember($tenant, 'أليس');
    $bob = staffMember($tenant, 'بوب');

    $thread = app(StartDirectConversationAction::class)->handle($alice, $bob->id);
    $message = app(SendMessageAction::class)->handle($thread, $alice, 'اجتماع الساعة الثالثة');
    app(PinMessageAction::class)->handle($message, $alice);

    $this->actingAs($bob)->get(route('tenant.messenger.show', $thread->id))
        ->assertOk()
        ->assertSee('رسالة مثبّتة', false)
        ->assertSee('اجتماع الساعة الثالثة', false);
});

test('reaction counts render on the bubble', function () {
    [$tenant, $owner] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);
    $alice = staffMember($tenant, 'أليس');
    $bob = staffMember($tenant, 'بوب');

    $thread = app(StartDirectConversationAction::class)->handle($alice, $bob->id);
    $message = app(SendMessageAction::class)->handle($thread, $alice, 'خبر جيد');
    app(ToggleReactionAction::class)->handle($message, $bob, '❤️');

    $this->actingAs($alice)->get(route('tenant.messenger.show', $thread->id))
        ->assertOk()
        ->assertSee('❤️', false);
});

// ---------------------------------------------------------------------------
// Archive / hide — both act on the caller's row only
// ---------------------------------------------------------------------------

test('archiving removes a thread from my inbox but not from theirs', function () {
    [$tenant, $owner] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);
    $alice = staffMember($tenant, 'أليس');
    $bob = staffMember($tenant, 'بوب');

    $thread = app(StartDirectConversationAction::class)->handle($alice, $bob->id);
    app(SendMessageAction::class)->handle($thread, $alice, 'رسالة');

    $this->actingAs($alice)->postJson(route('tenant.messenger.archive', $thread->id))
        ->assertOk()
        ->assertJson(['archived' => true]);

    expect(Conversation::query()->inboxFor($alice->fresh())->count())->toBe(0)
        // Bob's copy is untouched — the thread is shared.
        ->and(Conversation::query()->inboxFor($bob->fresh())->count())->toBe(1);

    // Still reachable by URL and still accessible: archiving files, it does
    // not revoke.
    $this->actingAs($alice)->get(route('tenant.messenger.show', $thread->id))->assertOk();
});

test('deleting a conversation destroys nothing and un-hides on a new message', function () {
    [$tenant, $owner] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);
    $alice = staffMember($tenant, 'أليس');
    $bob = staffMember($tenant, 'بوب');

    $thread = app(StartDirectConversationAction::class)->handle($alice, $bob->id);
    app(SendMessageAction::class)->handle($thread, $alice, 'الأولى');

    $this->actingAs($alice)->postJson(route('tenant.messenger.hide', $thread->id))
        ->assertOk()
        ->assertJson(['hidden' => true]);

    expect(Conversation::query()->inboxFor($alice->fresh())->count())->toBe(0)
        // "حذف" is per-participant: no message and no conversation is removed.
        ->and($thread->fresh())->not->toBeNull()
        ->and(Message::query()->where('conversation_id', $thread->id)->count())->toBe(1);

    app(SendMessageAction::class)->handle($thread->fresh(), $bob, 'هل أنت هناك؟');

    /*
     * The thread returns. Without this the user would have silently stopped
     * receiving that colleague's messages — they asked to clear a thread, not
     * to block a person.
     */
    expect(Conversation::query()->inboxFor($alice->fresh())->count())->toBe(1);
});

test('a non participant cannot archive or hide a thread', function () {
    [$tenant, $owner] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);
    $alice = staffMember($tenant, 'أليس');
    $bob = staffMember($tenant, 'بوب');

    $thread = app(StartDirectConversationAction::class)->handle($alice, $bob->id);

    $this->actingAs($owner)->postJson(route('tenant.messenger.archive', $thread->id))->assertNotFound();
    $this->actingAs($owner)->postJson(route('tenant.messenger.hide', $thread->id))->assertNotFound();
});

test('the thread header exposes close and options controls', function () {
    [$tenant, $owner] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);
    $alice = staffMember($tenant, 'أليس');
    $bob = staffMember($tenant, 'بوب');

    $thread = app(StartDirectConversationAction::class)->handle($alice, $bob->id);

    $this->actingAs($alice)->get(route('tenant.messenger.show', $thread->id))
        ->assertOk()
        ->assertSee('data-testid="messenger-close"', false)
        ->assertSee('data-testid="messenger-thread-menu"', false)
        ->assertSee('أرشفة المحادثة', false)
        ->assertSee('حذف المحادثة', false);
});

test('reacting returns authoritative counts for in-place rendering', function () {
    [$tenant, $owner] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);
    $alice = staffMember($tenant, 'أليس');
    $bob = staffMember($tenant, 'بوب');

    $thread = app(StartDirectConversationAction::class)->handle($alice, $bob->id);
    $message = app(SendMessageAction::class)->handle($thread, $alice, 'رسالة');

    // The client renders from this payload rather than guessing locally, which
    // would drift the moment two people react at once.
    $this->actingAs($bob)
        ->postJson(route('tenant.messenger.react', $message->id), ['emoji' => '👍'])
        ->assertOk()
        ->assertJson(['added' => true, 'counts' => ['👍' => 1]]);

    $this->actingAs($alice)
        ->postJson(route('tenant.messenger.react', $message->id), ['emoji' => '👍'])
        ->assertOk()
        ->assertJson(['counts' => ['👍' => 2]]);
});

// ---------------------------------------------------------------------------
// Message actions — delete, forward, reply
// ---------------------------------------------------------------------------

test('only the author may delete a message', function () {
    [$tenant, $owner] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);
    $alice = staffMember($tenant, 'أليس');
    $bob = staffMember($tenant, 'بوب');

    $thread = app(StartDirectConversationAction::class)->handle($alice, $bob->id);
    $message = app(SendMessageAction::class)->handle($thread, $alice, 'رسالتي');

    /*
     * Bob is a participant and can read it, but a thread stops being a
     * reliable record of who said what the moment someone else can remove it.
     */
    $this->actingAs($bob)
        ->deleteJson(route('tenant.messenger.messages.destroy', $message->id))
        ->assertStatus(422);

    expect(Message::query()->find($message->id))->not->toBeNull();

    $this->actingAs($alice)
        ->deleteJson(route('tenant.messenger.messages.destroy', $message->id))
        ->assertOk()
        ->assertJson(['deleted' => true]);

    // Soft: the row survives for retention and keeps replies resolvable.
    expect(Message::query()->find($message->id))->toBeNull()
        ->and(Message::withTrashed()->find($message->id))->not->toBeNull();
});

test('deleting a pinned message clears the pin', function () {
    [$tenant, $owner] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);
    $alice = staffMember($tenant, 'أليس');
    $bob = staffMember($tenant, 'بوب');

    $thread = app(StartDirectConversationAction::class)->handle($alice, $bob->id);
    $message = app(SendMessageAction::class)->handle($thread, $alice, 'مثبّتة');
    app(PinMessageAction::class)->handle($message, $alice);

    app(DeleteMessageAction::class)->handle($message->fresh(), $alice);

    // Otherwise the pinned bar quotes text no longer in the thread.
    expect(Message::withTrashed()->find($message->id)->pinned_at)->toBeNull();
});

test('forwarding copies a message into another thread the sender belongs to', function () {
    [$tenant, $owner] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);
    $alice = staffMember($tenant, 'أليس');
    $bob = staffMember($tenant, 'بوب');
    $carol = staffMember($tenant, 'كارول');

    $source = app(StartDirectConversationAction::class)->handle($alice, $bob->id);
    $destination = app(StartDirectConversationAction::class)->handle($alice, $carol->id);
    $message = app(SendMessageAction::class)->handle($source, $bob, 'معلومة مهمة');

    $this->actingAs($alice)
        ->postJson(route('tenant.messenger.forward', $message->id), ['conversation_id' => $destination->id])
        ->assertCreated()
        ->assertJson(['forwarded' => true]);

    // A copy, not a move — and the copy is authored by whoever forwarded it.
    expect($source->messages()->count())->toBe(1)
        ->and($destination->messages()->where('body', 'معلومة مهمة')->count())->toBe(1)
        ->and($destination->messages()->where('body', 'معلومة مهمة')->first()->sender_id)->toBe($alice->id);
});

test('forwarding is refused when the caller is not in the destination', function () {
    [$tenant, $owner] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);
    $alice = staffMember($tenant, 'أليس');
    $bob = staffMember($tenant, 'بوب');
    $carol = staffMember($tenant, 'كارول');
    $dave = staffMember($tenant, 'ديف');

    $source = app(StartDirectConversationAction::class)->handle($alice, $bob->id);
    $strangers = app(StartDirectConversationAction::class)->handle($carol, $dave->id);
    $message = app(SendMessageAction::class)->handle($source, $alice, 'سرّي');

    // Membership is checked on BOTH ends: without the destination check,
    // forwarding becomes a way to inject into someone else's thread.
    $this->actingAs($alice)
        ->postJson(route('tenant.messenger.forward', $message->id), ['conversation_id' => $strangers->id])
        ->assertNotFound();

    expect($strangers->messages()->count())->toBe(0);
});

test('a reply stores parent_id and rejects a parent from another thread', function () {
    [$tenant, $owner] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);
    $alice = staffMember($tenant, 'أليس');
    $bob = staffMember($tenant, 'بوب');
    $carol = staffMember($tenant, 'كارول');

    $thread = app(StartDirectConversationAction::class)->handle($alice, $bob->id);
    $other = app(StartDirectConversationAction::class)->handle($alice, $carol->id);

    $original = app(SendMessageAction::class)->handle($thread, $bob, 'سؤال');
    $foreign = app(SendMessageAction::class)->handle($other, $carol, 'من محادثة أخرى');

    $reply = app(SendMessageAction::class)->handle($thread, $alice, 'جواب', $original->id);
    expect($reply->parent_id)->toBe($original->id);

    /*
     * Quoting a message from a thread the reader is not in would leak its text
     * through the reply preview. The parent is dropped rather than the send
     * failing, so a stale client cannot block the message.
     */
    $crossThread = app(SendMessageAction::class)->handle($thread, $alice, 'محاولة', $foreign->id);
    expect($crossThread->parent_id)->toBeNull();
});

test('the composer send control is an icon button', function () {
    [$tenant, $owner] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);
    $alice = staffMember($tenant, 'أليس');
    $bob = staffMember($tenant, 'بوب');

    $thread = app(StartDirectConversationAction::class)->handle($alice, $bob->id);

    $this->actingAs($alice)->get(route('tenant.messenger.show', $thread->id))
        ->assertOk()
        ->assertSee('data-testid="messenger-send"', false)
        ->assertSee('aria-label="إرسال"', false);
});
