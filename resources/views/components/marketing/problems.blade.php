@props([
    'problems' => null,
])

@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Problem>|\Illuminate\Database\Eloquent\Collection<int, \App\Models\Problem> $problems */
    $problems ??= collect();
@endphp

<section class="bg-white py-24 dark:bg-ink-900">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-marketing.section-heading
            :eyebrow="$settings['problems_badge_text'] ?? 'التحديات'"
            :title="$settings['problems_title'] ?? ''"
            :subtitle="$settings['problems_sub_title'] ?? ''"
        />

        <div class="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($problems as $problem)
                <div class="rounded-2xl border border-mist-200 bg-ink-50/40 p-6 transition duration-200 ease-out hover:-translate-y-1 hover:border-danger-solid/40 dark:border-ink-800 dark:bg-ink-800/40">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-danger-solid/10 text-danger-solid">
                        @if ($problem->icon_key)
                            <iconify-icon icon="{{ $problem->icon_key }}" width="24" height="24" aria-hidden="true"></iconify-icon>
                        @endif
                    </div>
                    <h3 class="mt-5 font-display text-lg font-semibold text-ink-900 dark:text-ink-50">{{ $problem->title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-mist-500 dark:text-mist-400">{{ $problem->description }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
