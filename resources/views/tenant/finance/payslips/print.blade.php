{{--
    Payslip print view.

    Renders in a FIXED LIGHT THEME regardless of the active app theme
    (DESIGN_SYSTEM.md §2.2) — printed output must not depend on screen
    appearance settings. Standalone inline CSS, no Tailwind, no dark: variants,
    which also places it in the same exempt category as reports/print/* under
    DESIGN_SYSTEM.md §8.
--}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>قسيمة راتب — {{ $payslip->employee_name }} — {{ $payslip->payrollRun?->period }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 32px;
            background: #ffffff;
            color: #0f172a;
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            font-size: 13px;
            line-height: 1.6;
        }
        .sheet { max-width: 760px; margin: 0 auto; }
        header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0f172a; padding-bottom: 16px; margin-bottom: 24px; }
        h1 { margin: 0; font-size: 20px; }
        .muted { color: #64748b; font-size: 12px; }
        .ltr { direction: ltr; unicode-bidi: isolate; display: inline-block; font-variant-numeric: tabular-nums; }
        .meta { display: flex; flex-wrap: wrap; gap: 24px; margin-bottom: 24px; }
        .meta div { min-width: 120px; }
        .meta dt { color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: .4px; margin: 0; }
        .meta dd { margin: 2px 0 0; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { padding: 8px 12px; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; font-size: 11px; text-transform: uppercase; letter-spacing: .4px; color: #64748b; }
        th.start, td.start { text-align: right; }
        th.center, td.center { text-align: center; }
        th.end, td.end { text-align: left; }
        tfoot td { font-weight: 700; border-top: 2px solid #0f172a; border-bottom: none; }
        .net { font-size: 15px; }
        .negative { color: #b91c1c; }
        footer { margin-top: 32px; padding-top: 12px; border-top: 1px solid #e2e8f0; }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
@php
    $money = static fn (int $minor): string => number_format(abs($minor) / 100, 2);
    $signed = static fn (int $minor): string => ($minor < 0 ? '-' : '').number_format(abs($minor) / 100, 2);
    $index = 1;
@endphp

<div class="sheet">
    <header>
        <div>
            <h1>قسيمة راتب</h1>
            <p class="muted">فترة <span class="ltr">{{ $payslip->payrollRun?->period }}</span></p>
        </div>
        <div style="text-align: left;">
            <p class="muted">الحالة</p>
            <strong>{{ $payslip->payrollRun?->status->label() }}</strong>
        </div>
    </header>

    <dl class="meta">
        <div><dt>الموظف</dt><dd>{{ $payslip->employee_name }}</dd></div>
        <div><dt>المسمى الوظيفي</dt><dd>{{ $payslip->job_title ?? '—' }}</dd></div>
        <div><dt>القسم</dt><dd>{{ $payslip->department_name ?? '—' }}</dd></div>
        <div><dt>أساس الاحتساب</dt><dd>{{ $payslip->pay_basis->label() }}</dd></div>
        <div><dt>أيام العمل</dt><dd><span class="ltr">{{ $payslip->scheduled_days }}</span></dd></div>
        <div><dt>أيام الغياب</dt><dd><span class="ltr">{{ $payslip->absent_days }}</span></dd></div>
    </dl>

    <table>
        <thead>
            <tr>
                <th class="center" style="width: 40px;">#</th>
                <th class="start">البند</th>
                <th class="center">النوع</th>
                <th class="end">القيمة</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="center">{{ $index++ }}</td>
                <td class="start">الراتب الأساسي</td>
                <td class="center">أساسي</td>
                <td class="end"><span class="ltr">{{ $money($payslip->base_amount) }}</span></td>
            </tr>
            @if ($payslip->absence_deduction !== 0)
                <tr>
                    <td class="center">{{ $index++ }}</td>
                    <td class="start">خصم الغياب ({{ $payslip->absent_days }} يوم)</td>
                    <td class="center">استقطاع</td>
                    <td class="end negative"><span class="ltr">{{ $signed($payslip->absence_deduction) }}</span></td>
                </tr>
            @endif
            @foreach ($payslip->lineItems as $lineItem)
                <tr>
                    <td class="center">{{ $index++ }}</td>
                    <td class="start">{{ $lineItem->label }}</td>
                    <td class="center">{{ $lineItem->kind->label() }}</td>
                    <td class="end {{ $lineItem->amount < 0 ? 'negative' : '' }}"><span class="ltr">{{ $signed($lineItem->amount) }}</span></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="end">الإجمالي</td>
                <td class="end"><span class="ltr">{{ $money($payslip->gross_amount) }}</span></td>
            </tr>
            <tr class="net">
                <td colspan="3" class="end">صافي المستحق</td>
                <td class="end"><span class="ltr">{{ $money($payslip->net_amount) }} {{ $payslip->pay_currency }}</span></td>
            </tr>
        </tfoot>
    </table>

    <footer class="muted">
        <p>هذه القسيمة صادرة آلياً من نظام Veyra ERP ولا تحتاج إلى توقيع.</p>
    </footer>

    <p class="no-print" style="margin-top: 24px;">
        <button type="button" onclick="window.print()" style="padding: 8px 16px; font-size: 13px; cursor: pointer;">طباعة</button>
    </p>
</div>
</body>
</html>
