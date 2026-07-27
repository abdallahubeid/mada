<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use App\Services\Marketing\MarketingCache;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class TestimonialController extends Controller
{
    public function index(): View
    {
        return view('admin.landing.testimonials.index', [
            'testimonials' => Testimonial::query()->with('images')->latest()->paginate(config('app.paginate_page')),
        ]);
    }

    public function create(): View
    {
        return view('admin.landing.testimonials.create', [
            'testimonial' => new Testimonial([
                'sort_order' => (int) Testimonial::query()->max('sort_order') + 1,
                'is_published' => true,
                'rate' => 5,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'quote' => ['required', 'string', 'max:2000'],
            'client_name' => ['required', 'string', 'max:120'],
            'client_role' => ['nullable', 'string', 'max:120'],
            'organization_name' => ['nullable', 'string', 'max:120'],
            'rate' => ['nullable', 'integer', 'min:1', 'max:5'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_published' => ['sometimes', 'boolean'],
            'avatar' => ['nullable', 'file', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'],
            'logo' => ['nullable', 'file', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        $testimonial = Testimonial::query()->create([
            'quote' => $validated['quote'],
            'client_name' => $validated['client_name'],
            'client_role' => $validated['client_role'] ?? null,
            'organization_name' => $validated['organization_name'] ?? null,
            'rate' => $validated['rate'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published'),
        ]);

        $this->syncAvatar($request, $testimonial);

        MarketingCache::flush();
        flash()->success('تم إنشاء الشهادة بنجاح.');

        return redirect()->route('admin.testimonials.index');
    }

    public function edit(Testimonial $testimonial): View
    {
        $testimonial->load('images');

        return view('admin.landing.testimonials.edit', ['testimonial' => $testimonial]);
    }

    public function update(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $validated = $request->validate([
            'quote' => ['required', 'string', 'max:2000'],
            'client_name' => ['required', 'string', 'max:120'],
            'client_role' => ['nullable', 'string', 'max:120'],
            'organization_name' => ['nullable', 'string', 'max:120'],
            'rate' => ['nullable', 'integer', 'min:1', 'max:5'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_published' => ['sometimes', 'boolean'],
            'avatar' => ['nullable', 'file', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'],
            'logo' => ['nullable', 'file', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        $testimonial->update([
            'quote' => $validated['quote'],
            'client_name' => $validated['client_name'],
            'client_role' => $validated['client_role'] ?? null,
            'organization_name' => $validated['organization_name'] ?? null,
            'rate' => $validated['rate'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published'),
        ]);

        $this->syncAvatar($request, $testimonial);

        MarketingCache::flush();
        flash()->info('تم تحديث الشهادة بنجاح.');

        return redirect()->route('admin.testimonials.index');
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $testimonial->images->each->delete();
        $testimonial->delete();

        MarketingCache::flush();
        flash()->warning('تم حذف الشهادة بنجاح.');

        return redirect()->route('admin.testimonials.index');
    }

    private function syncAvatar(Request $request, Testimonial $testimonial): void
    {
        /** @var UploadedFile|null $file */
        $file = $request->file('avatar') ?? $request->file('logo');

        if ($file === null) {
            return;
        }

        $testimonial->images()->whereIn('collection', ['avatar', 'logo'])->get()->each->forceDelete();

        $path = $file->store('testimonial/avatar', 'custom');

        $testimonial->images()->create([
            'collection' => 'avatar',
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
