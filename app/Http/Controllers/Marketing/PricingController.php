<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Services\Marketing\MarketingContent;
use Illuminate\Contracts\View\View;

/**
 * Pricing marketing page (docs/MARKETING.md §2–§3). Plan tiers from DB via MarketingContent.
 */
class PricingController extends Controller
{
    public function __construct(private MarketingContent $marketing) {}

    public function __invoke(): View
    {
        return view('marketing.pricing', [
            'plans' => $this->marketing->plans(),
            'currency' => $this->marketing->currencySymbol(),
            'faqs' => $this->marketing->faqs(4),
        ]);
    }
}
