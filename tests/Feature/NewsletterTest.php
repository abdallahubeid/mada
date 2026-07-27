<?php

use App\Mail\Marketing\NewsletterCampaignMail;
use App\Mail\Marketing\WelcomeNewsletterMail;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('new newsletter subscription saves subscriber sends welcome mail and flashes success', function () {
    Mail::fake();

    $this->from(route('landing'))
        ->post(route('marketing.newsletter.subscribe'), [
            'email' => 'Subscriber@Example.com',
        ])
        ->assertRedirect(route('landing'))
        ->assertSessionHas('flasher', fn (array $flasher): bool => ($flasher['type'] ?? null) === 'success'
            && str_contains((string) ($flasher['message'] ?? ''), 'تم اشتراكك بنجاح'));

    $subscriber = NewsletterSubscriber::query()->where('email', 'subscriber@example.com')->first();

    expect($subscriber)->not->toBeNull()
        ->and($subscriber->status)->toBe(NewsletterSubscriber::STATUS_SUBSCRIBED)
        ->and($subscriber->subscribed_at)->not->toBeNull();

    Mail::assertSent(WelcomeNewsletterMail::class, function (WelcomeNewsletterMail $mail) {
        return $mail->subscriber->email === 'subscriber@example.com'
            && $mail->hasTo('subscriber@example.com');
    });
});

test('duplicate active subscription shows info toast without resending welcome mail', function () {
    Mail::fake();

    NewsletterSubscriber::factory()->subscribed()->create([
        'email' => 'dup@example.com',
    ]);

    $this->from(route('landing'))
        ->post(route('marketing.newsletter.subscribe'), [
            'email' => 'dup@example.com',
        ])
        ->assertRedirect(route('landing'))
        ->assertSessionHas('flasher', fn (array $flasher): bool => ($flasher['type'] ?? null) === 'info'
            && str_contains((string) ($flasher['message'] ?? ''), 'مشترك بالفعل'));

    expect(NewsletterSubscriber::query()->where('email', 'dup@example.com')->count())->toBe(1);
    Mail::assertNothingSent();
});

test('newsletter signup requires a valid email', function () {
    Mail::fake();

    $this->from(route('landing'))
        ->post(route('marketing.newsletter.subscribe'), [
            'email' => 'invalid',
        ])
        ->assertRedirect(route('landing'))
        ->assertSessionHasErrors(['email']);

    Mail::assertNothingSent();
});

test('public unsubscribe route marks subscriber as unsubscribed', function () {
    $subscriber = NewsletterSubscriber::factory()->subscribed()->create([
        'email' => 'leave@example.com',
    ]);

    $this->get(route('marketing.newsletter.unsubscribe', ['email' => $subscriber->email]))
        ->assertOk()
        ->assertSee('تم إلغاء الاشتراك', false)
        ->assertSee('leave@example.com', false);

    expect($subscriber->fresh()->status)->toBe(NewsletterSubscriber::STATUS_UNSUBSCRIBED)
        ->and($subscriber->fresh()->unsubscribed_at)->not->toBeNull();
});

test('admin newsletter dashboard shows stats and subscribers', function () {
    NewsletterSubscriber::factory()->subscribed()->create(['email' => 'active@example.com']);
    NewsletterSubscriber::factory()->unsubscribed()->create(['email' => 'gone@example.com']);

    $this->get(route('admin.newsletter.index'))
        ->assertOk()
        ->assertSee('النشرة البريدية', false)
        ->assertSee('active@example.com', false)
        ->assertSee('gone@example.com', false)
        ->assertSee('إرسال حملة', false)
        ->assertSee('veyraNewsletterDashboard', false)
        ->assertSee('font-mono text-sm', false)
        ->assertSee('dir="ltr"', false);
});

test('admin newsletter poll returns updated stats and subscribers', function () {
    NewsletterSubscriber::factory()->subscribed()->create(['email' => 'first@example.com']);

    $initial = $this->getJson(route('admin.newsletter.poll', ['status' => 'all']))
        ->assertOk()
        ->assertJsonPath('stats.active', 1)
        ->assertJsonPath('subscribers.0.email', 'first@example.com')
        ->json();

    NewsletterSubscriber::factory()->subscribed()->create(['email' => 'second@example.com']);

    $this->getJson(route('admin.newsletter.poll', ['status' => 'all']))
        ->assertOk()
        ->assertJsonPath('stats.active', 2)
        ->assertJsonPath('stats.total', 2)
        ->assertJsonFragment(['email' => 'second@example.com']);

    expect($initial['signature'])->not->toBe(
        $this->getJson(route('admin.newsletter.poll', ['status' => 'all']))->json('signature')
    );
});

test('admin newsletter poll uses arabic relative subscribed_at labels', function () {
    NewsletterSubscriber::factory()->subscribed()->create([
        'email' => 'arabic-time@example.com',
        'subscribed_at' => now()->subMinute(),
    ]);

    $this->getJson(route('admin.newsletter.poll'))
        ->assertOk()
        ->assertJsonPath('subscribers.0.email', 'arabic-time@example.com');

    $human = $this->getJson(route('admin.newsletter.poll'))->json('subscribers.0.subscribed_at_human');

    expect($human)->not->toBeNull()
        ->and($human)->not->toBe('—')
        ->and($human)->toMatch('/[\x{0600}-\x{06FF}]/u');
});

