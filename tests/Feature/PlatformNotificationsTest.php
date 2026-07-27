<?php

use App\Events\Platform\PlatformNotificationCreated;
use App\Models\PlatformNotification;
use App\Models\User;
use App\Services\Admin\AdminChromeBadges;
use App\Services\Admin\PlatformNotificationPublisher;
use App\Services\Newsletter\NewsletterService;
use App\Services\Support\SupportInbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('platform notification publisher persists and broadcasts urgent alerts', function () {
    Event::fake([PlatformNotificationCreated::class]);

    $notification = app(PlatformNotificationPublisher::class)->securityAlert(
        'تنبيه أمني',
        'محاولة دخول مشبوهة',
    );

    expect($notification->exists)->toBeTrue()
        ->and($notification->category)->toBe(PlatformNotification::CATEGORY_SECURITY)
        ->and($notification->read_at)->toBeNull();

    Event::assertDispatched(PlatformNotificationCreated::class, function (PlatformNotificationCreated $event) use ($notification): bool {
        return $event->notification->is($notification)
            && $event->unreadCount === 1
            && $event->broadcastAs() === 'PlatformNotificationCreated';
    });
});

test('routine newsletter notifications are persisted without broadcasting', function () {
    Event::fake([PlatformNotificationCreated::class]);
    Mail::fake();

    app(NewsletterService::class)->subscribe('ops-only@example.com');

    expect(PlatformNotification::query()->where('category', PlatformNotification::CATEGORY_OPS)->count())->toBe(1);

    Event::assertNotDispatched(PlatformNotificationCreated::class);
});

test('chrome poll returns real unread notification counts from the database', function () {
    PlatformNotification::factory()->unread()->count(2)->create();
    PlatformNotification::factory()->read()->create();

    $this->getJson(route('admin.chrome.poll'))
        ->assertOk()
        ->assertJsonPath('notifications_unread', 2)
        ->assertJsonStructure(['messages_unread', 'notifications_unread', 'signature']);

    expect(app(AdminChromeBadges::class)->unreadNotificationsCount())->toBe(2);
});

test('admin notifications page lists persisted notifications', function () {
    PlatformNotification::factory()->approval()->unread()->create([
        'title' => 'مستأجر بانتظار الموافقة',
        'body' => 'طلب تسجيل جديد',
    ]);

    $this->get(route('admin.notifications'))
        ->assertOk()
        ->assertSee('مركز الإشعارات', false)
        ->assertSee('مستأجر بانتظار الموافقة', false)
        ->assertSee('تحديد الكل كمقروء', false);
});

test('admin can mark all notifications as read and delete them', function () {
    PlatformNotification::factory()->unread()->count(3)->create();

    $this->post(route('admin.notifications.read-all'))
        ->assertRedirect()
        ->assertSessionHas('flasher');

    expect(PlatformNotification::query()->unread()->count())->toBe(0)
        ->and(PlatformNotification::query()->read()->count())->toBe(3);

    $this->delete(route('admin.notifications.destroy-all'))
        ->assertRedirect()
        ->assertSessionHas('flasher');

    expect(PlatformNotification::query()->count())->toBe(0)
        ->and(PlatformNotification::withTrashed()->count())->toBe(3);
});

test('admin can toggle and destroy a single notification', function () {
    $notification = PlatformNotification::factory()->unread()->create();

    $this->put(route('admin.notifications.toggle-read', $notification))
        ->assertRedirect();

    expect($notification->fresh()->isRead())->toBeTrue();

    $this->delete(route('admin.notifications.destroy', $notification))
        ->assertRedirect();

    expect(PlatformNotification::query()->find($notification->id))->toBeNull();
    $this->assertSoftDeleted('platform_notifications', ['id' => $notification->id]);
});

test('contact inquiry creates a broadcast support notification', function () {
    Event::fake([PlatformNotificationCreated::class]);

    app(SupportInbox::class)->ingestContactInquiry([
        'name' => 'أحمد',
        'email' => 'support-alert@example.com',
        'company' => 'شركة الاختبار',
        'subject' => 'demo',
        'message' => 'نحتاج عرضاً توضيحياً',
    ]);

    expect(PlatformNotification::query()->where('category', PlatformNotification::CATEGORY_OPS)->count())->toBe(1);

    Event::assertDispatched(PlatformNotificationCreated::class);
});

test('platform notification created event broadcasts on private admin channel', function () {
    $notification = PlatformNotification::factory()->unread()->create([
        'title' => 'بث تجريبي',
    ]);

    $event = new PlatformNotificationCreated($notification, 4);

    expect($event->broadcastOn()[0]->name)->toBe('private-admin.notifications')
        ->and($event->broadcastWith()['title'])->toBe('بث تجريبي')
        ->and($event->broadcastWith()['unread_count'])->toBe(4);
});

test('failed login streak publishes a security notification', function () {
    Event::fake([PlatformNotificationCreated::class]);

    User::factory()->create([
        'email' => 'locked@example.com',
        'password' => 'Password123!',
    ]);

    for ($i = 0; $i < 5; $i++) {
        $this->from('/login')
            ->post('/login', [
                'email' => 'locked@example.com',
                'password' => 'wrong-password',
            ]);
    }

    expect(PlatformNotification::query()->where('category', PlatformNotification::CATEGORY_SECURITY)->count())->toBe(1);

    Event::assertDispatched(PlatformNotificationCreated::class);
});
