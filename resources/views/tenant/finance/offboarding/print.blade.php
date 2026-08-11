{{--
    Settlement print view — fixed light theme regardless of app theme
    (DESIGN_SYSTEM.md §2.2), standalone inline CSS, same exempt category as
    reports/print/* under §8.
--}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تسوية نهاية الخدمة — {{ $settlement->employee_name }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 32px; background: #fff; color: #0f172a; font-family: "Segoe UI", Tahoma, Arial, sans-serif; font-size: 13px; line-height: 1.6; }
        .sheet { max-width: 760px; margin: 0 auto; }
        header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0f172a; padding-bottom: 16px; margin-bottom: 24px; }
        h1 { margin: 0; font-size: 20px; }
        .muted { color: #64748b; font-size: 12px; }
        .ltr { direction: ltr; unicode-bidi: isolate; display: inline-block; font-variant-numeric: tabular-nums; }
        .meta { display: flex; flex-wrap: wrap; gap: 24px; margin-bottom: 24px; }
        .meta dt { color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: .4px; margin: 0; }
        .meta dd { margin: 2px 0 0; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { padding: 8px 12px; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; font-size: 11px; text-transform: uppercase; letter-spacing: .4px; color: #64748b; }
        th.start, td.start { text-align: right; }
        th.center, td.center { text-align: center; }
        th.end, td.end { text-align: left; }
        tfoot td { font-weight: 700; border-top: 2px solid #0f172a; border-bottom: none; font-size: 15px; }
        .negative { color: #b91c1c; }
        .sign { margin-top: 48px; display: flex; justify-content: space-between; gap: 48px; }
        .sign div { flex: 1; border-top: 1px solid #94a3b8; padding-top: 8px; text-align: center; }
        @media print { body { padding: 0; } .no-print { display: none; } }
    </style>
</head>
<body>
@php
    $money = static fn (int $minor): string => ($minor < 0 ? '-' : '').number_format(abs($minor) / 100, 2);

    $lines = [
        ['label' => 'مكافأة نهاية الخدمة', 'value' => $settlement->eosb_amount],
        ['label' => 'بدل إجازات غير مستخدمة ('.$settlement->unused_leave_days.' يوم)', 'value' => $settlement->leave_payout_amount],
        ['label' => 'راتب الشهر الأخير', 'value' => $settlement->prorated_salary_amount],
        ['label' => 'استقطاع سلف', 'value' => $settlement->loan_deduction_amount],
        ['label' => 'استقطاعات أخرى', 'value' => $settlement->other_deduction_amount],
    ];
@endphp

<div class="sheet">
    <header>
        <div>
            <h1>تسوية نهاية الخدمة</h1>
            <p class="muted">{{ $settlement->reason->label() }}</p>
        </div>
        <div style="text-align: left;">
            <p class="muted">الحالة</p>
            <strong>{{ $settlement->status->label() }}</strong>
        </div>
    </header>

    <dl class="meta">
        <div><dt>الموظف</dt><dd>{{ $settlement->employee_name }}</dd></div>
        <div><dt>المسمى الوظيفي</dt><dd>{{ $settlement->job_title ?? '—' }}</dd></div>
        <div><dt>القسم</dt><dd>{{ $settlement->department_name ?? '—' }}</dd></div>
        <div><dt>تاريخ الالتحاق</dt><dd><span class="ltr">{{ $settlement->joining_date?->format('Y-m-d') ?? '—' }}</span></dd></div>
        <div><dt>آخر يوم عمل</dt><dd><span class="ltr">{{ $settlement->last_working_day?->format('Y-m-d') }}</span></dd></div>
        <div><dt>مدة الخدمة</dt><dd>{{ $settlement->serviceYearsLabel() }}</dd></div>
    </dl>

    <table>
        <thead>
            <tr>
                <th class="center" style="width: 40px;">#</th>
                <th class="start">البند</th>
                <th class="end">القيمة</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lines as $line)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td class="start">{{ $line['label'] }}</td>
                    <td class="end {{ $line['value'] < 0 ? 'negative' : '' }}"><span class="ltr">{{ $money($line['value']) }}</span></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="end">صافي التسوية</td>
                <td class="end"><span class="ltr">{{ $money($settlement->total_amount) }} {{ $settlement->currency }}</span></td>
            </tr>
        </tfoot>
    </table>

    <div class="sign">
        <div>توقيع الموظف</div>
        <div>توقيع إدارة المالية</div>
    </div>

    <p class="no-print" style="margin-top: 24px;">
        <button type="button" onclick="window.print()" style="padding: 8px 16px; font-size: 13px; cursor: pointer;">طباعة</button>
    </p>
</div>
</body>
</html>
