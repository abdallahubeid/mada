@props([
    'features' => null,
])

@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Feature>|\Illuminate\Database\Eloquent\Collection<int, \App\Models\Feature> $features */
    $features ??= collect();
@endphp

<section class="bg-ink-100 py-24 dark:bg-ink-950">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-marketing.section-heading
            :eyebrow="$settings['why_us_badge_text'] ?? 'لماذا Veyra'"
            :title="$settings['why_us_title'] ?? ''"
            :subtitle="$settings['why_us_sub_title'] ?? ''"
        />

        <div class="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($features as $feature)
                <div class="rounded-2xl border border-mist-200 bg-white p-6 text-center transition duration-200 ease-out hover:-translate-y-1 hover:border-emerald-400/50 hover:shadow-lg dark:border-ink-800 dark:bg-ink-800/60">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-400/10 text-emerald-600 dark:text-emerald-400">
                        @if ($feature->icon)
                            <iconify-icon icon="{{ $feature->icon }}" width="28" height="28" aria-hidden="true"></iconify-icon>
                        @endif
                    </div>
                    <h3 class="mt-5 font-display text-lg font-semibold text-ink-900 dark:text-ink-50">{{ $feature->title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-mist-500 dark:text-mist-400">{{ $feature->description }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
