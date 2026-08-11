<?php

namespace App\Services\Finance;

use App\Domain\Finance\Enums\ExpenseStatus;
use App\Domain\Finance\Enums\PayrollRunStatus;
use App\Domain\Finance\Enums\SettlementStatus;
use App\Domain\Finance\Models\Expense;
use App\Domain\Finance\Models\OffboardingSettlement;
use App\Domain\Finance\Models\PayrollRun;
use App\Domain\Finance\Models\Payslip;
use App\Domain\Tenancy\Models\OrgSetting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Cost-side finance dashboard (BR-607).
 *
 * Phase 2A has no revenue: Clients and Invoicing are Phase 2B, blocked on the
 * Projects module (ADR-18). This builder therefore returns cost figures only,
 * and the view omits Revenue / Net Profit tiles entirely rather than rendering
 * them as zero — a zero reads as "we earned nothing", which is a different and
 * much worse claim than "not tracked here yet".
 */
final class FinanceDashboard
{
    /**
     * @return array<string, mixed>
     */
    public function build(int $months = 6): array
    {
        $finalized = [PayrollRunStatus::Approved->value, PayrollRunStatus::Paid->value];

        return [
            'currency' => OrgSetting::query()->value('currency') ?? 'SAR',
            'kpis' => $this->kpis($finalized),
            'trend' => $this->monthlyTrend($months, $finalized),
            'statusBreakdown' => $this->statusBreakdown(),
            'pendingApproval' => $this->pendingApproval(),
            'recentRuns' => $this->recentRuns(),
            'topCosts' => $this->topCostCentres($finalized),
            'expenses' => $this->expenseSummary(),
            'offboarding' => $this->offboardingLiability(),
        ];
    }

    /**
     * Approved and paid expenses only (BR-607), broken down by category.
     *
     * @return array{total: int, pending_count: int, pending_total: int, unpaid_claims: int, by_category: list<array{name: string, total: int, count: int}>}
     */
    private function expenseSummary(): array
    {
        $finalized = [ExpenseStatus::Approved->value, ExpenseStatus::Paid->value];

        $byCategory = Expense::query()
            ->leftJoin('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->whereIn('expenses.status', $finalized)
            ->whereNull('expenses.deleted_at')
            ->selectRaw('coalesce(expense_categories.name, ?) as name, sum(expenses.amount) as total, count(*) as claim_count', ['غير مصنّف'])
            ->groupBy('name')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($row): array => [
                'name' => (string) $row->name,
                'total' => (int) $row->total,
                'count' => (int) $row->claim_count,
            ])
            ->all();

        return [
            'total' => (int) Expense::query()->whereIn('status', $finalized)->sum('amount'),
            'pending_count' => (int) Expense::query()->where('status', ExpenseStatus::PendingApproval->value)->count(),
            'pending_total' => (int) Expense::query()->where('status', ExpenseStatus::PendingApproval->value)->sum('amount'),
            // Approved but not yet reimbursed — money the company currently owes.
            'unpaid_claims' => (int) Expense::query()
                ->where('status', ExpenseStatus::Approved->value)
                ->where('is_claimable', true)
                ->sum('amount'),
            'by_category' => $byCategory,
        ];
    }

    /**
     * Outstanding end-of-service liability.
     *
     * `committed` is money already approved and legally owed but not yet paid —
     * the figure that matters for cash planning. `paid` is historical cost.
     *
     * @return array{committed: int, paid: int, pending_count: int, draft_count: int}
     */
    private function offboardingLiability(): array
    {
        return [
            'committed' => (int) OffboardingSettlement::query()
                ->where('status', SettlementStatus::Approved->value)
                ->sum('total_amount'),
            'paid' => (int) OffboardingSettlement::query()
                ->where('status', SettlementStatus::Paid->value)
                ->sum('total_amount'),
            'pending_count' => (int) OffboardingSettlement::query()
                ->where('status', SettlementStatus::PendingApproval->value)
                ->count(),
            'draft_count' => (int) OffboardingSettlement::query()
                ->where('status', SettlementStatus::Draft->value)
                ->count(),
        ];
    }

