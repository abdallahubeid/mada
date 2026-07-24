<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * Terms of Service legal page (docs/MARKETING.md §2).
 */
class TermsController extends Controller
{
    public function __invoke(): View
    {
        return view('marketing.terms');
    }
}
