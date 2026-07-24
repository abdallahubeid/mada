<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * About Us page (docs/MARKETING.md §2) — story, vision, and mission drawn
 * from docs/PROJECT_VISION.md.
 */
class AboutController extends Controller
{
    public function __invoke(): View
    {
        return view('marketing.about');
    }
}
