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
    <p>تاريخ التصدير: {{ now()->format('Y-m-d H:i') }}</p>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>الاسم</th>
                <th>المسمى</th>
                <th>القسم</th>
                <th>الحالة</th>
                <th>تاريخ الالتحاق</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $row->full_name }}</td>
                    <td>{{ $row->job_title }}</td>
                    <td>{{ $row->department?->name ?? '—' }}</td>
                    <td>{{ $row->status->label() }}</td>
                    <td dir="ltr">{{ $row->joining_date?->format('Y-m-d') }}</td>
                </tr>
            @empty
                <tr><td colspan="6">لا توجد بيانات.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
