{{--
    Landing page (docs/MARKETING.md §4). Data assembled by MarketingContent
    from DB (plans, faqs, testimonials, settings) with config fallbacks.
--}}
@php
    /** @var array<string, mixed> $content */
    $hero = $content['hero'];
    $partners = $content['partners'];
    $cta = $content['cta'];
    $featuresHeading = $content['features'];
@endphp

<x-layouts.marketing title="Veyra ERP — مستقبل إدارة المؤسسات">
    <x-marketing.nav />

    <main>
        <x-marketing.hero :hero="$hero" />
        <x-marketing.logo-ticker :eyebrow="$partners['eyebrow']" :brands="$partners['names']" />
        <x-marketing.problems />
        <x-marketing.solution />
        <x-marketing.feature-grid :title="$featuresHeading['title']" :subtitle="$featuresHeading['subtitle']" />
        <x-marketing.module-grid />
        <x-marketing.showcase />
        <x-marketing.ai-capabilities />
        <x-marketing.differentiators />
        <x-marketing.testimonials :testimonials="$content['testimonials']" />
        <x-marketing.pricing-table
            :compact="true"
            :plans="$content['plans']"
            :currency="$content['currency']"
        />
        <x-marketing.faq-accordion :items="$content['faqs']" />
        <x-marketing.cta-band
            :title="$cta['title']"
            :subtitle="$cta['subtitle']"
            :primary-label="$cta['primary']['label']"
            :primary-href="$cta['primary']['url']"
            :secondary-label="$cta['secondary']['label']"
            :secondary-href="$cta['secondary']['url']"
        />
    </main>

    <x-marketing.footer :footer="$content['footer']" />
</x-layouts.marketing>
