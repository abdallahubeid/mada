<?php

use App\Models\Faq;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use App\Models\PlatformNotification;
use App\Models\Problem;
use App\Models\Setting;
use App\Models\SupportMessage;
use App\Models\SupportThread;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    actingAsPlatformOperator();
});

test('core models soft delete instead of removing rows', function (string $modelClass, callable $factory) {
    /** @var Model $record */
    $record = $factory();

    $record->delete();

    $this->assertSoftDeleted($record->getTable(), ['id' => $record->id]);
    expect($modelClass::query()->find($record->id))->toBeNull()
        ->and($modelClass::withTrashed()->find($record->id))->not->toBeNull();
})->with([
    'users' => [
        User::class,
        fn () => User::factory()->create(),
    ],
    'problems' => [
        Problem::class,
        fn () => Problem::query()->create([
            'title' => 'Soft delete problem',
            'description' => 'Kept after delete',
            'icon_key' => 'alert',
            'sort_order' => 1,
            'is_published' => true,
        ]),
    ],
    'faqs' => [
        Faq::class,
        fn () => Faq::factory()->create(),
    ],
    'newsletter_subscribers' => [
        NewsletterSubscriber::class,
        fn () => NewsletterSubscriber::factory()->create(),
    ],
    'newsletter_campaigns' => [
        NewsletterCampaign::class,
        fn () => NewsletterCampaign::factory()->create(),
    ],
    'platform_notifications' => [
        PlatformNotification::class,
        fn () => PlatformNotification::factory()->create(),
    ],
    'settings' => [
        Setting::class,
        fn () => Setting::query()->create([
            'key' => 'soft_delete_probe_'.uniqid(),
            'value' => 'x',
        ]),
    ],
    'support_threads' => [
        SupportThread::class,
        fn () => SupportThread::factory()->create(),
    ],
]);

test('support messages soft delete with deleted_at set', function () {
    $thread = SupportThread::factory()->create();
    $message = SupportMessage::factory()->for($thread, 'thread')->create();

    $message->delete();

    $this->assertSoftDeleted('support_messages', ['id' => $message->id]);
});

test('image soft delete keeps disk file until forceDelete', function () {
    Storage::fake('custom');
    Storage::disk('custom')->put('tests/soft-delete.png', 'png-bytes');

    $user = User::factory()->create();
    $image = $user->images()->create([
        'collection' => 'avatar',
        'disk' => 'custom',
        'path' => 'tests/soft-delete.png',
        'alt_text' => 'probe',
    ]);

    $image->delete();

    $this->assertSoftDeleted('images', ['id' => $image->id]);
    Storage::disk('custom')->assertExists('tests/soft-delete.png');

    $image->forceDelete();

    $this->assertDatabaseMissing('images', ['id' => $image->id]);
    Storage::disk('custom')->assertMissing('tests/soft-delete.png');
});

test('admin problem destroy soft deletes the card', function () {
    $problem = Problem::query()->create([
        'title' => 'حذف ناعم',
        'description' => 'وصف',
        'icon_key' => 'alert',
        'sort_order' => 1,
        'is_published' => true,
    ]);

    $this->delete(route('admin.problems.destroy', $problem))
        ->assertRedirect(route('admin.problems.index'));

    $this->assertSoftDeleted('problems', ['id' => $problem->id]);
});
