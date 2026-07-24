<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Services\Marketing\MarketingCache;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class ModuleController extends Controller
{
    public function index(): View
    {
        return view('admin.landing.modules.index', [
            'modules' => Module::query()->with('images')->latest()->paginate(config('app.paginate_page')),
        ]);
    }

    public function create(): View
    {
        return view('admin.landing.modules.create', [
            'module' => new Module([
                'sort_order' => (int) Module::query()->max('sort_order') + 1,
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

        $module = Module::query()->create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'icon_key' => $validated['icon_key'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published'),
        ]);

        $this->syncIcon($request, $module);

        MarketingCache::flush();
        flash()->success('تم إنشاء الوحدة بنجاح.');

        return redirect()->route('admin.modules.index');
    }

    public function edit(Module $module): View
    {
        $module->load('images');

        return view('admin.landing.modules.edit', [
            'module' => $module,
        ]);
    }

    public function update(Request $request, Module $module): RedirectResponse
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

        $module->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'icon_key' => $validated['icon_key'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published'),
        ]);

        $this->syncIcon($request, $module);

        MarketingCache::flush();
        flash()->info('تم تحديث الوحدة بنجاح.');

        return redirect()->route('admin.modules.index');
    }

    public function destroy(Module $module): RedirectResponse
    {
        $module->images->each->delete();
        $module->delete();

        MarketingCache::flush();
        flash()->warning('تم حذف الوحدة بنجاح.');

        return redirect()->route('admin.modules.index');
    }

    private function syncIcon(Request $request, Module $module): void
    {
        if (! $request->hasFile('icon')) {
            return;
        }

        /** @var UploadedFile $file */
        $file = $request->file('icon');

        $module->images()->where('collection', 'icon')->get()->each->delete();

        $path = $file->store('module/icon', 'custom');

        $module->images()->create([
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
