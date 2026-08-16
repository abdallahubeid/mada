<?php

use App\Models\Image;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('profile page renders authenticated user details', function () {
    actingAsPlatformOperator(attributes: [
        'name' => 'سارة المطيري',
        'email' => 'sara@mada.test',
        'phone' => '+966501234567',
        'job_title' => 'مديرة تقنية',
    ]);

    $this->get(route('admin.profile'))
        ->assertOk()
        ->assertSee('سارة المطيري', false)
        ->assertSee('sara@mada.test', false)
        ->assertSee('مديرة تقنية', false)
        ->assertSee('المعلومات الشخصية', false)
        ->assertSee('الأمان وكلمة المرور', false)
        ->assertSee('متحقق منه', false)
        ->assertSee('إعادة ضبط / قص الصورة', false)
        ->assertSee('cropper.min.js', false);
});

test('verified badge is placed beside the email label not inside the input', function () {
    actingAsPlatformOperator(attributes: [
        'email' => 'owner@mada.test',
        'email_verified_at' => now(),
    ]);

    $html = $this->get(route('admin.profile'))
        ->assertOk()
        ->assertSee('owner@mada.test', false)
        ->assertSee('متحقق منه', false)
        ->getContent();

    expect($html)
        ->toContain('البريد الإلكتروني')
        ->not->toContain('pe-28')
        ->and(str_contains($html, 'absolute inset-y-0 end-2') && str_contains($html, 'متحقق منه'))
        ->toBeFalse();
});

test('profile update persists personal information', function () {
    $user = actingAsPlatformOperator(attributes: [
        'name' => 'Old Name',
        'email' => 'old@mada.test',
    ]);

    $this->put(route('admin.profile.update'), [
        'name' => 'New Name',
        'email' => 'new@mada.test',
        'phone' => '+966509998877',
        'job_title' => 'Product Manager',
    ])
        ->assertRedirect(route('admin.profile'));

    $user->refresh();

    expect($user->name)->toBe('New Name')
        ->and($user->email)->toBe('new@mada.test')
        ->and($user->phone)->toBe('+966509998877')
        ->and($user->job_title)->toBe('Product Manager')
        ->and($user->email_verified_at)->toBeNull()
        ->and(session('flasher.message'))->toBe('تم تحديث الملف الشخصي بنجاح.');
});

test('profile update creates polymorphic avatar image', function () {
    Storage::fake('custom');

    $user = actingAsPlatformOperator();

    $this->put(route('admin.profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'avatar' => UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg'),
    ])
        ->assertRedirect(route('admin.profile'));

    $user->refresh();
    $avatar = $user->avatar;

    expect($avatar)->toBeInstanceOf(Image::class)
        ->and($avatar->collection)->toBe('avatar')
        ->and($avatar->disk)->toBe('custom')
        ->and($avatar->imageable_type)->toBe(User::class)
        ->and($avatar->imageable_id)->toBe($user->id)
        ->and($user->avatar_url)->toBe(Storage::disk('custom')->url($avatar->path))
        ->and($user->avatar_url)->not->toStartWith('data:image/svg+xml');

    Storage::disk('custom')->assertExists($avatar->path);
});

test('profile update replaces existing avatar and deletes old file', function () {
    Storage::fake('custom');

    $user = actingAsPlatformOperator();
    $oldPath = 'user/avatar/old.png';
    Storage::disk('custom')->put($oldPath, 'old-avatar');

    $old = $user->images()->create([
        'collection' => 'avatar',
        'disk' => 'custom',
        'path' => $oldPath,
        'original_name' => 'old.png',
        'mime_type' => 'image/png',
        'file_size' => 9,
        'sort_order' => 0,
    ]);

    $this->put(route('admin.profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'avatar' => UploadedFile::fake()->create('new-avatar.png', 120, 'image/png'),
    ])
        ->assertRedirect(route('admin.profile'));

    $user->refresh();

    expect(Image::query()->find($old->id))->toBeNull()
        ->and($user->avatar)->not->toBeNull()
        ->and($user->images()->where('collection', 'avatar')->count())->toBe(1);

    Storage::disk('custom')->assertMissing($oldPath);
    Storage::disk('custom')->assertExists($user->avatar->path);
});

/*
 * ─────────────────────────────────────────────────────────────────────────────
 * A FAILED UPLOAD MUST NOT 500, AND MUST NOT COST THE USER THEIR OLD AVATAR.
 *
 * Reported from production as a 500 on /admin/profile. The `custom` disk roots
 * at public_path(''), and in the container that directory is owned by root
 * while the process runs as www-data — so `$file->store()` raised
 * League\Flysystem\UnableToCreateDirectory.
 *
 * The disk is configured `'throw' => false`, which is why this was not caught
 * earlier by inspection: that setting only swallows UnableToWriteFile.
 * UnableToCreateDirectory is a different class and escaped as an unhandled 500.
 *
 * The old code also deleted the existing avatar BEFORE storing the new one, so
 * the failure destroyed the avatar the user already had.
 *
 * Config::set on the disk root reproduces an unwritable target without needing
 * real filesystem permissions, which cannot be arranged portably on Windows.
 * ─────────────────────────────────────────────────────────────────────────────
 */
