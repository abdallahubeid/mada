<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminChromeBadges;
use Illuminate\Http\JsonResponse;

/**
 * Platform console chrome endpoints (TopBar badges poller).
 */
class ChromeController extends Controller
{
    public function __construct(private AdminChromeBadges $badges) {}

    public function poll(): JsonResponse
    {
        return response()->json($this->badges->snapshot());
    }
}
