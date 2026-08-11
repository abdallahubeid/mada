<?php

use App\Domain\Messaging\Actions\SendMessageAction;
use App\Domain\Messaging\Actions\StartDirectConversationAction;
use App\Domain\Messaging\EmployeeDirectory;
use App\Domain\Messaging\Exceptions\MessagingException;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Tenancy\Actions\SeedDefaultTenantRoles;
use App\Domain\Tenancy\Enums\EmployeeStatus;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantContext;
use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Events\Messaging\MessageSent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

/**
 * Messenger Phase 1.
 *
 * The privacy tests here are the load-bearing ones: the agreed policy is that
 * NO role — Owner, HR Manager or platform Super Admin — can read a thread they
 * are not a participant in, and `Gate::before` in AppServiceProvider grants
 * Owners every ability. Anything phrased as a permission check would therefore
 * have handed Owners every conversation in the company.
 *
 * @return array{0: Tenant, 1: User}
 */
function messagingTenant(): array
{
    $tenant = Tenant::factory()->active()->create();
    app(SeedDefaultTenantRoles::class)->handle($tenant);
    app(TenantContext::class)->setTenant($tenant);

    $owner = User::factory()->create(['tenant_id' => $tenant->id]);
    $owner->assignRole(TenantPermissionCatalog::ROLE_OWNER);

    return [$tenant, $owner];
}

/** Creates a staff member with a login and an employee record. */
function staffMember(Tenant $tenant, string $name, EmployeeStatus $status = EmployeeStatus::Active, bool $withLogin = true): User
{
    $user = $withLogin
        ? User::factory()->create(['tenant_id' => $tenant->id, 'name' => $name])
        : null;

    Employee::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user?->id,
        'status' => $status,
    ]);

    return $user ?? User::factory()->create(['tenant_id' => $tenant->id, 'name' => $name]);
}

// ---------------------------------------------------------------------------
// Directory
// ---------------------------------------------------------------------------

test('the directory lists only colleagues with an active login', function () {
    [$tenant, $viewer] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $viewer->id]);

    $reachable = staffMember($tenant, 'زميل نشط');
    staffMember($tenant, 'موظف مستقيل', EmployeeStatus::Resigned);

    // An employee with no user_id cannot authenticate onto a channel and is
    // hidden outright per the agreed decision.
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => null]);

    $directory = app(EmployeeDirectory::class)->for($viewer);
    $ids = $directory->pluck('user_id');

    expect($ids)->toContain($reachable->id)
        ->and($ids)->not->toContain($viewer->id);

    expect($directory->pluck('name'))->not->toContain('موظف مستقيل');
});

test('the directory never reaches across tenants', function () {
    [$tenantA, $viewer] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenantA->id, 'user_id' => $viewer->id]);

    [$tenantB] = messagingTenant();
    $stranger = staffMember($tenantB, 'موظف شركة أخرى');

    app(TenantContext::class)->setTenant($tenantA);

    expect(app(EmployeeDirectory::class)->for($viewer)->pluck('user_id'))
        ->not->toContain($stranger->id)
        ->and(app(EmployeeDirectory::class)->canReach($viewer, $stranger->id))->toBeFalse();
});

// ---------------------------------------------------------------------------
// Starting threads
// ---------------------------------------------------------------------------

test('a direct conversation is created once and reused thereafter', function () {
    [$tenant, $viewer] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $viewer->id]);
    $colleague = staffMember($tenant, 'زميل');

    $action = app(StartDirectConversationAction::class);

    $first = $action->handle($viewer, $colleague->id);
    $second = $action->handle($viewer, $colleague->id);

    expect($second->id)->toBe($first->id)
        ->and(Conversation::query()->count())->toBe(1);
});

test('the pair hash is order independent so both sides land in one thread', function () {
    [$tenant, $viewer] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $viewer->id]);
    $colleague = staffMember($tenant, 'زميل');

    $action = app(StartDirectConversationAction::class);

    $fromViewer = $action->handle($viewer, $colleague->id);
    // The colleague presses "message" from their side.
    $fromColleague = $action->handle($colleague, $viewer->id);

    // Without sorting the ids before hashing, whoever pressed first would
    // determine the hash and the pair would end up with two half-histories.
    expect($fromColleague->id)->toBe($fromViewer->id);
});

test('a conversation cannot be started with someone outside the tenant', function () {
    [$tenantA, $viewer] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenantA->id, 'user_id' => $viewer->id]);

    [$tenantB] = messagingTenant();
    $stranger = staffMember($tenantB, 'غريب');

    app(TenantContext::class)->setTenant($tenantA);

    expect(fn () => app(StartDirectConversationAction::class)->handle($viewer, $stranger->id))
        ->toThrow(MessagingException::class);
});

// ---------------------------------------------------------------------------
// Privacy — the policy that drove the whole design
// ---------------------------------------------------------------------------

