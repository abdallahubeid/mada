<?php

namespace App\Http\Controllers\Tenant\HR;

use App\Domain\Tenancy\Models\Employee;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Tenancy\EmployeeDashboard;
use App\Services\Tenancy\HrDashboard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class HrDashboardController extends Controller
{
    public function __construct(
        private readonly HrDashboard $hrDashboard,
        private readonly EmployeeDashboard $employeeDashboard,
    ) {}

    /**
     * HR Manager dashboard — org-wide workforce state and approval queues.
     */
    public function index(): View
    {
        return view('tenant.dashboard.hr', $this->hrDashboard->build());
    }

    /**
     * Employee self-service dashboard — scoped entirely to the acting user's
     * own employee record. A user with no linked employee profile gets an
     * explanatory empty state rather than a 403.
     */
    public function employee(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $employee = Employee::query()->where('user_id', $user->id)->first();

        return view('tenant.dashboard.employee', $this->employeeDashboard->build($employee));
    }
}
