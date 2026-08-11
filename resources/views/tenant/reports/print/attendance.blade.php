<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; color: #0f172a; margin: 24px; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        p { color: #64748b; font-size: 13px; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; font-size: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 8px; text-align: right; }
        th { background: #f1f5f9; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">طباعة / حفظ PDF</button>
    <h1>{{ $title }}</h1>
    <p>من {{ $from }} إلى {{ $to }} · {{ now()->format('Y-m-d H:i') }}</p>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>التاريخ</th>
                <th>الموظف</th>
                <th>الحضور</th>
                <th>الانصراف</th>
                <th>الحالة</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td dir="ltr">{{ $row->date?->format('Y-m-d') }}</td>
                    <td>{{ $row->employee?->full_name }}</td>
                    <td dir="ltr">{{ $row->check_in?->format('H:i') ?? '—' }}</td>
                    <td dir="ltr">{{ $row->check_out?->format('H:i') ?? '—' }}</td>
                    <td>{{ $row->status->label() }}</td>
                </tr>
            @empty
                <tr><td colspan="6">لا توجد بيانات.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
