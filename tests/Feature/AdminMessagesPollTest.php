<?php

use App\Models\SupportMessage;
use App\Models\SupportThread;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    actingAsPlatformOperator();
});

test('messages poll returns thread list signature and counts', function () {
    $thread = SupportThread::factory()->create([
        'status' => SupportThread::STATUS_OPEN,
        'company' => 'شركة الأفق',
        'subject' => 'طلب عرض توضيحي',
    ]);

    SupportMessage::factory()->create([
        'support_thread_id' => $thread->id,
        'body' => 'رسالة أولية',
    ]);

    $this->getJson(route('admin.messages.poll', ['status' => 'open']))
        ->assertOk()
        ->assertJsonPath('counts.open', 1)
        ->assertJsonPath('threads.0.id', $thread->id)
        ->assertJsonPath('threads.0.display_name', 'شركة الأفق')
        ->assertJsonStructure(['signature', 'threads', 'counts', 'messages', 'selected_exists']);
});

test('messages poll returns only newer messages for the open thread', function () {
    $thread = SupportThread::factory()->create(['status' => SupportThread::STATUS_OPEN]);

    $first = SupportMessage::factory()->create([
        'support_thread_id' => $thread->id,
        'body' => 'الرسالة الأولى',
        'delivered_at' => now()->subMinutes(2),
        'read_at' => null,
    ]);

    $second = SupportMessage::factory()->create([
        'support_thread_id' => $thread->id,
        'body' => 'رسالة جديدة للعميل',
        'delivered_at' => now(),
        'read_at' => null,
    ]);

    $this->getJson(route('admin.messages.poll', [
        'status' => 'open',
        'thread' => $thread->id,
        'after_message_id' => $first->id,
    ]))
        ->assertOk()
        ->assertJsonCount(1, 'messages')
        ->assertJsonPath('messages.0.id', $second->id)
        ->assertJsonPath('messages.0.body', 'رسالة جديدة للعميل')
        ->assertJsonPath('selected_exists', true);

    expect($second->fresh()->read_at)->not->toBeNull();
});

test('messages poll reports selected_exists false after soft delete', function () {
    $thread = SupportThread::factory()->create(['status' => SupportThread::STATUS_OPEN]);
    $threadId = $thread->id;
    $thread->delete();

    $this->getJson(route('admin.messages.poll', [
        'status' => 'open',
        'thread' => $threadId,
        'after_message_id' => 1,
    ]))
        ->assertOk()
        ->assertJsonPath('selected_exists', false)
        ->assertJsonCount(0, 'messages');
});

test('messages inbox page includes poll bootstrap config', function () {
    $this->get(route('admin.messages'))
        ->assertOk()
        ->assertSee('madaMessagesInbox', false)
        ->assertSee('pollIntervalMs', false)
        ->assertSee('deleteThread', false)
        ->assertSee('هل أنت تأكد من حذف هذه المحادثة؟', false);
});
