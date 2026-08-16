<?php

use App\Domain\Tenancy\Models\Tenant;
use App\Models\NewsletterSubscriber;
use App\Models\SupportMessage;
use App\Models\SupportThread;
use App\Services\Admin\AdminChromeBadges;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    actingAsPlatformOperator();
});

test('chrome poll returns unread message and notification badge counts', function () {
    $thread = SupportThread::factory()->create(['status' => SupportThread::STATUS_OPEN]);
    SupportMessage::factory()->create([
        'support_thread_id' => $thread->id,
        'sender_role' => SupportMessage::ROLE_CUSTOMER,
        'read_at' => null,
    ]);

    $this->getJson(route('admin.chrome.poll'))
        ->assertOk()
        ->assertJsonPath('messages_unread', 1)
        ->assertJsonPath('notifications_unread', app(AdminChromeBadges::class)->unreadNotificationsCount())
        ->assertJsonStructure(['messages_unread', 'notifications_unread', 'signature']);
});

test('chrome poll message badge drops after customer messages are read', function () {
    $thread = SupportThread::factory()->create(['status' => SupportThread::STATUS_OPEN]);
    SupportMessage::factory()->create([
        'support_thread_id' => $thread->id,
        'sender_role' => SupportMessage::ROLE_CUSTOMER,
        'read_at' => null,
    ]);

    expect(app(AdminChromeBadges::class)->unreadMessagesCount())->toBe(1);

    SupportMessage::query()->update(['read_at' => now()]);

    $this->getJson(route('admin.chrome.poll'))
        ->assertOk()
        ->assertJsonPath('messages_unread', 0);
});

test('admin topbar includes live search and badge poll wiring', function () {
    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('madaAdminChrome', false)
        ->assertSee('بحث في المنصّة', false)
        ->assertSee('aria-label="الرسائل"', false)
        ->assertSee('aria-label="الإشعارات"', false)
        ->assertSee('pollUrl', false)
        ->assertSee('suggestUrl', false)
        ->assertSee('messagesUnread', false)
        ->assertSee('notificationsUnread', false);
});

test('global search suggest returns grouped autocomplete matches', function () {
    $tenant = Tenant::factory()->create([
        'name' => 'شركة الأفق للتقنية',
        'slug' => 'ufuq-tech',
    ]);

    $thread = SupportThread::factory()->create([
        'subject' => 'استفسار عن الأفق',
        'email' => 'hello@ufuq.test',
        'status' => SupportThread::STATUS_OPEN,
    ]);

    NewsletterSubscriber::factory()->subscribed()->create([
        'email' => 'ufuq-fan@example.com',
    ]);

    $response = $this->getJson(route('admin.search.suggest', ['q' => 'الأفق']))
        ->assertOk()
        ->assertJsonPath('query', 'الأفق');

    $keys = collect($response->json('groups'))->pluck('key')->all();

    expect($keys)->toContain('tenants')
        ->and($keys)->toContain('messages');

    $this->getJson(route('admin.search.suggest', ['q' => 'ufuq-fan']))
        ->assertOk()
        ->assertJsonFragment(['title' => 'ufuq-fan@example.com']);

    expect($tenant->id)->toBeInt()
        ->and($thread->id)->toBeInt();
});

test('global search suggest indexes admin navigation pages', function () {
    $response = $this->getJson(route('admin.search.suggest', ['q' => 'النشرة']))
        ->assertOk();

    $keys = collect($response->json('groups'))->pluck('key')->all();

    expect($keys)->toContain('navigation');

    $navigation = collect($response->json('groups'))->firstWhere('key', 'navigation');
    $titles = collect($navigation['items'] ?? [])->pluck('title')->all();

    expect($titles)->toContain('النشرة البريدية');

    $urls = collect($navigation['items'] ?? [])->pluck('url')->all();

    expect($urls)->toContain(route('admin.newsletter.index'));
});

test('navigation search matches diacritic variants and english keywords', function () {
    $plans = $this->getJson(route('admin.search.suggest', ['q' => 'الخُطط']))
        ->assertOk()
        ->json('groups');

    $planTitles = collect($plans)
        ->firstWhere('key', 'navigation')['items'] ?? [];

    expect(collect($planTitles)->pluck('title'))->toContain('الخطط والحدود');

    $messages = $this->getJson(route('admin.search.suggest', ['q' => 'support']))
        ->assertOk()
        ->json('groups');

    $messageTitles = collect($messages)
        ->firstWhere('key', 'navigation')['items'] ?? [];

    expect(collect($messageTitles)->pluck('title'))->toContain('الرسائل والدعم');
});

