<?php

use App\Domain\Tenancy\Actions\SeedDefaultTenantRoles;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\TenantContactMessage;
use App\Domain\Tenancy\Models\TenantContactThread;
use App\Domain\Tenancy\Models\TenantPortalSetting;
use App\Domain\Tenancy\TenantContext;
use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Events\Tenant\NewContactMessageReceived;
use App\Mail\Tenant\ContactMessageReply;
use App\Notifications\Tenant\NewContactMessageNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

/**
 * @param  array<string, mixed>  $tenantAttributes
 */
function seedContactPortalTenant(array $tenantAttributes = []): Tenant
{
    $tenant = Tenant::factory()->active()->create(array_merge([
        'slug' => 'acme-robotics',
        'name' => 'Acme Robotics',
    ], $tenantAttributes));

    app(TenantContext::class)->setTenant($tenant);
    TenantPortalSetting::query()->create(TenantPortalSetting::defaultAttributes($tenant));
    app(SeedDefaultTenantRoles::class)->handle($tenant);
    app(TenantContext::class)->setTenant(null);

    return $tenant;
}

test('portal contact form threads messages by sender email', function () {
    Event::fake([NewContactMessageReceived::class]);
    Notification::fake();

    $tenant = seedContactPortalTenant();

    $payload = [
        'name' => 'نورة العتيبي',
        'email' => 'noura@example.com',
        'subject' => 'استفسار عن وظيفة',
        'message' => 'أرغب بمعرفة تفاصيل الشواغر الحالية في الشركة.',
    ];

    $this->post(route('portal.contact.store', $tenant->slug), $payload)
        ->assertRedirect(route('portal.contact', $tenant->slug));

    $this->post(route('portal.contact.store', $tenant->slug), [
        ...$payload,
        'name' => 'نورة العتيبي',
        'subject' => 'متابعة الاستفسار',
        'message' => 'هل يمكنني الحصول على رد خلال هذا الأسبوع؟',
    ])->assertRedirect(route('portal.contact', $tenant->slug));

    app(TenantContext::class)->setTenant($tenant);

    expect(TenantContactThread::query()->where('sender_email', 'noura@example.com')->count())->toBe(1)
        ->and(TenantContactMessage::query()->count())->toBe(2);

    $thread = TenantContactThread::query()->where('sender_email', 'noura@example.com')->first();

    expect($thread)->not->toBeNull()
        ->and($thread->messages)->toHaveCount(2)
        ->and($thread->messages->first()->receiptStatus())->toBe('delivered');

    Event::assertDispatched(NewContactMessageReceived::class, 2);
});

test('ajax show marks visitor messages as read', function () {
    Event::fake([NewContactMessageReceived::class]);
    Notification::fake();

    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $thread = TenantContactThread::factory()->create([
        'tenant_id' => $user->tenant_id,
        'sender_name' => 'خالد',
        'sender_email' => 'khaled@example.com',
        'subject' => 'شراكة',
    ]);

    $message = TenantContactMessage::factory()->create([
        'tenant_id' => $user->tenant_id,
        'tenant_contact_thread_id' => $thread->id,
        'sender_role' => TenantContactMessage::ROLE_VISITOR,
        'sender_name' => 'خالد',
        'body' => 'نود مناقشة فرصة تعاون.',
        'delivered_at' => now()->subMinute(),
        'read_at' => null,
    ]);

    expect($message->receiptStatus())->toBe('delivered');

    $this->getJson(route('tenant.contact-messages.show', $thread))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('thread.id', $thread->id)
        ->assertJsonPath('messages.0.body', 'نود مناقشة فرصة تعاون.');

    expect($message->fresh()->read_at)->not->toBeNull()
        ->and($message->fresh()->receiptStatus())->toBe('read');
});

test('contact messages inbox has no open closed status ui', function () {
    Event::fake([NewContactMessageReceived::class]);
    Notification::fake();

    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    TenantContactThread::factory()->create([
        'tenant_id' => $user->tenant_id,
        'subject' => 'اختبار الواجهة',
    ]);

    $this->get(route('tenant.contact-messages.index'))
        ->assertOk()
        ->assertSee('madaTenantContactInbox', false)
        ->assertSee('selectThread', false)
        ->assertSee('closeChat', false)
        ->assertSee('اختر محادثة لبدء القراءة', false)
        ->assertDontSee('مفتوحة', false)
        ->assertDontSee('مغلقة', false)
        ->assertDontSee('toggleThreadStatus', false)
        ->assertDontSee('data-status-toggle', false);
});

