@props([
    'title' => null,
    'subtitle' => null,
    'offerings' => null,
])

@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Offering>|\Illuminate\Database\Eloquent\Collection<int, \App\Models\Offering> $offerings */
    $offerings ??= collect();

    $sectionTitle = $title ?? ($settings['offerings_title'] ?? '');
    $sectionSubtitle = $subtitle ?? ($settings['offerings_sub_title'] ?? '');
@endphp

<section id="features" class="bg-white py-24 dark:bg-ink-900">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-marketing.section-heading :title="$sectionTitle" :subtitle="$sectionSubtitle" />

        <div class="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($offerings as $offering)
                <div class="veyra-card group rounded-2xl border border-mist-200 bg-ink-50/40 p-6 text-start shadow-sm hover:border-emerald-400/50 hover:shadow-lg dark:border-ink-800 dark:bg-ink-800/60">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-400/10 text-emerald-600 transition duration-200 group-hover:bg-emerald-400 group-hover:text-ink-950 dark:text-emerald-400">
                        @if ($offering->icon)
                            <iconify-icon icon="{{ $offering->icon }}" width="24" height="24" aria-hidden="true"></iconify-icon>
                        @endif
                    </div>
                    <h3 class="mt-5 font-display text-lg font-semibold text-ink-900 dark:text-ink-50">{{ $offering->title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-mist-500 dark:text-mist-400">{{ $offering->description }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