    /**
     * @param  list<string>  $finalized
     * @return array<string, int>
     */
    private function kpis(array $finalized): array
    {
        $finalizedRuns = PayrollRun::query()->whereIn('status', $finalized);

        return [
            // Only finalized runs count toward money figures (BR-607).
            'total_disbursed' => (int) PayrollRun::query()
                ->where('status', PayrollRunStatus::Paid->value)
                ->sum('total_net'),
            'total_approved' => (int) (clone $finalizedRuns)->sum('total_net'),
            'total_absence_deductions' => (int) (clone $finalizedRuns)->sum('total_absence_deduction'),
            'finalized_runs' => (int) (clone $finalizedRuns)->count(),
            'draft_runs' => (int) PayrollRun::query()
                ->where('status', PayrollRunStatus::Draft->value)
                ->count(),
            'pending_runs' => (int) PayrollRun::query()
                ->where('status', PayrollRunStatus::PendingApproval->value)
                ->count(),
            'employees_paid' => (int) PayrollRun::query()
                ->where('status', PayrollRunStatus::Paid->value)
                ->sum('payslip_count'),
        ];
    }

    /**
     * Net payroll cost per month, oldest first, with empty months filled in.
     *
     * Gaps are filled deliberately: a chart that silently skips a month with no
     * run makes an unpaid period look like it never existed.
     *
     * @param  list<string>  $finalized
     * @return list<array{period: string, total: int, label: string}>
     */
    private function monthlyTrend(int $months, array $finalized): array
    {
        $totals = PayrollRun::query()
            ->whereIn('status', $finalized)
            ->selectRaw('period, sum(total_net) as total')
            ->groupBy('period')
            ->pluck('total', 'period');

        $trend = [];

        for ($offset = $months - 1; $offset >= 0; $offset--) {
            $month = Carbon::now()->startOfMonth()->subMonths($offset);
            $period = $month->format('Y-m');

            $trend[] = [
                'period' => $period,
                'label' => $month->translatedFormat('M Y'),
                'total' => (int) ($totals[$period] ?? 0),
            ];
        }

        return $trend;
    }

    /**
     * @return list<array{status: PayrollRunStatus, count: int, total: int}>
     */
    private function statusBreakdown(): array
    {
        $rows = PayrollRun::query()
            ->selectRaw('status, count(*) as run_count, sum(total_net) as total')
            ->groupBy('status')
            ->get()
            ->keyBy(fn ($row): string => $row->status->value);

        return array_map(
            fn (PayrollRunStatus $status): array => [
                'status' => $status,
                'count' => (int) ($rows[$status->value]->run_count ?? 0),
                'total' => (int) ($rows[$status->value]->total ?? 0),
            ],
            PayrollRunStatus::cases(),
        );
    }

    /**
     * @return Collection<int, PayrollRun>
     */
    private function pendingApproval()
    {
        return PayrollRun::query()
            ->with('maker')
            ->where('status', PayrollRunStatus::PendingApproval->value)
            ->orderBy('period')
            ->get();
    }

    /**
     * @return Collection<int, PayrollRun>
     */
    private function recentRuns()
    {
        return PayrollRun::query()
            ->with(['maker', 'approver'])
            ->latest('period')
            ->limit(6)
            ->get();
    }

    /**
     * Departments ranked by finalized payroll cost.
     *
     * Reads `department_name` off the payslip SNAPSHOT rather than joining
     * employees, so a department rename or an employee transfer cannot rewrite
     * what a locked period cost (BR-608).
     *
     * @param  list<string>  $finalized
     * @return list<array{department: string, total: int, headcount: int}>
     */
    private function topCostCentres(array $finalized): array
    {
        return Payslip::query()
            ->whereHas('payrollRun', fn ($query) => $query->whereIn('status', $finalized))
            ->selectRaw('coalesce(department_name, ?) as department, sum(net_amount) as total, count(*) as headcount', ['غير محدد'])
            ->groupBy('department')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($row): array => [
                'department' => (string) $row->department,
                'total' => (int) $row->total,
                'headcount' => (int) $row->headcount,
            ])
            ->all();
    }
}
