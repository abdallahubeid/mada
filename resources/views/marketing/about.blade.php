{{-- About Us — grounded in docs/PROJECT_VISION.md. --}}
<x-layouts.marketing
    title="من نحن — مدى"
    description="تعرّف على رؤية مدى: منصة SaaS متعددة المستأجرين تربط التوظيف والموارد البشرية والرواتب في حلقة بيانات مغلقة."
>
    <x-marketing.nav />

    <main>
        <x-marketing.page-hero
            eyebrow="من نحن"
            title="نبني ERP يستحق الثقة"
            subtitle="مدى منصة تجارية متعددة المستأجرين — مصممة لتُباع، وتُشغَّل على نطاق واسع، وتُصان لسنوات."
        />

        {{-- What we are --}}
        <section class="bg-white py-24 dark:bg-ink-900">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <x-marketing.section-heading
                    title="ما هو مدى؟"
                    subtitle="منصة SaaS تجارية تدمج ثلاثة مجالات كانت تُباع عادة كأدوات منفصلة."
                />
                <div class="mt-12 grid gap-6 sm:grid-cols-3">
                    @foreach ([
                        {{-- "العمليات والمشاريع" (no projects/timesheets module) and
                             "الفوترة" (client invoicing is Phase 2B, blocked — ADR-18)
                             removed 2026-08-10 and replaced with built domains. --}}
                        ['title' => 'الموارد البشرية والتوظيف', 'body' => 'التوظيف والمقابلات، سجلات الموظفين، العقود، الحضور، والإجازات.'],
                        ['title' => 'الحوكمة والصلاحيات', 'body' => 'أدوار مخصّصة لكل مؤسسة، محرّك موافقات، وسجل تدقيق كامل.'],
                        ['title' => 'المالية والرواتب', 'body' => 'مسيّرات الرواتب، المصروفات، وتسويات نهاية الخدمة.'],
                    ] as $domain)
                        <div class="rounded-2xl border border-mist-200 bg-ink-50/40 p-5 dark:border-ink-800 dark:bg-ink-800/60">
                            <h3 class="font-display text-base font-medium text-ink-900 dark:text-ink-50">{{ $domain['title'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-mist-500 dark:text-mist-400">{{ $domain['body'] }}</p>
                        </div>
                    @endforeach
                </div>
                <p class="mt-8 text-center text-sm leading-relaxed text-mist-600 dark:text-mist-300">
                    كل مؤسسة (مستأجر) تعمل في عزل تام عن بقية العملاء، تحت تطبيق وقاعدة بيانات مشتركة، تُدار مركزياً عبر طبقة مشرفي المنصّة.
                </p>
            </div>
        </section>

        {{-- Value proposition --}}
        <section class="bg-ink-100 py-24 dark:bg-ink-950">
            <div class="mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
                <x-marketing.section-heading
                    eyebrow="القيمة الجوهرية"
                    title="حلقة بيانات مغلقة"
                    subtitle="البيانات تُدخل مرة واحدة وتتدفق تلقائياً إلى كل مكان تُحتاج فيه — بلا إعادة إدخال يدوي."
                />
                <p class="mt-8 text-base leading-relaxed text-mist-600 dark:text-mist-300">
                    توظيف ← توظيف رسمي ← تتبع العمل ← صرف الرواتب وتوليد إيرادات العملاء — وكل ذلك على لوحة مالية واحدة.
                    هذا هو الفارق: الأنظمة المنفصلة تفرض عليك المصالحة اليدوية؛ مدى يجعل المصالحة تلقائية وقابلة للإثبات.
                </p>
            </div>
        </section>

        {{-- Target + mission --}}
        <section class="bg-white py-24 dark:bg-ink-900">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
                <div class="rounded-3xl border border-mist-200 bg-ink-50/40 p-8 dark:border-ink-800 dark:bg-ink-800/60">
                    <h2 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">من نخدم؟</h2>
                    <p class="mt-4 text-sm leading-relaxed text-mist-600 dark:text-mist-300">
                        المؤسسات الصغيرة والمتوسطة (حوالي 5–500 موظف) التي تستخدم اليوم أدوات متفرقة أو تجاوزت جداول البيانات دون أن تناسبها أنظمة المؤسسات الثقيلة.
                    </p>
                    <p class="mt-3 text-sm leading-relaxed text-mist-600 dark:text-mist-300">
                        السوق الأساسي: منطقة الشرق الأوسط وشمال أفريقيا — لذا العربية أولاً مع دعم ثنائي اللغة قابل للتوسع.
                    </p>
                </div>
                <div class="rounded-3xl border border-mist-200 bg-ink-50/40 p-8 dark:border-ink-800 dark:bg-ink-800/60">
                    <h2 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">معايير نجاحنا</h2>
                    <ul class="mt-4 space-y-3 text-sm text-mist-600 dark:text-mist-300">
                        <li class="flex gap-3">
                            <span class="mt-0.5 text-brand-500"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg></span>
                            إكمال الحلقة الكاملة (توظيف ← عمل ← دفع/فوترة) دون نسخ بيانات يدوياً بين الوحدات.
                        </li>
                        <li class="flex gap-3">
                            <span class="mt-0.5 text-brand-500"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg></span>
                            لا يمكن لأي مستأجر رؤية بيانات مستأجر آخر — في أي مسار، بما في ذلك المهام الخلفية والتخزين المؤقت.
                        </li>
                        <li class="flex gap-3">
                            <span class="mt-0.5 text-brand-500"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg></span>
                            تجربة الموظف اليومية (تسجيل الحضور، مهامه) سريعة ولا تحتاج تدريباً.
                        </li>
                        <li class="flex gap-3">
                            <span class="mt-0.5 text-brand-500"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg></span>
                            منتج يبدو ويُشعر كمنصة تجارية جادة: هوية متسقة، وضع داكن/فاتح، وRTL سليم.
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <x-marketing.cta-band
            title="جاهز لمعرفة المزيد؟"
            subtitle="ابدأ تجربة مجانية أو احجز عرضاً توضيحياً مع فريقنا."
            secondary-label="احجز عرضاً توضيحياً"
            secondary-href="/contact"
        />
    </main>

    <x-marketing.footer />
</x-layouts.marketing>