test('contextual suggest prioritizes active page entity group after navigation', function () {
    NewsletterSubscriber::factory()->subscribed()->create([
        'email' => 'priority@example.com',
    ]);

    Tenant::factory()->create([
        'name' => 'Priority Tenant Corp',
        'slug' => 'priority-tenant',
    ]);

    $keys = collect($this->getJson(route('admin.search.suggest', [
        'q' => 'priority',
        'context' => 'newsletter',
    ]))->assertOk()->json('groups'))->pluck('key')->values()->all();

    $newsletterIndex = array_search('newsletter', $keys, true);
    $tenantsIndex = array_search('tenants', $keys, true);

    expect($newsletterIndex)->not->toBeFalse()
        ->and($tenantsIndex)->not->toBeFalse()
        ->and($newsletterIndex)->toBeLessThan($tenantsIndex);
});

test('entity search is case-insensitive for partial emails', function () {
    NewsletterSubscriber::factory()->subscribed()->create([
        'email' => 'CaseMix@Example.COM',
    ]);

    $this->getJson(route('admin.search.suggest', ['q' => 'casemix']))
        ->assertOk()
        ->assertJsonFragment(['title' => 'CaseMix@Example.COM']);
});

test('global search suggest ignores short queries', function () {
    $this->getJson(route('admin.search.suggest', ['q' => 'a']))
        ->assertOk()
        ->assertJsonPath('total', 0)
        ->assertJsonPath('groups', []);
});

test('global search results page lists matches by category tabs', function () {
    Tenant::factory()->create([
        'name' => 'مؤسسة نماء الرقمية',
        'slug' => 'namaa-digital',
    ]);

    SupportThread::factory()->create([
        'subject' => 'طلب دعم نماء',
        'email' => 'support@namaa.test',
        'status' => SupportThread::STATUS_IN_PROGRESS,
    ]);

    NewsletterSubscriber::factory()->subscribed()->create([
        'email' => 'namaa@example.com',
    ]);

    $this->get(route('admin.search', ['q' => 'namaa']))
        ->assertOk()
        ->assertSee('نتائج البحث', false)
        ->assertSee('المستأجرون', false)
        ->assertSee('الرسائل والدعم', false)
        ->assertSee('مشتركو النشرة', false)
        ->assertSee('namaa-digital', false)
        ->assertSee('support@namaa.test', false)
        ->assertSee('namaa@example.com', false)
        ->assertSee('عرض', false);
});

test('search suggestion links point at entity destinations with highlight anchors', function () {
    $tenant = Tenant::factory()->create([
        'name' => 'مجموعة رواد',
        'slug' => 'ruwwad-group',
    ]);

    $thread = SupportThread::factory()->create([
        'subject' => 'محادثة رواد',
        'status' => SupportThread::STATUS_OPEN,
    ]);

    $subscriber = NewsletterSubscriber::factory()->subscribed()->create([
        'email' => 'ruwwad@example.com',
    ]);

    $payload = $this->getJson(route('admin.search.suggest', ['q' => 'رواد']))->json();

    $items = collect($payload['groups'])->flatMap(fn (array $g) => $g['items']);

    expect($items->pluck('url'))->toContain(route('admin.tenants.show', $tenant))
        ->and($items->firstWhere('title', 'محادثة رواد')['anchor'] ?? null)->toBe('mada-search-thread-'.$thread->id)
        ->and($items->firstWhere('title', 'محادثة رواد')['mode'] ?? null)->toBe('scroll');

    $newsletterPayload = $this->getJson(route('admin.search.suggest', ['q' => 'ruwwad@']))->json();
    $newsletterItem = collect($newsletterPayload['groups'] ?? [])
        ->flatMap(fn (array $g) => $g['items'])
        ->firstWhere('title', $subscriber->email);

    expect($newsletterItem)->not->toBeNull()
        ->and($newsletterItem['anchor'])->toBe('mada-search-subscriber-'.$subscriber->id)
        ->and($newsletterItem['url'])->toContain('highlight=subscriber-'.$subscriber->id);
});

test('newsletter and messages pages expose in-page search anchors', function () {
    $subscriber = NewsletterSubscriber::factory()->subscribed()->create([
        'email' => 'anchor@example.com',
    ]);

    $thread = SupportThread::factory()->create([
        'status' => SupportThread::STATUS_OPEN,
        'subject' => 'مرساة بحث',
    ]);

    $this->get(route('admin.newsletter.index'))
        ->assertOk()
        ->assertSee('mada-search-subscriber-'.$subscriber->id, false)
        ->assertSee('data-mada-search="subscriber-'.$subscriber->id.'"', false);

    $this->get(route('admin.messages', ['status' => 'open']))
        ->assertOk()
        ->assertSee('mada-search-thread-'.$thread->id, false)
        ->assertSee('data-mada-search="thread-'.$thread->id.'"', false)
        ->assertSee('mada-search-flash', false);
});
