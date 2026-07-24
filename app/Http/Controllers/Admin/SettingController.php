<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * Landing-page key/value settings CMS (docs/LANDING_CMS_IMPLEMENTATION.md).
 */
class SettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.landing.settings.index', [
            'settings' => Setting::map(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $textKeys = collect(Setting::landingKeys())
            ->reject(fn (string $key): bool => in_array($key, ['site_logo', 'previews_img', 'previews_video'], true))
            ->all();

        $rules = [
            'site_logo' => ['nullable', 'file', 'mimes:jpeg,jpg,png,gif,webp,svg', 'max:4096'],
            'previews_img' => ['nullable', 'file', 'mimes:jpeg,jpg,png,gif,webp,svg', 'max:8192'],
            'previews_video' => ['nullable', 'file', 'mimes:mp4,webm,ogg', 'max:51200'],
        ];

        foreach ($textKeys as $key) {
            $rules[$key] = ['nullable', 'string', 'max:10000'];
        }

        $validated = $request->validate($rules);

        foreach (['site_logo', 'previews_img', 'previews_video'] as $fileKey) {
            if ($request->hasFile($fileKey)) {
                /** @var UploadedFile $file */
                $file = $request->file($fileKey);
                $validated[$fileKey] = $file->store('uploads/settings', 'custom');
            } else {
                unset($validated[$fileKey]);
            }
        }

        foreach ($validated as $key => $value) {
            if (! is_string($key) || $key === '') {
                continue;
            }

            if (! in_array($key, Setting::landingKeys(), true)) {
                continue;
            }

            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => is_scalar($value) || $value === null ? $value : json_encode($value)],
            );
        }

        flash()->info('Settings Updated successfully');

        return redirect()->route('admin.landing.settings.edit');
    }
}
