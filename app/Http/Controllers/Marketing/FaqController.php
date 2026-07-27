<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Services\Marketing\MarketingContent;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

/**
 * Full FAQ page — published faqs from the database, grouped by category.
 */
class FaqController extends Controller
{
    public function __construct(private MarketingContent $marketing) {}

    public function __invoke(): View
    {
        /** @var Collection<int, array{id: string, title: string, items: \Illuminate\Database\Eloquent\Collection<int, Faq>}> $categories */
        $categories = $this->marketing->faqs()
            ->groupBy('category')
            ->values()
            ->map(fn (Collection $items, int $index): array => [
                'id' => 'cat-'.($index + 1),
                'title' => (string) $items->first()->category,
                'items' => $items->values(),
            ]);

        return view('marketing.faq', [
            'categories' => $categories,
        ]);
    }
}
