@props([
    'aiFeatures' => null,
])

@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\AiFeature>|\Illuminate\Database\Eloquent\Collection<int, \App\Models\AiFeature> $aiFeatures */
    $aiFeatures ??= collect();
@endphp

<section class="relative overflow-hidden bg-mist-50 py-24">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute top-0 start-1/2 h-72 w-72 -translate-x-1/2 rounded-md bg-brand-500/15 blur-3xl"></div>
    </div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <span class="inline-flex items-center gap-2 rounded-md border border-brand-500/30 bg-brand-500/10 px-4 py-1.5 text-xs font-semibold text-brand-600">
                <span class="h-1.5 w-1.5 rounded-md bg-brand-500"></span>
                {{ $settings['ai_badge_text'] ?? 'قريباً · خارطة الطريق' }}
            </span>
            <h2 class="mt-4 font-display text-3xl font-medium text-ink-900 sm:text-4xl">{{ $settings['ai_title'] ?? '' }}</h2>
            <p class="mt-4 text-mist-400">{{ $settings['ai_sub_title'] ?? '' }}</p>
        </div>

        <div class="mt-16 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($aiFeatures as $feature)
                <div class="mada-surface relative p-7">
                    <span class="absolute end-4 top-4 rounded-md bg-mist-100 px-2 py-0.5 text-xs font-semibold uppercase text-mist-400">قريباً</span>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-500/10 text-brand-600">
                        @if ($feature->icon)
                            <x-ui.icon :name="$feature->icon" class="h-6 w-6" />
                        @endif
                    </div>
                    <h3 class="mt-5 font-display text-lg font-bold text-ink-900">{{ $feature->title }}</h3>
                    <p class="mt-2 text-base leading-relaxed text-mist-400">{{ $feature->description }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
