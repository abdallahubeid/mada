<?php

use App\Mail\Marketing\ContactInquiry;
use App\Models\SupportMessage;
use App\Models\SupportThread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('the contact page renders the form', function () {
    $this->get(route('marketing.contact'))
        ->assertOk()
        ->assertSee('تواصل معنا')
        ->assertSee('إرسال الرسالة')
        ->assertSee('طلب عرض توضيحي');
});

test('a valid contact submission sends mail creates open thread and redirects with status', function () {
    Mail::fake();

    $this->post(route('marketing.contact.store'), [
        'name' => 'نورة القحطاني',
        'email' => 'noura@example.com',
        'company' => 'مؤسسة نماء',
        'subject' => 'demo',
        'message' => 'أرغب في حجز عرض توضيحي للمنصّة لفريقنا.',
    ])
        ->assertRedirect(route('marketing.contact'))
        ->assertSessionHas('flasher', function (array $flasher): bool {
            return ($flasher['type'] ?? null) === 'success'
                && filled($flasher['message'] ?? null);
        });

    Mail::assertSent(ContactInquiry::class, function (ContactInquiry $mail) {
        return $mail->inquiry['email'] === 'noura@example.com'
            && $mail->hasTo(config('mail.from.address'));
    });

    $thread = SupportThread::query()->where('email', 'noura@example.com')->first();

    expect($thread)->not->toBeNull()
        ->and($thread->status)->toBe(SupportThread::STATUS_OPEN)
        ->and($thread->subject)->toBe('طلب عرض توضيحي')
        ->and($thread->messages)->toHaveCount(1)
        ->and($thread->messages->first()->sender_role)->toBe(SupportMessage::ROLE_CUSTOMER)
        ->and($thread->messages->first()->delivered_at)->not->toBeNull()
        ->and($thread->messages->first()->read_at)->toBeNull();
});

test('contact form appends to an existing open thread for the same email', function () {
    Mail::fake();

    $thread = SupportThread::factory()->create([
        'email' => 'same@example.com',
        'name' => 'عميل سابق',
        'status' => SupportThread::STATUS_OPEN,
        'subject' => 'طلب عرض توضيحي',
    ]);

    SupportMessage::factory()->create([
        'support_thread_id' => $thread->id,
        'body' => 'الرسالة الأولى',
    ]);

    $this->post(route('marketing.contact.store'), [
        'name' => 'عميل سابق',
        'email' => 'same@example.com',
        'subject' => 'support',
        'message' => 'هذه رسالة متابعة على نفس المحادثة النشطة.',
    ])->assertRedirect(route('marketing.contact'));

    expect(SupportThread::query()->where('email', 'same@example.com')->count())->toBe(1)
        ->and($thread->fresh()->messages)->toHaveCount(2)
        ->and($thread->fresh()->messages->last()->body)->toContain('متابعة');
});

test('contact form creates a new thread when previous thread is resolved', function () {
    Mail::fake();

    SupportThread::factory()->resolved()->create([
        'email' => 'resolved@example.com',
        'name' => 'عميل',
    ]);

    $this->post(route('marketing.contact.store'), [
        'name' => 'عميل',
        'email' => 'resolved@example.com',
        'subject' => 'sales',
        'message' => 'استفسار جديد بعد إغلاق المحادثة السابقة بالكامل.',
    ])->assertRedirect();

    expect(SupportThread::query()->where('email', 'resolved@example.com')->count())->toBe(2)
        ->and(SupportThread::query()->where('email', 'resolved@example.com')->where('status', SupportThread::STATUS_OPEN)->count())->toBe(1);
});

test('contact form validation rejects incomplete submissions', function () {
    Mail::fake();

    $this->from(route('marketing.contact'))
        ->post(route('marketing.contact.store'), [
            'name' => '',
            'email' => 'not-an-email',
            'subject' => 'demo',
            'message' => 'قصير',
        ])
        ->assertRedirect(route('marketing.contact'))
        ->assertSessionHasErrors(['name', 'email', 'message']);

    Mail::assertNothingSent();
    expect(SupportThread::query()->count())->toBe(0);
});
