<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
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
        /** @var Collection<int, array{id: string, title: string, items: list<array{category: string, question: string, answer: string}>}> $categories */
        $categories = collect($this->marketing->faqs())
            ->groupBy('category')
            ->values()
            ->map(fn (Collection $items, int $index): array => [
                'id' => 'cat-'.($index + 1),
                'title' => (string) $items->first()['category'],
                'items' => $items->values()->all(),
            ]);

        return view('marketing.faq', [
            'categories' => $categories,
        ]);
    }
}
