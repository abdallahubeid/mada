<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Problem;
use App\Services\Admin\TrashManager;
use App\Services\Marketing\MarketingCache;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class ProblemController extends Controller
{
    public function index(): View
    {
        return view('admin.landing.problems.index', [
            'problems' => Problem::query()->with('images')->latest()->paginate(config('app.paginate_page')),
        ]);
    }

    public function create(): View
    {
        return view('admin.landing.problems.create', [
            'problem' => new Problem([
                'sort_order' => (int) Problem::query()->max('sort_order') + 1,
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

        $problem = Problem::query()->create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'icon_key' => $validated['icon_key'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published'),
        ]);

        $this->syncIcon($request, $problem);

        MarketingCache::flush();
        flash()->success('تم إنشاء المشكلة بنجاح.');

        return redirect()->route('admin.problems.index');
    }

    public function edit(Problem $problem): View
    {
        $problem->load('images');

        return view('admin.landing.problems.edit', [
            'problem' => $problem,
        ]);
    }

    public function update(Request $request, Problem $problem): RedirectResponse
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

        $problem->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'icon_key' => $validated['icon_key'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published'),
        ]);

        $this->syncIcon($request, $problem);

        MarketingCache::flush();
        flash()->info('تم تحديث المشكلة بنجاح.');

        return redirect()->route('admin.problems.index');
    }

    public function destroy(Problem $problem): RedirectResponse
    {
        $problem->images->each->delete();
        $problem->delete();

        MarketingCache::flush();
        app(TrashManager::class)->flashSoftDeleted('تم حذف المشكلة بنجاح.', 'problems', $problem);

        return redirect()->route('admin.problems.index');
    }

    private function syncIcon(Request $request, Problem $problem): void
    {
        if (! $request->hasFile('icon')) {
            return;
        }

        /** @var UploadedFile $file */
        $file = $request->file('icon');

        $problem->images()->where('collection', 'icon')->get()->each->forceDelete();

        $path = $file->store('problem/icon', 'custom');

        $problem->images()->create([
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
