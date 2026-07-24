{{-- Terms of Service (docs/MARKETING.md §2). Placeholder legal copy for MVP — replace with counsel-approved text before launch. --}}
<x-layouts.marketing
    title="الشروط والأحكام — Veyra ERP"
    description="شروط استخدام منصة Veyra ERP: الحسابات، المستأجرين، الخطط، والاستخدام المقبول."
>
    <x-marketing.nav />

    <main>
        <x-marketing.page-hero
            eyebrow="القانونية"
            title="الشروط والأحكام"
            subtitle="آخر تحديث: يوليو 2026. باستخدامك لـ Veyra ERP فإنك توافق على هذه الشروط."
        />

        <section class="bg-white py-16 dark:bg-ink-900">
            <article class="mx-auto max-w-3xl space-y-10 px-4 text-sm leading-relaxed text-mist-600 sm:px-6 lg:px-8 dark:text-mist-300">
                <div>
                    <h2 class="font-display text-xl font-bold text-ink-900 dark:text-ink-50">1. قبول الشروط</h2>
                    <p class="mt-3">باستخدام المنصّة أو إنشاء حساب أو تجربة مجانية، فإنك توافق على هذه الشروط وسياسة الخصوصية. إن لم توافق، يرجى عدم استخدام الخدمة.</p>
                </div>

                <div>
                    <h2 class="font-display text-xl font-bold text-ink-900 dark:text-ink-50">2. الحسابات والمستأجرون</h2>
                    <p class="mt-3">كل مؤسسة مسجّلة تُعامل كمستأجر مستقل. أنت مسؤول عن سرية بيانات الدخول وعن نشاط المستخدمين داخل مؤسستك. قد تخضع الحسابات الجديدة للتحقق والموافقة قبل التفعيل الكامل.</p>
                </div>

                <div>
                    <h2 class="font-display text-xl font-bold text-ink-900 dark:text-ink-50">3. الخطط والتجربة</h2>
                    <p class="mt-3">تتوفر خطط Startup و Growth و Enterprise كما هو موضّح في صفحة الأسعار. قد تتضمن التجربة المجانية حدوداً زمنية أو وظيفية. الرسوم غير المدفوعة قد تؤدي إلى تعليق الوصول.</p>
                </div>

                <div>
                    <h2 class="font-display text-xl font-bold text-ink-900 dark:text-ink-50">4. الاستخدام المقبول</h2>
                    <ul class="mt-3 list-disc space-y-2 ps-5">
                        <li>عدم محاولة اختراق عزل المستأجرين أو الوصول إلى بيانات مؤسسات أخرى.</li>
                        <li>عدم إساءة استخدام واجهات البرمجة أو إغراق النظام بطلبات غير مشروعة.</li>
                        <li>الالتزام بالقوانين المعمول بها عند إدخال ومعالجة بيانات الموظفين والعملاء.</li>
                    </ul>
                </div>

                <div>
                    <h2 class="font-display text-xl font-bold text-ink-900 dark:text-ink-50">5. الملكية الفكرية</h2>
                    <p class="mt-3">المنصّة وعلاماتها وتصميمها ملك لـ Veyra. تبقى بياناتك التشغيلية ملكاً لمؤسستك. نمنحك ترخيصاً محدوداً غير حصري لاستخدام الخدمة وفق خطتك.</p>
                </div>

                <div>
                    <h2 class="font-display text-xl font-bold text-ink-900 dark:text-ink-50">6. إخلاء المسؤولية</h2>
                    <p class="mt-3">تُقدَّم الخدمة «كما هي» ضمن حدود القانون. لا نضمن عدم انقطاع الخدمة، ونحدّ مسؤوليتنا إلى أقصى حد يسمح به القانون المعمول به.</p>
                </div>

                <div>
                    <h2 class="font-display text-xl font-bold text-ink-900 dark:text-ink-50">7. إنهاء الخدمة</h2>
                    <p class="mt-3">يمكنك إلغاء اشتراكك وفق آلية الحساب. نحتفظ بحق تعليق أو إنهاء الحسابات التي تنتهك هذه الشروط أو تهدد أمان المنصّة.</p>
                </div>

                <div>
                    <h2 class="font-display text-xl font-bold text-ink-900 dark:text-ink-50">8. التواصل</h2>
                    <p class="mt-3">للاستفسارات القانونية أو التعاقدية: <a href="{{ route('marketing.contact') }}" class="font-medium text-emerald-600 hover:underline dark:text-emerald-400">تواصل معنا</a>.</p>
                </div>
            </article>
        </section>
    </main>

    <x-marketing.footer />
</x-layouts.marketing>
