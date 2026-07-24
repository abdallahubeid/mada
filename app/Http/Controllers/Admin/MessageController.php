<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Messages & Support Inquiries (docs/MODULES.md §6, BR-805/BR-806, ADR-17).
 * A tenant Owner-initiated conversation handled by the Super Admin — a
 * dedicated model, not the Approval Engine. Frontend slice: threads are mocked
 * in-controller and filtered by the `status` param; `thread` selects the open
 * conversation. Reading a thread is an explicit audited cross-tenant read
 * (ARCHITECTURE.md §8).
 */
class MessageController extends Controller
{
    /**
     * @return list<array{id:int, tenant:string, subject:string, snippet:string, status:string, time:string, unread:bool, messages:list<array{from:string, name:string, body:string, time:string}>}>
     */
    private function threads(): array
    {
        return [
            [
                'id' => 1,
                'tenant' => 'شركة الأفق للتقنية',
                'subject' => 'استفسار حول ترقية الخطة',
                'snippet' => 'نرغب بترقية اشتراكنا إلى خطة Growth، ما الخطوات المطلوبة؟',
                'status' => 'open',
                'time' => 'قبل 10 دقائق',
                'unread' => true,
                'messages' => [
                    ['from' => 'owner', 'name' => 'سارة المنصوري', 'body' => 'السلام عليكم، نرغب بترقية اشتراكنا إلى خطة Growth. ما الخطوات المطلوبة وهل سيتم احتساب فرق السعر تلقائيًا؟', 'time' => 'قبل 10 دقائق'],
                ],
            ],
            [
                'id' => 2,
                'tenant' => 'مؤسسة نماء',
                'subject' => 'مشكلة في تسجيل دخول أحد الموظفين',
                'snippet' => 'أحد الموظفين لا يستطيع الدخول رغم إعادة تعيين كلمة المرور.',
                'status' => 'in_progress',
                'time' => 'قبل ساعتين',
                'unread' => false,
                'messages' => [
                    ['from' => 'owner', 'name' => 'خالد العتيبي', 'body' => 'لدينا موظف لا يستطيع تسجيل الدخول رغم إعادة تعيين كلمة المرور عدة مرات.', 'time' => 'قبل 3 ساعات'],
                    ['from' => 'admin', 'name' => 'مشرف المنصّة', 'body' => 'أهلًا خالد، هل يظهر للموظف رسالة خطأ محددة؟ يرجى تزويدنا ببريده الإلكتروني لمراجعة السجل.', 'time' => 'قبل ساعتين'],
                    ['from' => 'owner', 'name' => 'خالد العتيبي', 'body' => 'نعم، تظهر رسالة «بيانات الدخول غير صحيحة». بريده: staff@namaa.co', 'time' => 'قبل ساعتين'],
                ],
            ],
            [
                'id' => 3,
                'tenant' => 'مجموعة رواد',
                'subject' => 'طلب فاتورة ضريبية',
                'snippet' => 'نحتاج فاتورة ضريبية رسمية عن اشتراك هذا الشهر.',
                'status' => 'open',
                'time' => 'أمس',
                'unread' => true,
                'messages' => [
                    ['from' => 'owner', 'name' => 'ليلى الحربي', 'body' => 'مرحبًا، نحتاج فاتورة ضريبية رسمية عن اشتراك هذا الشهر لأغراض المحاسبة.', 'time' => 'أمس'],
                ],
            ],
            [
                'id' => 4,
                'tenant' => 'شركة الابتكار',
                'subject' => 'شكر على الدعم السريع',
                'snippet' => 'شكرًا لكم، تم حل المشكلة بشكل ممتاز.',
                'status' => 'resolved',
                'time' => 'قبل 3 أيام',
                'unread' => false,
                'messages' => [
                    ['from' => 'owner', 'name' => 'نورة القحطاني', 'body' => 'واجهنا بطئًا في تحميل لوحة المشاريع.', 'time' => 'قبل 4 أيام'],
                    ['from' => 'admin', 'name' => 'مشرف المنصّة', 'body' => 'تم تحسين الأداء من جانبنا، يرجى إعادة المحاولة.', 'time' => 'قبل 3 أيام'],
                    ['from' => 'owner', 'name' => 'نورة القحطاني', 'body' => 'شكرًا لكم، تم حل المشكلة بشكل ممتاز.', 'time' => 'قبل 3 أيام'],
                ],
            ],
        ];
    }

    public function index(Request $request): View
    {
        $threads = $this->threads();

        $tabs = [
            'open' => 'مفتوح',
            'in_progress' => 'قيد المعالجة',
            'resolved' => 'تم الحل',
        ];

        $counts = [];

        foreach (array_keys($tabs) as $status) {
            $counts[$status] = count(array_filter($threads, fn ($t): bool => $t['status'] === $status));
        }

        $activeStatus = $request->query('status', 'open');

        if (! array_key_exists($activeStatus, $tabs)) {
            $activeStatus = 'open';
        }

        $filtered = array_values(array_filter($threads, fn ($t): bool => $t['status'] === $activeStatus));

        // Resolve the open conversation: requested thread (if it's in the current tab), else the first.
        $requestedId = (int) $request->query('thread', 0);
        $selected = collect($filtered)->firstWhere('id', $requestedId) ?? ($filtered[0] ?? null);

        return view('admin.messages.index', [
            'threads' => $filtered,
            'tabs' => $tabs,
            'counts' => $counts,
            'activeStatus' => $activeStatus,
            'selected' => $selected,
        ]);
    }
}
