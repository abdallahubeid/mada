<?php

use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Models\User;
use App\Notifications\Tenant\TaskAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/**
 * Write one unread database notification straight onto the user.
 *
 * Deliberately not `$user->notify(new SomeNotification(...))`: every concrete
 * TenantNotification is ShouldBroadcastNow and carries a model, so dispatching
 * one here would drag the broadcast channel and model serialization into a test
 * about marking rows read. The controller only ever reads `$user->notifications()`
 * — this produces exactly the row it will find.
 */
function seedNotification(User $user, string $title = 'إشعار تجريبي'): string
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

test('clicking an unread notification marks it read and drops the unread count', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $id = seedNotification($user);

    expect($user->unreadNotifications()->count())->toBe(1);

    $this->postJson(route('tenant.notifications.read', ['notification' => $id]))
        ->assertOk()
        ->assertJson(['ok' => true, 'unread_count' => 0]);

    expect($user->refresh()->unreadNotifications()->count())->toBe(0)
        ->and($user->notifications()->firstOrFail()->read_at)->not->toBeNull();
});

test('marking an already read notification is idempotent', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $id = seedNotification($user);

    $this->postJson(route('tenant.notifications.read', ['notification' => $id]))->assertOk();
    $first = $user->refresh()->notifications()->firstOrFail()->read_at;

    $this->postJson(route('tenant.notifications.read', ['notification' => $id]))
        ->assertOk()
        ->assertJson(['unread_count' => 0]);

    /*
     * markAsRead() is a no-op once read_at is set, so the original timestamp
     * stands. A second click must not rewrite when the user first saw it —
     * that timestamp is the only record of it.
     */
    expect($user->refresh()->notifications()->firstOrFail()->read_at->toIso8601String())
        ->toBe($first->toIso8601String());
});

test('a user cannot mark another users notification as read', function () {
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $colleague = User::factory()->create(['tenant_id' => $owner->tenant_id]);
    $id = seedNotification($colleague);

    /*
     * The lookup runs through `$user->notifications()`, so someone else's row is
     * not "forbidden" — it does not exist for this user at all. That is the
     * stronger guarantee: there is no code path where the id is even resolved
     * before the ownership check.
     */
    $this->postJson(route('tenant.notifications.read', ['notification' => $id]))
        ->assertNotFound();

    expect($colleague->refresh()->unreadNotifications()->count())->toBe(1);
});

test('read all clears every unread notification at once', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    seedNotification($user, 'الأول');
    seedNotification($user, 'الثاني');

    expect($user->refresh()->unreadNotifications()->count())->toBe(2);

    $this->postJson(route('tenant.notifications.read-all'))
        ->assertOk()
        ->assertJson(['ok' => true, 'unread_count' => 0]);

    expect($user->refresh()->unreadNotifications()->count())->toBe(0);
});

test('the notification index reports the unread count the drawer renders', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $id = seedNotification($user);

    $response = $this->getJson(route('tenant.notifications.index'))->assertOk();

    expect($response->json('unread_count'))->toBe(1)
        ->and($response->json('notifications.0.id'))->toBe($id)
        ->and($response->json('notifications.0.read_at'))->toBeNull();
});

test('an unauthenticated request never reaches the notification endpoints', function () {
    $id = (string) Str::uuid();

    // Redirected to login by the auth middleware rather than 401 — these routes
    // live in the session-backed `web` group, not an API group.
    $this->post(route('tenant.notifications.read', ['notification' => $id]))->assertRedirect();
    $this->post(route('tenant.notifications.read-all'))->assertRedirect();
    $this->get(route('tenant.notifications.index'))->assertRedirect();
});
