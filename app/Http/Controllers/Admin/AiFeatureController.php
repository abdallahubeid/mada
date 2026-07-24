<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiFeature;
use App\Services\Marketing\MarketingCache;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class AiFeatureController extends Controller
{
    public function index(): View
    {
        return view('admin.landing.ai-features.index', [
            'aiFeatures' => AiFeature::query()->with('images')->latest()->paginate(config('app.paginate_page')),
        ]);
    }

    public function create(): View
    {
        return view('admin.landing.ai-features.create', [
            'aiFeature' => new AiFeature([
                'sort_order' => (int) AiFeature::query()->max('sort_order') + 1,
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

        $aiFeature = AiFeature::query()->create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'icon_key' => $validated['icon_key'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published'),
        ]);

        $this->syncIcon($request, $aiFeature);

        MarketingCache::flush();
        flash()->success('تم إنشاء ميزة الذكاء الاصطناعي بنجاح.');

        return redirect()->route('admin.ai-features.index');
    }

    public function edit(AiFeature $aiFeature): View
    {
        $aiFeature->load('images');

        return view('admin.landing.ai-features.edit', [
            'aiFeature' => $aiFeature,
        ]);
    }

    public function update(Request $request, AiFeature $aiFeature): RedirectResponse
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

        $aiFeature->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'icon_key' => $validated['icon_key'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published'),
        ]);

        $this->syncIcon($request, $aiFeature);

        MarketingCache::flush();
        flash()->info('تم تحديث ميزة الذكاء الاصطناعي بنجاح.');

        return redirect()->route('admin.ai-features.index');
    }

    public function destroy(AiFeature $aiFeature): RedirectResponse
    {
        $aiFeature->images->each->delete();
        $aiFeature->delete();

        MarketingCache::flush();
        flash()->warning('تم حذف ميزة الذكاء الاصطناعي بنجاح.');

        return redirect()->route('admin.ai-features.index');
    }

    private function syncIcon(Request $request, AiFeature $aiFeature): void
    {
        if (! $request->hasFile('icon')) {
            return;
        }

        /** @var UploadedFile $file */
        $file = $request->file('icon');

        $aiFeature->images()->where('collection', 'icon')->get()->each->delete();

        $path = $file->store('aifeature/icon', 'custom');

        $aiFeature->images()->create([
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
