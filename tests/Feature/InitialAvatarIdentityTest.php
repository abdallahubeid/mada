<?php

use App\Domain\Tenancy\Models\Employee;
use App\Models\User;

/**
 * Decodes an initial-avatar data URI back to its SVG markup.
 */
function decodeInitialAvatar(string $dataUri): string
{
    expect($dataUri)->toStartWith('data:image/svg+xml;base64,');

    return base64_decode(substr($dataUri, strlen('data:image/svg+xml;base64,')));
}

test('user initial avatar is drawn in the orange identity, not emerald', function () {
    $svg = decodeInitialAvatar(User::make(['name' => 'سارة المطيري'])->initialAvatarDataUri());

    expect($svg)
        ->toContain('fill="#ffece4"')
        ->toContain('fill="#c2410c"')
        ->toContain('>س</text>')
        ->not->toContain('#10b981');
});

test('employee initial avatar matches the user avatar identity', function () {
    $employee = (new Employee)->forceFill(['first_name' => 'محمد', 'last_name' => 'الشمري']);

    $svg = decodeInitialAvatar($employee->avatarUrl());

    expect($svg)
        ->toContain('fill="#ffece4"')
        ->toContain('fill="#c2410c"')
        ->toContain('>م</text>')
        ->toContain('aria-label="م"')
        ->not->toContain('#10b981');
});
