<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Services\Marketing\MarketingCache;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class FeatureController extends Controller
{
    public function index(): View
    {
        return view('admin.landing.features.index', [
            'features' => Feature::query()->with('images')->latest()->paginate(config('app.paginate_page')),
        ]);
    }

    public function create(): View
    {
        return view('admin.landing.features.create', [
            'feature' => new Feature([
                'sort_order' => (int) Feature::query()->max('sort_order') + 1,
                'is_published' => true,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'icon_key' => ['nullable', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_published' => ['sometimes', 'boolean'],
            'icon' => ['nullable', 'file', 'mimes:jpeg,jpg,png,gif,webp,svg', 'max:2048'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        $feature = Feature::query()->create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'icon_key' => $validated['icon_key'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published'),
        ]);

        $this->syncIcon($request, $feature);

        MarketingCache::flush();
        flash()->success('تم إنشاء الميزة بنجاح.');

        return redirect()->route('admin.features.index');
    }

    public function edit(Feature $feature): View
    {
        $feature->load('images');

        return view('admin.landing.features.edit', [
            'feature' => $feature,
        ]);
    }

    public function update(Request $request, Feature $feature): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'icon_key' => ['nullable', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_published' => ['sometimes', 'boolean'],
            'icon' => ['nullable', 'file', 'mimes:jpeg,jpg,png,gif,webp,svg', 'max:2048'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        $feature->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'icon_key' => $validated['icon_key'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published'),
        ]);

        $this->syncIcon($request, $feature);

        MarketingCache::flush();
        flash()->info('تم تحديث الميزة بنجاح.');

        return redirect()->route('admin.features.index');
    }

    public function destroy(Feature $feature): RedirectResponse
    {
        $feature->images->each->delete();
        $feature->delete();

        MarketingCache::flush();
        flash()->warning('تم حذف الميزة بنجاح.');

        return redirect()->route('admin.features.index');
    }

    private function syncIcon(Request $request, Feature $feature): void
    {
        if (! $request->hasFile('icon')) {
            return;
        }

        /** @var UploadedFile $file */
        $file = $request->file('icon');

        $feature->images()->where('collection', 'icon')->get()->each->delete();

        $path = $file->store('feature/icon', 'custom');

        $feature->images()->create([
            'collection' => 'icon',
            'disk' => 'custom',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'alt_text' => $request->input('alt_text'),
            'sort_order' => 0,
        ]);
    }
}
