@props([
    'aiFeatures' => null,
])

@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\AiFeature>|\Illuminate\Database\Eloquent\Collection<int, \App\Models\AiFeature> $aiFeatures */
    $aiFeatures ??= collect();
@endphp

<section class="relative overflow-hidden bg-ink-950 py-24">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute top-0 start-1/2 h-72 w-72 -translate-x-1/2 rounded-full bg-emerald-500/15 blur-3xl"></div>
    </div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <span class="inline-flex items-center gap-2 rounded-full border border-emerald-400/30 bg-emerald-400/10 px-4 py-1.5 text-xs font-semibold text-emerald-400">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                {{ $settings['ai_badge_text'] ?? 'قريباً · خارطة الطريق' }}
            </span>
            <h2 class="mt-4 font-display text-3xl font-bold text-white sm:text-4xl">{{ $settings['ai_title'] ?? '' }}</h2>
            <div class="mx-auto mt-4 h-1 w-16 rounded-full bg-emerald-400"></div>
            <p class="mt-4 text-mist-400">{{ $settings['ai_sub_title'] ?? '' }}</p>
        </div>

        <div class="mt-16 grid gap-6 sm:grid-cols-3">
            @foreach ($aiFeatures as $feature)
                <div class="relative rounded-2xl border border-ink-800 bg-ink-900/60 p-6">
                    <span class="absolute end-4 top-4 rounded-full bg-ink-800 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-mist-400">قريباً</span>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-400/10 text-emerald-400">
                        @if ($feature->icon)
                            <iconify-icon icon="{{ $feature->icon }}" width="24" height="24" aria-hidden="true"></iconify-icon>
                        @endif
                    </div>
                    <h3 class="mt-5 font-display text-lg font-semibold text-white">{{ $feature->title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-mist-400">{{ $feature->description }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
