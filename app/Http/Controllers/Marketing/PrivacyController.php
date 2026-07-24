<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * Privacy Policy legal page (docs/MARKETING.md §2).
 */
class PrivacyController extends Controller
{
    public function __invoke(): View
    {
        return view('marketing.privacy');
    }
}
