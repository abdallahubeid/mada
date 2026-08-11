@props([
    'modules' => null,
])

@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Module>|\Illuminate\Database\Eloquent\Collection<int, \App\Models\Module> $modules */
    $modules ??= collect();
@endphp

<section id="modules" class="bg-ink-100 py-24 dark:bg-ink-950">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-marketing.section-heading
            :eyebrow="$settings['modules_badge_text'] ?? 'الوحدات'"
            :title="$settings['modules_title'] ?? ''"
            :subtitle="$settings['modules_sub_title'] ?? ''"
        />

        <div class="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($modules as $module)
                <div class="veyra-card group flex items-start gap-4 rounded-2xl border border-mist-200 bg-white p-6 hover:border-emerald-400/50 hover:shadow-lg dark:border-ink-800 dark:bg-ink-800/60">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-400/10 text-emerald-600 transition duration-200 group-hover:bg-emerald-400 group-hover:text-ink-950 dark:text-emerald-400">
                        @if ($module->icon)
                            <iconify-icon icon="{{ $module->icon }}" width="24" height="24" aria-hidden="true"></iconify-icon>
                        @endif
                    </span>
                    <div class="min-w-0">
                        <h3 class="font-display text-lg font-semibold text-ink-900 dark:text-ink-50">{{ $module->title }}</h3>
                        <p class="mt-1 text-sm leading-relaxed text-mist-500 dark:text-mist-400">{{ $module->description }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
