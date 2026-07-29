<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFaqRequest;
use App\Http\Requests\Admin\UpdateFaqRequest;
use App\Models\Faq;
use App\Services\Admin\TrashManager;
use App\Services\Marketing\MarketingCache;
use App\Services\Platform\PlatformAuditor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class FaqController extends Controller
{
    public function index(): View
    {
        return view('admin.faqs.index', [
            'faqs' => Faq::query()->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.faqs.create', [
            'faq' => new Faq([
                'category' => 'عام',
                'sort_order' => (int) Faq::query()->max('sort_order') + 1,
                'is_published' => true,
            ]),
        ]);
    }

    public function store(StoreFaqRequest $request, PlatformAuditor $auditor): RedirectResponse
    {
        $faq = Faq::query()->create($request->validated());

        MarketingCache::flush();
        $auditor->log('faq.created', $faq);

        flash()->success('تم إنشاء السؤال بنجاح.');

        return redirect()->route('admin.faqs.index');
    }

    public function edit(Faq $faq): View
    {
        return view('admin.faqs.edit', ['faq' => $faq]);
    }

    public function update(UpdateFaqRequest $request, Faq $faq, PlatformAuditor $auditor): RedirectResponse
    {
        $faq->update($request->validated());

        MarketingCache::flush();
        $auditor->log('faq.updated', $faq);

        flash()->info('تم تحديث السؤال بنجاح.');

        return redirect()->route('admin.faqs.index');
    }

    public function destroy(Faq $faq, PlatformAuditor $auditor): RedirectResponse
    {
        $faq->delete();

        MarketingCache::flush();
        $auditor->log('faq.deleted', $faq);

        app(TrashManager::class)->flashSoftDeleted('تم حذف السؤال بنجاح.', 'faqs', $faq);

        return redirect()->route('admin.faqs.index');
    }
}
