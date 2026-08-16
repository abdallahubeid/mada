<?php

use App\Models\SupportMessage;
use App\Models\SupportThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    actingAsPlatformOperator();
});

test('admin messages inbox lists open contact threads without auto selecting', function () {
    $thread = SupportThread::factory()->create([
        'name' => 'سارة المنصوري',
        'email' => 'sara@example.com',
        'company' => 'شركة الأفق',
        'subject' => 'طلب عرض توضيحي',
        'status' => SupportThread::STATUS_OPEN,
    ]);

    SupportMessage::factory()->create([
        'support_thread_id' => $thread->id,
        'sender_name' => 'سارة المنصوري',
        'body' => 'نرغب بترقية اشتراكنا إلى خطة Growth',
    ]);

    $this->get(route('admin.messages', ['status' => 'open']))
        ->assertOk()
        ->assertSee('شركة الأفق', false)
        ->assertSee('طلب عرض توضيحي', false)
        ->assertSee('اختر محادثة لبدء القراءة', false)
        ->assertDontSee('اكتب ردًا...', false)
        ->assertDontSee('aria-label="إرسال"', false);
});

test('selecting a thread loads history and marks customer messages as read', function () {
    $thread = SupportThread::factory()->create(['status' => SupportThread::STATUS_OPEN]);
    $message = SupportMessage::factory()->create([
        'support_thread_id' => $thread->id,
        'sender_role' => SupportMessage::ROLE_CUSTOMER,
        'body' => 'رسالة غير مقروءة من العميل',
        'delivered_at' => now()->subMinute(),
        'read_at' => null,
    ]);

    $this->get(route('admin.messages', ['status' => 'open', 'thread' => $thread->id]))
        ->assertOk()
        ->assertSee('رسالة غير مقروءة من العميل', false)
        ->assertSee('اكتب ردًا...', false)
        ->assertSee('data-chat-active', false);

    expect($message->fresh()->read_at)->not->toBeNull()
        ->and($message->fresh()->receiptStatus())->toBe('read');
});

test('admin can reply and thread moves to in progress', function () {
    auth()->user()->update(['name' => 'مشرف المنصّة']);
    $thread = SupportThread::factory()->create(['status' => SupportThread::STATUS_OPEN]);

    SupportMessage::factory()->create([
        'support_thread_id' => $thread->id,
        'body' => 'سؤال العميل',
    ]);

    $this->post(route('admin.messages.reply', $thread), [
        'body' => 'أهلًا، تم استلام طلبك وسنتابع معك.',
    ])
        ->assertRedirect()
        ->assertSessionHas('flasher', fn (array $flasher): bool => ($flasher['type'] ?? null) === 'success');

    $thread->refresh();

    expect($thread->status)->toBe(SupportThread::STATUS_IN_PROGRESS)
        ->and($thread->messages)->toHaveCount(2)
        ->and($thread->messages->last()->sender_role)->toBe(SupportMessage::ROLE_ADMIN)
        ->and($thread->messages->last()->delivered_at)->not->toBeNull()
        ->and($thread->messages->last()->read_at)->toBeNull();
});

test('admin messages view renders avatars and receipt indicators', function () {
    Storage::fake('custom');

    $user = User::factory()->create(['name' => 'Demo Owner']);
    $path = 'user/avatar/chat-avatar.jpg';
    Storage::disk('custom')->put($path, 'avatar');

    $user->images()->create([
        'collection' => 'avatar',
        'disk' => 'custom',
        'path' => $path,
        'original_name' => 'chat-avatar.jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 6,
        'sort_order' => 0,
    ]);

    $thread = SupportThread::factory()->create([
        'user_id' => $user->id,
        'name' => 'Demo Owner',
        'email' => $user->email,
        'status' => SupportThread::STATUS_OPEN,
    ]);

    SupportMessage::factory()->create([
        'support_thread_id' => $thread->id,
        'user_id' => $user->id,
        'sender_name' => 'Demo Owner',
        'body' => 'مرحبا من العميل',
        'delivered_at' => now(),
        'read_at' => null,
    ]);

    $avatarUrl = $user->fresh()->avatar_url;

    $this->get(route('admin.messages', ['status' => 'open', 'thread' => $thread->id]))
        ->assertOk()
        ->assertSee($avatarUrl, false)
        ->assertSee('h-10 w-10 shrink-0 rounded-full border border-slate-700 object-cover', false)
        ->assertSee('تمت القراءة', false)
        ->assertSee('mada-relative-time', false);
});

test('admin can update thread status', function () {
    $thread = SupportThread::factory()->create(['status' => SupportThread::STATUS_OPEN]);

    $this->put(route('admin.messages.status', $thread), [
        'status' => SupportThread::STATUS_RESOLVED,
    ])->assertRedirect(route('admin.messages', [
        'status' => SupportThread::STATUS_RESOLVED,
        'thread' => $thread->id,
    ]))->assertSessionHas('flasher');

    expect($thread->fresh()->status)->toBe(SupportThread::STATUS_RESOLVED);
});

test('admin can archive a thread', function () {
    $thread = SupportThread::factory()->create(['status' => SupportThread::STATUS_OPEN]);

    $this->post(route('admin.messages.archive', $thread))
        ->assertRedirect(route('admin.messages', ['status' => SupportThread::STATUS_ARCHIVED]))
        ->assertSessionHas('flasher', fn (array $flasher): bool => ($flasher['type'] ?? null) === 'info');

    expect($thread->fresh()->status)->toBe(SupportThread::STATUS_ARCHIVED);
});

test('admin can soft delete a thread via ajax', function () {
    $thread = SupportThread::factory()->create(['status' => SupportThread::STATUS_OPEN]);

    $this->deleteJson(route('admin.messages.destroy', $thread))
        ->assertOk()
        ->assertJson(['success' => true]);

    expect(SupportThread::query()->find($thread->id))->toBeNull()
        ->and(SupportThread::withTrashed()->find($thread->id))->not->toBeNull();
});
