<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Marketing\MarketingCache;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
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
        $fileKeys = ['site_logo', 'site_favicon', 'previews_img', 'previews_video'];

        /*
             * `is_video_section_active` is excluded from the generic text rules
             * and handled explicitly below — an unchecked checkbox submits
             * NOTHING, so it can never be validated or written by the loop that
             * follows. Left to the default path, switching the section OFF would
             * appear to save and then silently do nothing.
             */
        $textKeys = collect(Setting::landingKeys())
            ->reject(fn (string $key): bool => in_array($key, [...$fileKeys, 'is_video_section_active'], true))
            ->all();

        $rules = [
            'site_logo' => ['nullable', 'file', 'mimes:jpeg,jpg,png,gif,webp,svg', 'max:4096'],
            'site_favicon' => ['nullable', 'file', 'mimes:png,svg,ico', 'max:2048'],
            'previews_img' => ['nullable', 'file', 'mimes:jpeg,jpg,png,gif,webp,svg', 'max:8192'],
            'previews_video' => ['nullable', 'file', 'mimes:mp4,webm,ogg', 'max:51200'],
            // A pasted CDN link, or blank to fall back to the uploaded file.
            'video_url' => ['nullable', 'string', 'url', 'max:2048'],
            'is_video_section_active' => ['nullable', 'boolean'],
        ];

        foreach ($textKeys as $key) {
            $rules[$key] = ['nullable', 'string', 'max:10000'];
        }

        $validated = $request->validate($rules);

        // Absent means unchecked, which is a real value ('0'), not "leave alone".
        $validated['is_video_section_active'] = $request->boolean('is_video_section_active') ? '1' : '0';

        foreach ($fileKeys as $fileKey) {
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

        flash()->info('تم تحديث الإعدادات بنجاح.');

        return redirect()->route('admin.landing.settings.edit');
    }

    public function destroyImage(string $key): JsonResponse
    {
        Setting::clearUploadedFile($key);
        MarketingCache::flush();

        return response()->json(['success' => true]);
    }
}