test('an owner cannot read a conversation they are not part of', function () {
    [$tenant, $owner] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);

    $alice = staffMember($tenant, 'أليس');
    $bob = staffMember($tenant, 'بوب');

    $thread = app(StartDirectConversationAction::class)->handle($alice, $bob->id);
    app(SendMessageAction::class)->handle($thread, $alice, 'رسالة خاصة');

    /*
     * The Owner holds every permission through Gate::before. If access were
     * phrased as an ability this would return 200 and leak the thread.
     * A 404 rather than 403 is also deliberate — 403 would confirm it exists.
     */
    $this->actingAs($owner)->get(route('tenant.messenger.show', $thread->id))->assertNotFound();

    expect(Conversation::query()->visibleTo($owner)->count())->toBe(0)
        ->and(Conversation::query()->visibleTo($alice)->count())->toBe(1);
});

test('a participant can read their own conversation', function () {
    [$tenant, $owner] = messagingTenant();
    $alice = staffMember($tenant, 'أليس');
    $bob = staffMember($tenant, 'بوب');
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);

    $thread = app(StartDirectConversationAction::class)->handle($alice, $bob->id);
    app(SendMessageAction::class)->handle($thread, $alice, 'أهلاً بوب');

    $this->actingAs($bob)->get(route('tenant.messenger.show', $thread->id))
        ->assertOk()
        ->assertSee('أهلاً بوب', false);
});

test('a non participant cannot post into a conversation over HTTP', function () {
    [$tenant, $owner] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);
    $alice = staffMember($tenant, 'أليس');
    $bob = staffMember($tenant, 'بوب');

    $thread = app(StartDirectConversationAction::class)->handle($alice, $bob->id);

    $this->actingAs($owner)
        ->post(route('tenant.messenger.send', $thread->id), ['body' => 'أقحمت نفسي'])
        ->assertNotFound();

    expect($thread->messages()->count())->toBe(0);
});

// ---------------------------------------------------------------------------
// Sending and read watermarks
// ---------------------------------------------------------------------------

test('sending broadcasts immediately on the tenant scoped private channel', function () {
    Event::fake([MessageSent::class]);

    [$tenant, $owner] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);
    $alice = staffMember($tenant, 'أليس');
    $bob = staffMember($tenant, 'بوب');

    $thread = app(StartDirectConversationAction::class)->handle($alice, $bob->id);
    app(SendMessageAction::class)->handle($thread, $alice, 'رسالة');

    Event::assertDispatched(MessageSent::class, function (MessageSent $event) use ($thread, $tenant): bool {
        $channels = $event->broadcastOn();

        // The tenant segment must be in the channel NAME, not just checked by
        // the authorizer: conversation ids are globally sequential.
        return (string) $channels[0] === "private-tenant.{$tenant->id}.conversations.{$thread->id}";
    });
});

test('the broadcast payload carries no user model fields', function () {
    [$tenant, $owner] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);
    $alice = staffMember($tenant, 'أليس');
    $bob = staffMember($tenant, 'بوب');

    $thread = app(StartDirectConversationAction::class)->handle($alice, $bob->id);
    $message = app(SendMessageAction::class)->handle($thread, $alice, 'رسالة');

    $payload = (new MessageSent($message))->broadcastWith();

    // Broadcasting bypasses Eloquent, so a serialised model would ship whatever
    // columns it happened to hold.
    expect(array_keys($payload))->toBe([
        'id', 'conversation_id', 'sender_id', 'sender_name',
        'type', 'body', 'parent_id', 'sent_at',
    ]);
});

test('the sender does not see their own message as unread', function () {
    [$tenant, $owner] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);
    $alice = staffMember($tenant, 'أليس');
    $bob = staffMember($tenant, 'بوب');

    $thread = app(StartDirectConversationAction::class)->handle($alice, $bob->id);
    app(SendMessageAction::class)->handle($thread, $alice, 'رسالة');

    $aliceSide = $thread->participants()->where('user_id', $alice->id)->first();
    $bobSide = $thread->participants()->where('user_id', $bob->id)->first();

    expect($aliceSide->unreadCount())->toBe(0)
        ->and($bobSide->unreadCount())->toBe(1);
});

test('opening a thread advances the reader watermark', function () {
    [$tenant, $owner] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);
    $alice = staffMember($tenant, 'أليس');
    $bob = staffMember($tenant, 'بوب');

    $thread = app(StartDirectConversationAction::class)->handle($alice, $bob->id);
    app(SendMessageAction::class)->handle($thread, $alice, 'واحد');
    app(SendMessageAction::class)->handle($thread, $alice, 'اثنان');

    expect($thread->participants()->where('user_id', $bob->id)->first()->unreadCount())->toBe(2);

    $this->actingAs($bob)->get(route('tenant.messenger.show', $thread->id))->assertOk();

    // One UPDATE of one row, whatever the thread's length.
    expect($thread->participants()->where('user_id', $bob->id)->first()->unreadCount())->toBe(0);
});

test('an empty message is refused', function () {
    [$tenant, $owner] = messagingTenant();
    Employee::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);
    $alice = staffMember($tenant, 'أليس');
    $bob = staffMember($tenant, 'بوب');

    $thread = app(StartDirectConversationAction::class)->handle($alice, $bob->id);

    expect(fn () => app(SendMessageAction::class)->handle($thread, $alice, '   '))
        ->toThrow(MessagingException::class);
});