test('owner can reply via ajax and email is sent to the visitor', function () {
    Event::fake([NewContactMessageReceived::class]);
    Notification::fake();
    Mail::fake();

    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, [
        'status' => 'active',
        'name' => 'مالك المؤسسة',
    ]);

    $thread = TenantContactThread::factory()->create([
        'tenant_id' => $user->tenant_id,
        'sender_email' => 'visitor@example.com',
        'subject' => 'استفسار عام',
    ]);

    TenantContactMessage::factory()->create([
        'tenant_id' => $user->tenant_id,
        'tenant_contact_thread_id' => $thread->id,
        'body' => 'سؤال الزائر',
    ]);

    $this->postJson(route('tenant.contact-messages.reply', $thread), [
        'body' => 'شكرًا لتواصلك، سنعود إليك قريبًا.',
    ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('chat_message.sender_role', TenantContactMessage::ROLE_STAFF);

    $thread->refresh();

    expect($thread->messages)->toHaveCount(2)
        ->and($thread->messages->last()->sender_role)->toBe(TenantContactMessage::ROLE_STAFF)
        ->and($thread->messages->last()->delivered_at)->not->toBeNull();

    Mail::assertSent(ContactMessageReply::class, function (ContactMessageReply $mail) use ($thread): bool {
        return $mail->hasTo('visitor@example.com')
            && $mail->thread->is($thread);
    });
});

test('portal contact notifies owners and broadcasts NewContactMessageReceived', function () {
    Event::fake([NewContactMessageReceived::class]);
    Notification::fake();

    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, [
        'status' => 'active',
        'slug' => 'acme-robotics',
    ]);

    $tenant = $owner->tenant;
    assert($tenant instanceof Tenant);

    app(TenantContext::class)->setTenant($tenant);
    TenantPortalSetting::query()->create(TenantPortalSetting::defaultAttributes($tenant));
    app(TenantContext::class)->setTenant(null);

    auth()->logout();

    $this->post(route('portal.contact.store', $tenant->slug), [
        'name' => 'سارة',
        'email' => 'sara@example.com',
        'subject' => 'مرحبا',
        'message' => 'هذه رسالة اختبارية من نموذج التواصل العام.',
    ])->assertRedirect();

    Event::assertDispatched(NewContactMessageReceived::class, function (NewContactMessageReceived $event) use ($tenant): bool {
        return $event->tenantId === $tenant->id
            && $event->broadcastAs() === 'NewContactMessageReceived'
            && $event->broadcastOn()[0]->name === 'private-tenant.'.$tenant->id.'.notifications.'.$event->ownerIds[0];
    });

    Notification::assertSentTo($owner, NewContactMessageNotification::class);
});

test('employee without contact permission cannot open inbox', function () {
    actingAsTenantUser(TenantPermissionCatalog::ROLE_EMPLOYEE, ['status' => 'active']);

    $this->get(route('tenant.contact-messages.index'))->assertForbidden();
});

test('owner can archive a thread via ajax and it leaves the inbox list', function () {
    Event::fake([NewContactMessageReceived::class]);
    Notification::fake();

    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $thread = TenantContactThread::factory()->create([
        'tenant_id' => $user->tenant_id,
        'subject' => 'للأرشفة',
    ]);

    $this->postJson(route('tenant.contact-messages.archive', $thread))
        ->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'تم نقل المحادثة إلى الأرشيف',
            'thread_id' => $thread->id,
        ]);

    expect($thread->fresh()->status)->toBe(TenantContactThread::STATUS_ARCHIVED);

    $this->get(route('tenant.contact-messages.index'))
        ->assertOk()
        ->assertDontSee('للأرشفة', false);

    $this->getJson(route('tenant.contact-messages.threads', ['folder' => 'archived']))
        ->assertOk()
        ->assertJsonPath('folder', 'archived')
        ->assertJsonFragment(['subject' => 'للأرشفة']);

    $this->getJson(route('tenant.contact-messages.show', $thread))
        ->assertOk()
        ->assertJsonPath('can_reply', false)
        ->assertJsonPath('thread.is_archived', true);
});

