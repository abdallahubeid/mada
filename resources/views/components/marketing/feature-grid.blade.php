@props([
    'title' => null,
    'subtitle' => null,
    'offerings' => null,
])

@php
    $offerings ??= collect();
    $sectionTitle = $title ?? ($settings['offerings_title'] ?? '');
    $sectionSubtitle = $subtitle ?? ($settings['offerings_sub_title'] ?? '');
@endphp

{{--
    Template `Features`: centred heading, then white cards with a faint
    shadow and the square outlined icon box. On hover the shadow gives way to
    a peach hairline and the icon box fills with cream from the top.

    The template runs these through a carousel; a grid shows every pillar at
    once, which suits four items better than a slider with one hidden.
--}}
<section id="features" class="site-section">
    <div class="site-container text-center">
        <x-marketing.section-heading :title="$sectionTitle" :subtitle="$sectionSubtitle" />

        <div class="mt-10 grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($offerings as $offering)
                <article class="site-feature-card">
                    <span class="site-feature-card-icon">
                        @if ($offering->icon)
                            <x-ui.icon :name="$offering->icon" class="h-8 w-8" />
                        @endif
                    </span>
                    <h3 class="site-h4 mt-6 mb-5">{{ $offering->title }}</h3>
                    <p>{{ $offering->description }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>