test('admin can toggle and soft delete a subscriber', function () {
    $subscriber = NewsletterSubscriber::factory()->subscribed()->create([
        'email' => 'toggle@example.com',
    ]);

    $this->put(route('admin.newsletter.toggle', $subscriber))
        ->assertRedirect()
        ->assertSessionHas('flasher');

    expect($subscriber->fresh()->status)->toBe(NewsletterSubscriber::STATUS_UNSUBSCRIBED);

    $this->delete(route('admin.newsletter.destroy', $subscriber))
        ->assertRedirect()
        ->assertSessionHas('flasher');

    expect(NewsletterSubscriber::query()->find($subscriber->id))->toBeNull()
        ->and(NewsletterSubscriber::withTrashed()->find($subscriber->id))->not->toBeNull();
});

test('admin can export active subscribers as csv', function () {
    NewsletterSubscriber::factory()->subscribed()->create(['email' => 'export-me@example.com']);
    NewsletterSubscriber::factory()->unsubscribed()->create(['email' => 'skip-me@example.com']);

    $response = $this->get(route('admin.newsletter.export'));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('.csv');

    $content = $response->streamedContent();

    expect($content)->toContain('export-me@example.com')
        ->and($content)->not->toContain('skip-me@example.com');
});

test('admin campaign sends to active subscribers except excluded ones', function () {
    Mail::fake();

    $keep = NewsletterSubscriber::factory()->subscribed()->create(['email' => 'keep@example.com']);
    $exclude = NewsletterSubscriber::factory()->subscribed()->create(['email' => 'exclude@example.com']);
    NewsletterSubscriber::factory()->unsubscribed()->create(['email' => 'inactive@example.com']);

    $this->post(route('admin.newsletter.campaign'), [
        'subject' => 'تحديث المنتج',
        'body' => '<p>مرحبا بكم في الحملة</p>',
        'exclude_ids' => [$exclude->id],
    ])
        ->assertRedirect()
        ->assertSessionHas('flasher', fn (array $flasher): bool => ($flasher['type'] ?? null) === 'success');

    Mail::assertSent(NewsletterCampaignMail::class, function (NewsletterCampaignMail $mail) use ($keep) {
        return $mail->hasTo($keep->email)
            && $mail->campaignSubject === 'تحديث المنتج';
    });

    Mail::assertNotSent(NewsletterCampaignMail::class, function (NewsletterCampaignMail $mail) {
        return $mail->hasTo('exclude@example.com') || $mail->hasTo('inactive@example.com');
    });

    $campaign = NewsletterCampaign::query()->first();

    expect($campaign)->not->toBeNull()
        ->and($campaign->subject)->toBe('تحديث المنتج')
        ->and($campaign->recipients_count)->toBe(1)
        ->and($campaign->content)->toContain('مرحبا بكم في الحملة');
});

test('admin campaigns history page lists sent campaigns and show endpoint returns content', function () {
    $campaign = NewsletterCampaign::factory()->create([
        'subject' => 'حملة تجريبية',
        'content' => '<p>محتوى الحملة</p>',
        'recipients_count' => 3,
    ]);

    $this->get(route('admin.newsletter.campaigns.index'))
        ->assertOk()
        ->assertSee('الحملات البريدية', false)
        ->assertSee('حملة تجريبية', false)
        ->assertSee('قراءة المحتوى', false);

    $this->getJson(route('admin.newsletter.campaigns.show', $campaign))
        ->assertOk()
        ->assertJsonPath('subject', 'حملة تجريبية')
        ->assertJsonPath('content', '<p>محتوى الحملة</p>')
        ->assertJsonPath('recipients_count', 3);
});

test('newsletter dashboard table includes index column markup', function () {
    NewsletterSubscriber::factory()->subscribed()->create(['email' => 'numbered@example.com']);

    $this->get(route('admin.newsletter.index'))
        ->assertOk()
        ->assertSee('>1</td>', false)
        ->assertSee('border-e border-mist-100', false);
});

test('markdown mail branding uses veyra footer copy', function () {
    $subscriber = NewsletterSubscriber::factory()->subscribed()->create([
        'email' => 'branding@example.com',
    ]);

    $mailable = new WelcomeNewsletterMail($subscriber);

    $mailable->assertSeeInHtml('تحياتنا، فريق عمل')
        ->assertSeeInHtml(config('app.name'));
});

test('legacy newsletter store route still works', function () {
    Mail::fake();

    $this->from(route('landing'))
        ->post(route('marketing.newsletter.store'), [
            'email' => 'legacy@example.com',
        ])
        ->assertRedirect(route('landing'))
        ->assertSessionHas('flasher');

    expect(NewsletterSubscriber::query()->where('email', 'legacy@example.com')->exists())->toBeTrue();
});
