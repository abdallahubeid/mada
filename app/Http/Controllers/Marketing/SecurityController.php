<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * Security & Compliance marketing page (docs/MARKETING.md §2). Surfaces the
 * platform guarantees buyers in government / NGO / education care about:
 * row-level isolation, 5-state lifecycle, mandatory admin 2FA (ADR-14),
 * audit log (NFR-05), and secret encryption (ADR-16).
 */
class SecurityController extends Controller
{
    public function __invoke(): View
    {
        $pillars = [
            [
                'title' => 'عزل بيانات متعدد المستأجرين',
                'description' => 'كل مؤسسة معزولة على مستوى الصفوف. لا يمكن لأي مستأجر الوصول إلى بيانات مستأجر آخر — عزل صارم مدمج في طبقة الاستعلام.',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6.03 11.959 11.959 0 0 0 3 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.72A11.959 11.959 0 0 1 12 2.714Z" />',
            ],
            [
                'title' => 'دورة حياة من 5 مراحل',
                'description' => 'من التحقق والموافقة إلى التفعيل والتعليق والإلغاء — تحكم تشغيلي كامل بحالة كل مؤسسة قبل منح الوصول التشغيلي.',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />',
            ],
            [
                'title' => 'تحقق بخطوتين إلزامي للمشرفين',
                'description' => 'حسابات مشرفي المنصّة تتطلب المصادقة الثنائية قبل منح الجلسة (ADR-14) — لا استثناءات للوصول الإداري.',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />',
            ],
            [
                'title' => 'سجل نشاط قابل للتدقيق',
                'description' => 'كل عملية حسّاسة تُسجَّل مع الفاعل والهدف والطابع الزمني والتغييرات — جاهز للمراجعة والامتثال (NFR-05).',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />',
            ],
            [
                'title' => 'تشفير الأسرار الحساسة',
                'description' => 'مفاتيح SMTP وبوابات الدفع والأسرار التشغيلية تُخزَّن مشفّرة ولا تُعرض كنص صريح في الواجهة (ADR-16).',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5" />',
            ],
            [
                'title' => 'صلاحيات دقيقة حسب الدور',
                'description' => 'أدوار وصلاحيات على مستوى الفريق داخل كل مؤسسة، مع فصل واضح بين مشرفي المنصّة ومشرفي الدعم.',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />',
            ],
        ];

        return view('marketing.security', [
            'pillars' => $pillars,
        ]);
    }
}
