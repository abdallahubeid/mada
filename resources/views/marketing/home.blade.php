{{--
    Landing page (docs/MARKETING.md §4). Data assembled by MarketingContent
    from DB (plans, faqs, testimonials, settings) with config fallbacks.
--}}
@php
    /** @var array<string, mixed> $content */
    $hero = $content['hero'];
    $partners = $content['partners'];
@endphp

<x-layouts.marketing title="مدى — مستقبل إدارة المؤسسات">
    <x-marketing.nav />

    <main>
        <x-marketing.hero :hero="$hero" />
        <x-marketing.product-video />
        <x-marketing.logo-ticker :eyebrow="$partners['eyebrow']" :brands="$partners['names']" />
        <x-marketing.problems :problems="$content['problems']" />
        <x-marketing.solution :modules="$content['solution_sidebar_modules']" :solutions="$content['solutions']" />
        <x-marketing.feature-grid :offerings="$content['offerings']" />
        <x-marketing.module-grid :modules="$content['modules']" />
        <x-marketing.showcase :stats="$content['product_preview_stats']" />
        <x-marketing.ai-capabilities :ai-features="$content['ai_features']" />
        <x-marketing.differentiators :features="$content['why_us_features']" />
        <x-marketing.testimonials :testimonials="$content['testimonials']" />
        <x-marketing.pricing-table
            :compact="true"
            :plans="$content['plans']"
            :currency="$content['currency']"
        />
        <x-marketing.faq-accordion :faqs="$content['faqs']" />
        <x-marketing.cta-band />
    </main>

    <x-marketing.footer />
</x-layouts.marketing>
