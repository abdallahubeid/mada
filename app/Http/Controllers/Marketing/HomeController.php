<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Services\Marketing\MarketingContent;
use Illuminate\Contracts\View\View;

/**
 * Landing page (docs/MARKETING.md §4) — assembles section data via MarketingContent.
 */
class HomeController extends Controller
{
    public function __construct(private MarketingContent $marketing) {}

    public function __invoke(): View
    {
        return view('marketing.home', [
            'content' => $this->marketing->home(),
        ]);
    }
}
