<?php

use App\Domain\Platform\PlatformPermissionCatalog;
use App\Models\User;
use Database\Seeders\PlatformRolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change this if the default isn't what you want.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing expectations, you can use the `expect()` function. Of course,
| you may also use Pest's expectation API to extend the Expectation class.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function seedPlatformPermissions(): void
{
    test()->seed(PlatformRolesAndPermissionsSeeder::class);
    PlatformPermissionCatalog::bindTeam();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
}

/**
 * Create a platform operator and authenticate as them.
 *
 * @param  array<string, mixed>  $attributes
 */
function actingAsPlatformOperator(string $role = PlatformPermissionCatalog::ROLE_SUPER_ADMIN, array $attributes = []): User
{
    seedPlatformPermissions();

    $user = User::factory()->create(array_merge([
        'tenant_id' => null,
    ], $attributes));

    PlatformPermissionCatalog::bindTeam();
    $user->assignRole($role);

    test()->actingAs($user);

    return $user;
}
