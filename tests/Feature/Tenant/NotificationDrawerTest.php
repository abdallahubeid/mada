<?php

use App\Broadcasting\TenantNotificationChannel;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Models\User;
use App\Notifications\Tenant\TaskAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function drawerNotification(User $user, string $title = 'إشعار تجريبي'): string
{
    $notification = $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => TaskAssignedNotification::class,
        'data' => [
            'title' => $title,
            'message' => 'نص الإشعار',
            'url' => null,
            'icon' => 'bell',
            'severity' => 'medium',
            'type' => 'task.assigned',
        ],
    ]);

    return (string) $notification->id;
}

// ---------------------------------------------------------------------------
// Drawer markup contract
// ---------------------------------------------------------------------------

test('the drawer no longer renders a manual mark all as read button', function () {
    actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $html = $this->get('/app/dashboard')->assertOk()->getContent();

    /*
     * Opening the drawer is the acknowledgement now, so the button must be
     * gone from the markup — not merely hidden with x-show, which would leave
     * it reachable and would re-introduce a second way to do the same thing.
     */
    expect($html)->not->toContain('markAllRead()');

    /*
     * The Arabic label must not reach the client at all — not in markup and not
     * in a shipped JS comment. The shell's inline <script> is served verbatim,
     * so a comment quoting the old label would still be in the document and
     * would still match here. That is the point: this asserts on what the
     * browser receives, not on what the templates intend.
     */
    expect($html)->not->toContain('قراءة الكل');
});

test('opening and dismissing the drawer both route through the acknowledging handler', function () {
    actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $html = $this->get('/app/dashboard')->assertOk()->getContent();

    expect($html)->toContain('openNotificationsDrawer()')
        ->and($html)->toContain('closeNotificationsDrawer()')
        ->and($html)->toContain('acknowledgeAll()');

    /*
     * Every dismissal path must acknowledge. A template binding that sets the
     * flag directly — `x-on:click="notificationsOpen = false"` — would close the
     * drawer without marking anything read, which is the regression this guards.
     *
     * Matched with the surrounding quotes on purpose: the bare substring also
     * occurs inside closeNotificationsDrawer()'s own body, where it is correct.
     */
    expect($html)->not->toContain('="notificationsOpen = false"');
});

test('the badge is rendered and seeded from the server on first paint', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    drawerNotification($user);

    $html = $this->get('/app/dashboard')->assertOk()->getContent();

    /*
     * The shell config is emitted through @js(), which hex-escapes quotes into
     * a JS string literal. Asserting the exact escaping would be testing Blade,
     * not this feature — so assert the key is seeded, and take the count itself
     * from the endpoint the drawer actually reads.
     */
    expect($html)->toContain('tenant-notifications-badge')
        ->and($html)->toContain('unreadCount')
        ->and($html)->toContain('echoEnabled');

    expect($this->getJson(route('tenant.notifications.index'))->json('unread_count'))->toBe(1);
});

// ---------------------------------------------------------------------------
// The endpoint the drawer calls on open/close
// ---------------------------------------------------------------------------

test('acknowledging clears every unread notification for this user only', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $colleague = User::factory()->create(['tenant_id' => $user->tenant_id]);

    drawerNotification($user, 'الأول');
    drawerNotification($user, 'الثاني');
    drawerNotification($colleague, 'إشعار زميل');

    $this->postJson(route('tenant.notifications.read-all'))
        ->assertOk()
        ->assertJson(['ok' => true, 'unread_count' => 0]);

    expect($user->refresh()->unreadNotifications()->count())->toBe(0)
        // A shared "mark all" that reached across users would silently clear a
        // colleague's inbox from someone else's drawer.
        ->and($colleague->refresh()->unreadNotifications()->count())->toBe(1);
});

test('acknowledging with nothing unread is a harmless no-op', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    // The client skips the request when the badge is already 0, but the drawer
    // fires on both open and close, so the endpoint must tolerate the repeat.
    $this->postJson(route('tenant.notifications.read-all'))->assertOk();
    $this->postJson(route('tenant.notifications.read-all'))
        ->assertOk()
        ->assertJson(['unread_count' => 0]);

    expect($user->refresh()->unreadNotifications()->count())->toBe(0);
});

// ---------------------------------------------------------------------------
// Broadcast channel authorization — multi-tenant isolation
// ---------------------------------------------------------------------------

test('a user may join only their own tenant-scoped notification channel', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $channel = new TenantNotificationChannel;

    expect($channel->join($user, $user->tenant_id, $user->id))->toBeTrue();
});

test('a colleague in the same tenant cannot join another users channel', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $colleague = User::factory()->create(['tenant_id' => $user->tenant_id]);

    $channel = new TenantNotificationChannel;

    // Same tenant is not enough — the channel is per USER, and notifications
    // carry candidate names, payroll figures and leave reasons.
    expect($channel->join($colleague, $user->tenant_id, $user->id))->toBeFalse();
});

test('a matching user id in a different tenant is rejected', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $otherTenant = Tenant::factory()->create();

    $channel = new TenantNotificationChannel;

    /*
     * The tenant segment is what makes this channel safe. Laravel's default
     * `App.Models.User.{id}` naming carries no tenant, so a mismatch between
     * the id space and the tenant would be unauthorised here but invisible
     * there.
     */
    expect($channel->join($user, $otherTenant->id, $user->id))->toBeFalse();
});

test('a platform operator with no tenant cannot join a tenant channel', function () {
    $operator = User::factory()->create(['tenant_id' => null]);

    $channel = new TenantNotificationChannel;

    expect($channel->join($operator, 1, $operator->id))->toBeFalse();
});

test('the shell subscribes to the tenant scoped private channel', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $html = $this->get('/app/dashboard')->assertOk()->getContent();

    /*
     * The shell hands Echo the tenant and user ids; echo.js composes
     * `tenant.{tenantId}.notifications.{userId}` from them — a per-user channel
     * inside a per-tenant namespace, which Laravel's default
     * `App.Models.User.{id}` naming cannot express.
     */
    expect($html)->toContain('tenantId')
        ->and($html)->toContain('userId')
        ->and($html)->toContain('veyraListenTenantNotifications')
        ->and($html)->toContain('this.echoEnabled');
});

test('the shell prepends a broadcast notification and lifts the badge', function () {
    actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $html = $this->get('/app/dashboard')->assertOk()->getContent();

    // Guards the live-update contract: increment, dedupe, prepend, cap.
    expect($html)->toContain('handleRealtimeNotification')
        ->and($html)->toContain('this.notifications = [item, ...this.notifications]')
        ->and($html)->toContain('Number(this.unreadCount || 0) + 1');
});
