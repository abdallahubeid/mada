<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Offering;
use App\Services\Admin\TrashManager;
use App\Services\Marketing\MarketingCache;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class OfferingController extends Controller
{
    public function index(): View
    {
        return view('admin.landing.offerings.index', [
            'offerings' => Offering::query()->with('images')->latest()->paginate(config('app.paginate_page')),
        ]);
    }

    public function create(): View
    {
        return view('admin.landing.offerings.create', [
            'offering' => new Offering([
                'sort_order' => (int) Offering::query()->max('sort_order') + 1,
                'is_published' => true,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'icon' => ['nullable', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_published' => ['sometimes', 'boolean'],
            'icon_image' => ['nullable', 'file', 'mimes:jpeg,jpg,png,gif,webp,svg', 'max:2048'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        $offering = Offering::query()->create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'icon' => $validated['icon'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published'),
        ]);

        $this->syncIconImage($request, $offering);

        MarketingCache::flush();
        flash()->success('تم إنشاء العرض بنجاح.');

        return redirect()->route('admin.offerings.index');
    }

    public function edit(Offering $offering): View
    {
        $offering->load('images');

        return view('admin.landing.offerings.edit', [
            'offering' => $offering,
        ]);
    }

    public function update(Request $request, Offering $offering): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'icon' => ['nullable', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_published' => ['sometimes', 'boolean'],
            'icon_image' => ['nullable', 'file', 'mimes:jpeg,jpg,png,gif,webp,svg', 'max:2048'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        $offering->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'icon' => $validated['icon'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published'),
        ]);

        $this->syncIconImage($request, $offering);

        MarketingCache::flush();
        flash()->info('تم تحديث العرض بنجاح.');

        return redirect()->route('admin.offerings.index');
    }

    public function destroy(Offering $offering): RedirectResponse
    {
        $offering->images->each->delete();
        $offering->delete();

        MarketingCache::flush();
        app(TrashManager::class)->flashSoftDeleted('تم حذف العرض بنجاح.', 'offerings', $offering);

        return redirect()->route('admin.offerings.index');
    }

    private function syncIconImage(Request $request, Offering $offering): void
    {
        if (! $request->hasFile('icon_image')) {
            return;
        }

        /** @var UploadedFile $file */
        $file = $request->file('icon_image');

        $offering->images()->where('collection', 'icon')->get()->each->forceDelete();

        $path = $file->store('offering/icon', 'custom');

        $offering->images()->create([
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