test('avatar upload failure degrades gracefully instead of returning 500', function () {
    $user = actingAsPlatformOperator();

    $oldPath = 'user/avatar/keep-me.png';
    Storage::fake('custom');
    Storage::disk('custom')->put($oldPath, 'old-avatar');

    $old = $user->images()->create([
        'collection' => 'avatar',
        'disk' => 'custom',
        'path' => $oldPath,
        'original_name' => 'keep-me.png',
        'mime_type' => 'image/png',
        'file_size' => 9,
        'sort_order' => 0,
    ]);

    // Point the disk at a root that cannot be created, then drop the resolved
    // instance so the next call rebuilds it against the new config.
    config()->set('filesystems.disks.custom.root', 'Z:/unwritable-'.uniqid());
    Storage::forgetDisk('custom');

    $response = $this->put(route('admin.profile.update'), [
        'name' => 'اسم محدّث',
        'email' => $user->email,
        'avatar' => UploadedFile::fake()->create('new.png', 120, 'image/png'),
    ]);

    // Not a 500, and not a silent success either.
    $response->assertRedirect(route('admin.profile'));

    expect(session('flasher.type'))->toBe('warning');

    $user->refresh();

    // The rest of the form still saved — a broken upload must not discard the
    // name change that already committed.
    expect($user->name)->toBe('اسم محدّث')
        // The previous avatar survives: still exactly one, still the old row.
        ->and($user->images()->where('collection', 'avatar')->count())->toBe(1)
        ->and($user->avatar->id)->toBe($old->id)
        ->and($user->avatar->path)->toBe($oldPath);
});

test('profile password update requires current password and updates hash', function () {
    $user = actingAsPlatformOperator(attributes: [
        'password' => 'password',
    ]);

    $this->from(route('admin.profile'))
        ->put(route('admin.profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])
        ->assertRedirect(route('admin.profile'))
        ->assertSessionHasErrors('current_password');

    $this->put(route('admin.profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'current_password' => 'password',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ])
        ->assertRedirect(route('admin.profile'));

    expect(Hash::check('NewPassword123!', $user->fresh()->password))->toBeTrue();
});

test('profile page does not render raw avatar storage path as plain text', function () {
    Storage::fake('custom');

    $user = actingAsPlatformOperator();
    $path = 'user/avatar/sample.png';
    Storage::disk('custom')->put($path, 'avatar-bytes');

    $user->images()->create([
        'collection' => 'avatar',
        'disk' => 'custom',
        'path' => $path,
        'original_name' => 'sample.png',
        'mime_type' => 'image/png',
        'file_size' => 12,
        'sort_order' => 0,
    ]);

    $user = $user->fresh();
    $expectedUrl = Storage::disk('custom')->url($path);

    expect($user->avatar_url)->toBe($expectedUrl)
        ->and($user->avatar?->disk)->toBe('custom');

    $html = $this->get(route('admin.profile'))
        ->assertOk()
        ->getContent();

    expect($html)
        ->not->toContain('dir="ltr">'.$path)
        ->and($html)->toContain('madaProfileAvatar');
});

test('admin topbar renders the authenticated user avatar url', function () {
    Storage::fake('custom');

    $user = actingAsPlatformOperator(attributes: ['name' => 'Demo Owner']);
    $path = 'user/avatar/topbar-avatar.jpg';
    Storage::disk('custom')->put($path, 'avatar-bytes');

    $user->images()->create([
        'collection' => 'avatar',
        'disk' => 'custom',
        'path' => $path,
        'original_name' => 'topbar-avatar.jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 12,
        'sort_order' => 0,
    ]);

    $avatarUrl = $user->fresh()->avatar_url;

    $html = $this->get(route('admin.profile'))
        ->assertOk()
        ->assertSee('Demo Owner', false)
        ->getContent();

    expect($html)
        ->toContain('h-8 w-8 rounded-full border border-slate-700 object-cover')
        ->and(substr_count($html, $avatarUrl))->toBeGreaterThanOrEqual(2);
});

test('custom disk avatar urls omit the public path segment', function () {
    $path = 'user/avatar/BAasBQZPselXgOVhN963L1qnsh9S2tV6XsZnlQ5t.jpg';

    config([
        'filesystems.disks.custom.url' => 'http://localhost:8000',
        'filesystems.disks.custom.root' => public_path(''),
    ]);

    $url = Storage::disk('custom')->url($path);

    expect($url)->toBe('http://localhost:8000/'.$path)
        ->and($url)->not->toContain('/public/user/')
        ->and(asset($path))->toBe($url);
});

test('user avatar url falls back to svg data uri when no image exists', function () {
    $user = User::factory()->create(['name' => 'Mada Admin']);

    expect($user->avatar_url)->toStartWith('data:image/svg+xml;base64,')
        ->and($user->avatar())->toBeInstanceOf(MorphOne::class);
});
