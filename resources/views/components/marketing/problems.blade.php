@props([
    'problems' => null,
])

@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Problem>|\Illuminate\Database\Eloquent\Collection<int, \App\Models\Problem> $problems */
    $problems ??= collect();
@endphp

{{--
    The pain points, laid out like the template's "Works" block on the About
    page: a centred heading, then two columns of centred statements. Each
    gets the template's square outlined icon box so the four read as a set
    without being boxed into cards — the card treatment is kept for the
    feature section that answers them.
--}}
<section class="site-section">
    <div class="site-container">
        <x-marketing.section-heading
            :eyebrow="$settings['problems_badge_text'] ?? 'التحديات'"
            :title="$settings['problems_title'] ?? ''"
            :subtitle="$settings['problems_sub_title'] ?? ''"
        />

        <div class="mx-auto mt-10 grid max-w-5xl gap-x-10 gap-y-12 md:grid-cols-2">
            @foreach ($problems as $problem)
                <article class="text-center md:px-6 xl:px-12">
                    <span class="site-feature-card-icon h-16 w-16">
                        @if ($problem->icon_key)
                            <x-ui.icon :name="$problem->icon_key" class="h-7 w-7" />
                        @endif
                    </span>
                    <h3 class="site-h4 mt-5">{{ $problem->title }}</h3>
                    <p class="mt-2">{{ $problem->description }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>
