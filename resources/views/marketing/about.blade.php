{{-- About Us — grounded in docs/PROJECT_VISION.md. --}}
<x-layouts.marketing
    title="من نحن — Veyra ERP"
    description="تعرّف على رؤية Veyra ERP: منصة SaaS متعددة المستأجرين تربط الموارد البشرية والمشاريع والرواتب في حلقة بيانات مغلقة."
>
    <x-marketing.nav />

    <main>
        <x-marketing.page-hero
            eyebrow="من نحن"
            title="نبني ERP يستحق الثقة"
            subtitle="Veyra منصة تجارية متعددة المستأجرين — مصممة لتُباع، وتُشغَّل على نطاق واسع، وتُصان لسنوات."
        />

        {{-- What we are --}}
        <section class="bg-white py-20 dark:bg-ink-900">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <x-marketing.section-heading
                    title="ما هو Veyra ERP؟"
                    subtitle="منصة SaaS تجارية تدمج ثلاثة مجالات كانت تُباع عادة كأدوات منفصلة."
                />
                <div class="mt-12 grid gap-6 sm:grid-cols-3">
                    @foreach ([
                        ['title' => 'الموارد البشرية والتوظيف', 'body' => 'التوظيف، سجلات الموظفين، الحضور، والإجازات.'],
                        ['title' => 'العمليات والمشاريع', 'body' => 'تنفيذ المهام والمشاريع وتتبع الوقت.'],
                        ['title' => 'المالية والرواتب', 'body' => 'الرواتب، الفوترة، المصاريف، والتقارير المالية.'],
                    ] as $domain)
                        <div class="rounded-2xl border border-mist-200 bg-ink-50/40 p-5 dark:border-ink-800 dark:bg-ink-800/60">
                            <h3 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">{{ $domain['title'] }}</h3>
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
        <section class="bg-ink-100 py-20 dark:bg-ink-950">
            <div class="mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
                <x-marketing.section-heading
                    eyebrow="القيمة الجوهرية"
                    title="حلقة بيانات مغلقة"
                    subtitle="البيانات تُدخل مرة واحدة وتتدفق تلقائياً إلى كل مكان تُحتاج فيه — بلا إعادة إدخال يدوي."
                />
                <p class="mt-8 text-base leading-relaxed text-mist-600 dark:text-mist-300">
                    توظيف ← توظيف رسمي ← تتبع العمل ← صرف الرواتب وتوليد إيرادات العملاء — وكل ذلك على لوحة مالية واحدة.
                    هذا هو الفارق: الأنظمة المنفصلة تفرض عليك المصالحة اليدوية؛ Veyra يجعل المصالحة تلقائية وقابلة للإثبات.
                </p>
            </div>
        </section>

        {{-- Target + mission --}}
        <section class="bg-white py-20 dark:bg-ink-900">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
                <div class="rounded-3xl border border-mist-200 bg-ink-50/40 p-8 dark:border-ink-800 dark:bg-ink-800/60">
                    <h2 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">من نخدم؟</h2>
                    <p class="mt-4 text-sm leading-relaxed text-mist-600 dark:text-mist-300">
                        المؤسسات الصغيرة والمتوسطة (حوالي 5–500 موظف) التي تستخدم اليوم أدوات متفرقة أو تجاوزت جداول البيانات دون أن تناسبها أنظمة المؤسسات الثقيلة.
                    </p>
                    <p class="mt-3 text-sm leading-relaxed text-mist-600 dark:text-mist-300">
                        السوق الأساسي: منطقة الشرق الأوسط وشمال أفريقيا — لذا العربية أولاً مع دعم ثنائي اللغة قابل للتوسع.
                    </p>
                </div>
                <div class="rounded-3xl border border-mist-200 bg-ink-50/40 p-8 dark:border-ink-800 dark:bg-ink-800/60">
                    <h2 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">معايير نجاحنا</h2>
                    <ul class="mt-4 space-y-3 text-sm text-mist-600 dark:text-mist-300">
                        <li class="flex gap-3">
                            <span class="mt-0.5 text-emerald-500">✓</span>
                            إكمال الحلقة الكاملة (توظيف ← عمل ← دفع/فوترة) دون نسخ بيانات يدوياً بين الوحدات.
                        </li>
                        <li class="flex gap-3">
                            <span class="mt-0.5 text-emerald-500">✓</span>
                            لا يمكن لأي مستأجر رؤية بيانات مستأجر آخر — في أي مسار، بما في ذلك المهام الخلفية والتخزين المؤقت.
                        </li>
                        <li class="flex gap-3">
                            <span class="mt-0.5 text-emerald-500">✓</span>
                            تجربة الموظف اليومية (تسجيل الحضور، مهامه) سريعة ولا تحتاج تدريباً.
                        </li>
                        <li class="flex gap-3">
                            <span class="mt-0.5 text-emerald-500">✓</span>
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