test('owner can unarchive a thread via ajax and it returns to the active list', function () {
    Event::fake([NewContactMessageReceived::class]);
    Notification::fake();

    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $thread = TenantContactThread::factory()->create([
        'tenant_id' => $user->tenant_id,
        'subject' => 'من الأرشيف',
        'status' => TenantContactThread::STATUS_ARCHIVED,
    ]);

    $this->postJson(route('tenant.contact-messages.unarchive', $thread))
        ->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'تم إعادة المحادثة إلى الرسائل النشطة',
            'thread_id' => $thread->id,
        ]);

    expect($thread->fresh()->status)->toBe(TenantContactThread::STATUS_OPEN);

    $this->getJson(route('tenant.contact-messages.threads', ['folder' => 'active']))
        ->assertOk()
        ->assertJsonFragment(['subject' => 'من الأرشيف']);

    $this->getJson(route('tenant.contact-messages.threads', ['folder' => 'archived']))
        ->assertOk()
        ->assertJsonMissing(['subject' => 'من الأرشيف']);
});

test('threads endpoint filters by folder and returns counts', function () {
    Event::fake([NewContactMessageReceived::class]);
    Notification::fake();

    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    TenantContactThread::factory()->create([
        'tenant_id' => $user->tenant_id,
        'subject' => 'نشطة',
        'status' => TenantContactThread::STATUS_OPEN,
    ]);

    TenantContactThread::factory()->create([
        'tenant_id' => $user->tenant_id,
        'subject' => 'مؤرشفة',
        'status' => TenantContactThread::STATUS_ARCHIVED,
    ]);

    $this->getJson(route('tenant.contact-messages.threads', ['folder' => 'active']))
        ->assertOk()
        ->assertJsonPath('counts.active', 1)
        ->assertJsonPath('counts.archived', 1)
        ->assertJsonFragment(['subject' => 'نشطة'])
        ->assertJsonMissing(['subject' => 'مؤرشفة']);

    $this->getJson(route('tenant.contact-messages.threads', ['folder' => 'archived']))
        ->assertOk()
        ->assertJsonFragment(['subject' => 'مؤرشفة'])
        ->assertJsonMissing(['subject' => 'نشطة']);
});

test('owner can soft delete a thread via ajax', function () {
    Event::fake([NewContactMessageReceived::class]);
    Notification::fake();

    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $thread = TenantContactThread::factory()->create([
        'tenant_id' => $user->tenant_id,
        'subject' => 'للحذف',
    ]);

    $this->deleteJson(route('tenant.contact-messages.destroy', $thread))
        ->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'تم حذف المحادثة بنجاح',
            'thread_id' => $thread->id,
        ])
        ->assertJsonPath('undo_url', route('tenant.trash.restore', [
            'type' => 'contact-messages',
            'id' => $thread->id,
        ]))
        ->assertJsonPath('undo_label', 'تراجع');

    expect(TenantContactThread::query()->find($thread->id))->toBeNull()
        ->and(TenantContactThread::withTrashed()->find($thread->id))->not->toBeNull();
});

test('contact messages inbox renders archive and delete menu actions', function () {
    Event::fake([NewContactMessageReceived::class]);
    Notification::fake();

    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    TenantContactThread::factory()->create([
        'tenant_id' => $user->tenant_id,
        'subject' => 'قائمة الإجراءات',
    ]);

    $this->get(route('tenant.contact-messages.index'))
        ->assertOk()
        ->assertSee('الرسائل النشطة', false)
        ->assertSee('الأرشيف', false)
        ->assertSee('setFolder', false)
        ->assertSee('unarchiveThread', false)
        ->assertSee('archiveThread', false)
        ->assertSee('deleteThread', false)
        ->assertSee('أرشفة', false)
        ->assertSee('حذف', false)
        ->assertSee('هل أنت تأكد من الحذف؟', false);
});
