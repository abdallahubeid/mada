<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * Two-Factor Challenge (docs/MODULES.md §6, ADR-14). Standalone gate shown
 * after primary credentials succeed but before a session is granted. Rendered
 * outside the console shell on the minimal guest layout. Frontend slice: the
 * form posts nowhere yet — verification logic lands with the backend phase.
 */
class TwoFactorChallengeController extends Controller
{
    public function __invoke(): View
    {
        return view('auth.two-factor-challenge');
    }
}
