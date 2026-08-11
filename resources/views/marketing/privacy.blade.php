{{-- Privacy Policy (docs/MARKETING.md §2). Placeholder legal copy for MVP — replace with counsel-approved text before launch. --}}
<x-layouts.marketing
    title="سياسة الخصوصية — Veyra ERP"
    description="كيف يجمع Veyra ERP بياناتك ويعالجها ويحميها. عزل متعدد المستأجرين وتشفير للأسرار الحساسة."
>
    <x-marketing.nav />

    <main>
        <x-marketing.page-hero
            eyebrow="القانونية"
            title="سياسة الخصوصية"
            subtitle="آخر تحديث: يوليو 2026. توضّح هذه الصفحة كيف نتعامل مع بياناتك الشخصية وبيانات مؤسستك."
        />

        <section class="bg-white py-16 dark:bg-ink-900">
            <article class="prose-marketing mx-auto max-w-3xl space-y-10 px-4 text-sm leading-relaxed text-mist-600 sm:px-6 lg:px-8 dark:text-mist-300">
                <div>
                    <h2 class="font-display text-xl font-bold text-ink-900 dark:text-ink-50">1. من نحن</h2>
                    <p class="mt-3">Veyra ERP منصة SaaS متعددة المستأجرين لإدارة التوظيف والموارد البشرية والرواتب. عند إنشاء حساب، تصبح مؤسستك «مستأجراً» معزولاً عن بقية العملاء.</p>
                </div>

                <div>
                    <h2 class="font-display text-xl font-bold text-ink-900 dark:text-ink-50">2. البيانات التي نجمعها</h2>
                    <ul class="mt-3 list-disc space-y-2 ps-5">
                        <li>بيانات الحساب: الاسم، البريد الإلكتروني، كلمة المرور (مشفّرة)، وبيانات المؤسسة.</li>
                        <li>بيانات تشغيلية يدخلها المستأجر داخل النظام (موظفون، مشاريع، رواتب، إلخ) — تبقى معزولة لكل مؤسسة.</li>
                        <li>بيانات تقنية: سجلات الدخول، عناوين IP، وسجلات النشاط لأغراض الأمان والتدقيق.</li>
                        <li>بيانات التواصل: الرسائل المرسلة عبر نموذج الاتصال أو النشرة البريدية.</li>
                    </ul>
                </div>

                <div>
                    <h2 class="font-display text-xl font-bold text-ink-900 dark:text-ink-50">3. كيف نستخدم البيانات</h2>
                    <p class="mt-3">نشغّل الخدمة ونحسّنها، نتحقق من الهوية، نرسل إشعارات تشغيلية، ونستجيب لاستفسارات الدعم. لا نبيع بياناتك الشخصية لأطراف ثالثة.</p>
                </div>

                <div>
                    <h2 class="font-display text-xl font-bold text-ink-900 dark:text-ink-50">4. العزل والأمان</h2>
                    <p class="mt-3">نعتمد عزل البيانات على مستوى الصفوف بين المستأجرين، وتحققاً بخطوتين إلزامياً لحسابات مشرفي المنصّة، وتشفير الأسرار الحساسة، وسجل نشاط قابل للتدقيق للعمليات الحساسة.</p>
                </div>

                <div>
                    <h2 class="font-display text-xl font-bold text-ink-900 dark:text-ink-50">5. الاحتفاظ والمشاركة</h2>
                    <p class="mt-3">نحتفظ بالبيانات طالما كان الحساب نشطاً أو حسب ما يقتضيه القانون. قد نشارك بيانات محدودة مع مزوّدي بنية تحتية (استضافة، بريد) بموجب التزامات تعاقدية مناسبة.</p>
                </div>

                <div>
                    <h2 class="font-display text-xl font-bold text-ink-900 dark:text-ink-50">6. حقوقك</h2>
                    <p class="mt-3">يمكنك طلب الوصول إلى بياناتك أو تصحيحها أو حذفها عبر التواصل معنا على <a href="mailto:hello@veyra.app" class="font-medium text-emerald-600 hover:underline dark:text-emerald-400">hello@veyra.app</a>، مع مراعاة الالتزامات القانونية والعقود السارية.</p>
                </div>

                <div>
                    <h2 class="font-display text-xl font-bold text-ink-900 dark:text-ink-50">7. التواصل</h2>
                    <p class="mt-3">لأي استفسار متعلق بالخصوصية: <a href="{{ route('marketing.contact') }}" class="font-medium text-emerald-600 hover:underline dark:text-emerald-400">صفحة التواصل</a> أو البريد أعلاه.</p>
                </div>
            </article>
        </section>
    </main>

    <x-marketing.footer />
</x-layouts.marketing>
