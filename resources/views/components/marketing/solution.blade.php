@props([
    'modules' => null,
    'solutions' => null,
])

@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Module>|\Illuminate\Database\Eloquent\Collection<int, \App\Models\Module> $modules */
    $modules ??= collect();

    /** @var \Illuminate\Support\Collection<int, \App\Models\Solution>|\Illuminate\Database\Eloquent\Collection<int, \App\Models\Solution> $solutions */
    $solutions ??= collect();

    $progressWidths = [90, 75, 85, 70];
@endphp

<section class="bg-ink-100 py-24 dark:bg-ink-950">
    <div class="mx-auto grid max-w-7xl gap-16 px-4 sm:px-6 lg:grid-cols-2 lg:items-center lg:px-8">
        {{-- Visual --}}
        <div class="relative order-last lg:order-first">
            <div class="rounded-3xl border border-mist-200 bg-white p-6 shadow-xl dark:border-ink-800 dark:bg-ink-800/60">
                <div class="space-y-4">
                    @foreach ($modules as $index => $module)
                        <div class="flex items-center gap-4 rounded-xl border border-mist-200 bg-ink-50/50 p-4 dark:border-ink-800 dark:bg-ink-900">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-400/15 font-display text-sm font-bold text-emerald-800 dark:text-emerald-400">{{ $index + 1 }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-ink-900 dark:text-ink-50">{{ $module->title }}</p>
                                <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-mist-200 dark:bg-ink-700">
                                    <div class="h-full rounded-full bg-emerald-400" style="width: {{ $progressWidths[$index] ?? 70 }}%"></div>
                                </div>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75" /></svg>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="pointer-events-none absolute -inset-4 -z-10 rounded-[2rem] bg-emerald-400/10 blur-2xl" aria-hidden="true"></div>
        </div>

        {{-- Copy --}}
        <div>
            <x-marketing.section-heading
                :eyebrow="$settings['solutions_badge_text'] ?? 'الحل'"
                :title="$settings['solutions_title'] ?? ''"
                :subtitle="$settings['solutions_sub_title'] ?? ''"
                align="start"
            />

            <ul class="mt-8 space-y-4">
                @foreach ($solutions as $solution)
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-400/15 text-emerald-600 dark:text-emerald-400">
                            @if ($solution->icon)
                                <iconify-icon icon="{{ $solution->icon }}" width="16" height="16" aria-hidden="true"></iconify-icon>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            @endif
                        </span>
                        <span class="text-mist-600 dark:text-mist-300">{{ $solution->title }}</span>
                    </li>
                @endforeach
            </ul>

            @if ($settings['solutions_btn_text'] ?? null)
                <a href="{{ $settings['solutions_btn_link'] ?? '#' }}" class="mt-8 inline-flex items-center gap-2 text-sm font-semibold text-emerald-600 transition hover:gap-3 dark:text-emerald-400">
                    {{ $settings['solutions_btn_text'] }}
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" /></svg>
                </a>
            @endif
        </div>
    </div>
</section>
