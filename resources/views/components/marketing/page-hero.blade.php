@props([
    'eyebrow' => null,
    'title' => '',
    'subtitle' => null,
    'crumb' => null,
])

{{--
    Inner-page banner (template `Banner` / `.banner-single`): a flat cream
    panel with the long dashed wave and scattered circles, a centred title,
    and a breadcrumb back to the home page.

    The breadcrumb names the PAGE, not its headline: most pages have a slogan
    as their title ("لنبدأ محادثة") and their name as the eyebrow ("تواصل
    معنا"), so the crumb defaults to the eyebrow. Pages whose title is already
    the name (the legal pages) pass `crumb` explicitly.
--}}
@php
    $crumb ??= $eyebrow ?? $title;
@endphp

<section class="site-container-xl">
    <div class="site-panel-host px-4 py-20 text-center sm:py-[120px]">
        <x-marketing.panel variant="flat" circles="single" />

        <h1 class="mx-auto max-w-4xl">{{ $title }}</h1>

        @if ($subtitle)
            <p class="mx-auto mt-6 max-w-2xl text-base">{{ $subtitle }}</p>
        @endif

        <nav class="mt-8" aria-label="مسار التنقّل">
            <ol class="flex flex-wrap items-center justify-center gap-2">
                <li><a href="{{ route('landing') }}" class="inline-flex min-h-6 items-center text-site-primary-ink hover:underline">الرئيسية</a></li>
                <li aria-hidden="true">/</li>
                <li aria-current="page">{{ $crumb }}</li>
            </ol>
        </nav>
    </div>
</section>
