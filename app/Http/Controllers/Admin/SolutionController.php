<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Solution;
use App\Services\Admin\TrashManager;
use App\Services\Marketing\MarketingCache;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class SolutionController extends Controller
{
    public function index(): View
    {
        return view('admin.landing.solutions.index', [
            'solutions' => Solution::query()->with('images')->latest()->paginate(config('app.paginate_page')),
        ]);
    }

    public function create(): View
    {
        return view('admin.landing.solutions.create', [
            'solution' => new Solution([
                'sort_order' => (int) Solution::query()->max('sort_order') + 1,
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

        $solution = Solution::query()->create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'icon' => $validated['icon'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published'),
        ]);

        $this->syncIconImage($request, $solution);

        MarketingCache::flush();
        flash()->success('تم إنشاء الحل بنجاح.');

        return redirect()->route('admin.solutions.index');
    }

    public function edit(Solution $solution): View
    {
        $solution->load('images');

        return view('admin.landing.solutions.edit', [
            'solution' => $solution,
        ]);
    }

    public function update(Request $request, Solution $solution): RedirectResponse
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

        $solution->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'icon' => $validated['icon'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published'),
        ]);

        $this->syncIconImage($request, $solution);

        MarketingCache::flush();
        flash()->info('تم تحديث الحل بنجاح.');

        return redirect()->route('admin.solutions.index');
    }

    public function destroy(Solution $solution): RedirectResponse
    {
        $solution->images->each->delete();
        $solution->delete();

        MarketingCache::flush();
        app(TrashManager::class)->flashSoftDeleted('تم حذف الحل بنجاح.', 'solutions', $solution);

        return redirect()->route('admin.solutions.index');
    }

    private function syncIconImage(Request $request, Solution $solution): void
    {
        if (! $request->hasFile('icon_image')) {
            return;
        }

        /** @var UploadedFile $file */
        $file = $request->file('icon_image');

        $solution->images()->where('collection', 'icon')->get()->each->forceDelete();

        $path = $file->store('solution/icon', 'custom');

        $solution->images()->create([
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
