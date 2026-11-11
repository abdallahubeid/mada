{{-- About Us — grounded in docs/PROJECT_VISION.md, laid out like the Andromeda About page. --}}
@php
    $domains = [
        ['title' => 'الموارد البشرية والتوظيف', 'body' => 'التوظيف والمقابلات، سجلات الموظفين، العقود، الحضور، والإجازات.'],
        ['title' => 'الحوكمة والصلاحيات', 'body' => 'أدوار مخصّصة لكل مؤسسة، محرّك موافقات، وسجل تدقيق كامل.'],
        ['title' => 'المالية والرواتب', 'body' => 'مسيّرات الرواتب، المصروفات، وتسويات نهاية الخدمة.'],
    ];

    $criteria = [
        'إكمال الحلقة الكاملة (توظيف ← عمل ← دفع/فوترة) دون نسخ بيانات يدوياً بين الوحدات.',
        'لا يمكن لأي مستأجر رؤية بيانات مستأجر آخر — في أي مسار، بما في ذلك المهام الخلفية والتخزين المؤقت.',
        'تجربة الموظف اليومية (تسجيل الحضور، مهامه) سريعة ولا تحتاج تدريباً.',
        'منتج يبدو ويُشعر كمنصة تجارية جادة: هوية متسقة، وضع داكن/فاتح، وRTL سليم.',
    ];
@endphp

<x-layouts.marketing
    title="من نحن — مدى"
    description="تعرّف على رؤية مدى: منصة SaaS متعددة المستأجرين تربط التوظيف والموارد البشرية والرواتب في حلقة بيانات مغلقة."
>
    <x-marketing.nav />

    <main>
        <x-marketing.page-hero
            eyebrow="من نحن"
            title="نبني ERP يستحق الثقة"
            subtitle="مدى منصة تجارية متعددة المستأجرين — مصممة لتُباع، وتُشغَّل على نطاق واسع، وتُصان لسنوات."
        />

        {{-- What Mada is: the template's "works" block — centred heading over a row of centred statements. --}}
        <section class="site-section">
            <div class="site-container">
                <x-marketing.section-heading
                    title="ما هو مدى؟"
                    subtitle="منصة SaaS تجارية تدمج ثلاثة مجالات كانت تُباع عادة كأدوات منفصلة."
                />

                <div class="mx-auto mt-10 grid max-w-6xl gap-10 md:grid-cols-3">
                    @foreach ($domains as $domain)
                        <div class="text-center md:px-4">
                            <h3 class="site-h4">{{ $domain['title'] }}</h3>
                            <p class="mt-2">{{ $domain['body'] }}</p>
                        </div>
                    @endforeach
                </div>

                <p class="mx-auto mt-12 max-w-3xl text-center">
                    كل مؤسسة (مستأجر) تعمل في عزل تام عن بقية العملاء، تحت تطبيق وقاعدة بيانات مشتركة، تُدار مركزياً عبر طبقة مشرفي المنصّة.
                </p>
            </div>
        </section>

        {{-- The closed loop: the template's short-intro panel, peach with waves and circles. --}}
        <section class="pb-16">
            <div class="site-container-xl">
                <div class="site-panel-host px-4 py-[70px]">
                    <x-marketing.panel circles="section" />

                    <x-marketing.section-heading
                        eyebrow="القيمة الجوهرية"
                        title="حلقة بيانات مغلقة"
                        subtitle="البيانات تُدخل مرة واحدة وتتدفق تلقائياً إلى كل مكان تُحتاج فيه — بلا إعادة إدخال يدوي."
                    />

                    <p class="mx-auto mt-6 max-w-3xl text-center">
                        توظيف ← توظيف رسمي ← تتبع العمل ← صرف الرواتب وتوليد إيرادات العملاء — وكل ذلك على لوحة مالية واحدة.
                        هذا هو الفارق: الأنظمة المنفصلة تفرض عليك المصالحة اليدوية؛ مدى يجعل المصالحة تلقائية وقابلة للإثبات.
                    </p>
                </div>
            </div>
        </section>

        {{-- Who we serve and how we judge ourselves: the template's mission row, copy beside a card on circles. --}}
        <section class="site-section">
            <div class="site-container">
                <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                    <div>
                        <h2 class="site-section-title site-section-title--start">من نخدم؟</h2>
                        <p class="mt-10">
                            المؤسسات الصغيرة والمتوسطة (حوالي 5–500 موظف) التي تستخدم اليوم أدوات متفرقة أو تجاوزت جداول البيانات دون أن تناسبها أنظمة المؤسسات الثقيلة.
                        </p>
                        <p class="mt-4">
                            السوق الأساسي: منطقة الشرق الأوسط وشمال أفريقيا — لذا العربية أولاً مع دعم ثنائي اللغة قابل للتوسع.
                        </p>
                    </div>

                    <div class="relative isolate p-6 sm:p-[60px]">
                        <x-marketing.circles set="figure" />

                        <div class="site-card relative p-7">
                            <h2 class="site-h4">معايير نجاحنا</h2>
                            <ul class="mt-5 space-y-4">
                                @foreach ($criteria as $criterion)
                                    <li class="flex gap-3">
                                        <span class="mt-1 text-site-primary">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                        </span>
                                        {{ $criterion }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
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
