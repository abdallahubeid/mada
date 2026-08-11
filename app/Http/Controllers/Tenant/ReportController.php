<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Tenancy\Models\Attendance;
use App\Domain\Tenancy\Models\AuditLog;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\LeaveRequest;
use App\Http\Controllers\Controller;
use App\Services\Tenancy\AuditLogPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private readonly AuditLogPresenter $auditPresenter) {}

    public function index(): View
    {
        $auditModules = AuditLog::query()
            ->select('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module')
            ->mapWithKeys(fn (string $module) => [$module => $this->auditPresenter->moduleLabel($module)]);

        return view('tenant.reports.index', [
            'auditModules' => $auditModules,
        ]);
    }

    public function exportAttendance(Request $request): StreamedResponse|View|Response
    {
        $format = (string) $request->string('format', 'csv');
        $from = $request->filled('from')
            ? Carbon::parse((string) $request->string('from'))->toDateString()
            : now()->startOfMonth()->toDateString();
        $to = $request->filled('to')
            ? Carbon::parse((string) $request->string('to'))->toDateString()
            : now()->toDateString();

        $rows = Attendance::query()
            ->with('employee')
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->get();

        if ($format === 'pdf') {
            return view('tenant.reports.print.attendance', [
                'rows' => $rows,
                'from' => $from,
                'to' => $to,
                'title' => 'تقرير الحضور',
            ]);
        }

        return $this->csvDownload(
            'attendance-'.$from.'-'.$to.'.csv',
            ['التاريخ', 'الموظف', 'الحضور', 'الانصراف', 'الحالة'],
            $rows->map(fn (Attendance $row) => [
                $row->date?->format('Y-m-d'),
                $row->employee?->full_name,
                $row->check_in?->format('H:i'),
                $row->check_out?->format('H:i'),
                $row->status->label(),
            ])->all(),
        );
    }

    public function exportLeaves(Request $request): StreamedResponse|View|Response
    {
        $format = (string) $request->string('format', 'csv');

        $rows = LeaveRequest::query()
            ->with(['employee', 'leaveType'])
            ->when(
                $request->filled('status') && (string) $request->string('status') !== 'all',
                fn ($query) => $query->where('status', (string) $request->string('status')),
            )
            ->latest()
            ->get();

        if ($format === 'pdf') {
            return view('tenant.reports.print.leaves', [
                'rows' => $rows,
                'title' => 'تقرير طلبات الإجازة',
            ]);
        }

        return $this->csvDownload(
            'leave-requests-'.now()->format('Ymd').'.csv',
            ['الموظف', 'النوع', 'من', 'إلى', 'الأيام', 'الحالة'],
            $rows->map(fn (LeaveRequest $row) => [
                $row->employee?->full_name,
                $row->leaveType?->name,
                $row->start_date?->format('Y-m-d'),
                $row->end_date?->format('Y-m-d'),
                $row->days_count,
                $row->status->label(),
            ])->all(),
        );
    }

    public function exportEmployees(Request $request): StreamedResponse|View|Response
    {
        $format = (string) $request->string('format', 'csv');

        $rows = Employee::query()
            ->with('department')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        if ($format === 'pdf') {
            return view('tenant.reports.print.employees', [
                'rows' => $rows,
                'title' => 'كشف الموظفين',
            ]);
        }

        return $this->csvDownload(
            'employees-'.now()->format('Ymd').'.csv',
            ['الاسم', 'المسمى', 'القسم', 'الحالة', 'تاريخ الالتحاق'],
            $rows->map(fn (Employee $row) => [
                $row->full_name,
                $row->job_title,
                $row->department?->name,
                $row->status->label(),
                $row->joining_date?->format('Y-m-d'),
            ])->all(),
        );
    }

    public function exportAuditLogs(Request $request): StreamedResponse|View|Response
    {
        abort_unless($request->user()?->can('tenant.audit_logs.view') ?? false, 403);

        $format = (string) $request->string('format', 'csv');
        $from = $request->filled('from')
            ? Carbon::parse((string) $request->string('from'))->startOfDay()
            : now()->startOfMonth()->startOfDay();
        $to = $request->filled('to')
            ? Carbon::parse((string) $request->string('to'))->endOfDay()
            : now()->endOfDay();

        $auditRows = AuditLog::query()
            ->with('user')
            ->whereDate('created_at', '>=', $from->toDateString())
            ->whereDate('created_at', '<=', $to->toDateString())
            ->when(
                $request->filled('module') && (string) $request->string('module') !== 'all',
                fn ($query) => $query->where('module', (string) $request->string('module')),
            )
            ->latest('id')
            ->get()
            ->map(function (AuditLog $log): array {
                $presented = $this->auditPresenter->present($log);
                $details = collect($presented['rows'])
                    ->map(fn (array $row): string => $row['field'].': '.$row['before'].' ← '.$row['after'])
                    ->implode(' | ');

                return [
                    'time' => $log->created_at?->format('Y-m-d H:i'),
                    'user' => $log->user?->name ?? 'النظام',
                    'summary' => $presented['summary'],
                    'module' => $presented['module_label'],
                    'ip' => $log->ip_address ?? '—',
                    'details' => $details !== '' ? $details : '—',
                ];
            })
            ->values();

        if ($format === 'pdf') {
            return view('tenant.reports.print.audit-logs', [
                'rows' => $auditRows,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'title' => 'سجل النشاط والأمان',
            ]);
        }

        return $this->csvDownload(
            'audit-logs-'.$from->toDateString().'-'.$to->toDateString().'.csv',
            ['التوقيت', 'المستخدم', 'الإجراء', 'الوحدة', 'IP', 'التفاصيل'],
            $auditRows->map(fn (array $row): array => [
                $row['time'],
                $row['user'],
                $row['summary'],
                $row['module'],
                $row['ip'],
                $row['details'],
            ])->all(),
        );
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<mixed>>  $rows
     */
    private function csvDownload(string $filename, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
