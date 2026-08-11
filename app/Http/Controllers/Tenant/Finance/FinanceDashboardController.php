<?php

namespace App\Http\Controllers\Tenant\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\FinanceDashboard;
use Illuminate\Contracts\View\View;

class FinanceDashboardController extends Controller
{
    public function __construct(private readonly FinanceDashboard $dashboard) {}

    public function index(): View
    {
        return view('tenant.finance.dashboard', $this->dashboard->build());
    }
}
