{{-- Features page — CMS feature headings + shared sections. --}}
<x-layouts.marketing
    title="المميزات — Veyra ERP"
    description="اكتشف قدرات Veyra ERP: أمان متعدد المستأجرين، موارد بشرية وتوظيف، مشاريع وعمليات، ورواتب وتحليلات مالية في منصة واحدة."
>
    <x-marketing.nav />

    <main>
        <x-marketing.page-hero
            eyebrow="المميزات"
            title="كل ما تحتاجه لإدارة مؤسستك بذكاء"
            subtitle="منصة متكاملة تجمع التوظيف والموارد البشرية والرواتب والحوكمة — بواجهة عربية أصلية وتصميم يليق بمؤسستك."
        />

        <x-marketing.feature-grid
            :title="$features['title']"
            :subtitle="$features['subtitle']"
            :offerings="$offerings"
        />

        <x-marketing.module-grid :modules="$modules" />

        <x-marketing.showcase :stats="$productPreviewStats" />

        <x-marketing.differentiators :features="$whyUsFeatures" />

        <x-marketing.cta-band
            :title="$cta['title']"
            :subtitle="$cta['subtitle']"
            :primary-label="$cta['primary']['label']"
            :primary-href="$cta['primary']['url']"
            :secondary-label="$cta['secondary']['label']"
            :secondary-href="$cta['secondary']['url']"
        />
    </main>

    <x-marketing.footer />
</x-layouts.marketing>
