@props([
    'label' => '',
    'value' => '',
    'delta' => null,
    'trend' => 'up',
    'icon' => null,
    'accent' => false,
    'muted' => false,
    'phaseTag' => null,
])

@php
    /*
     * Metric card for the Platform Console dashboards (docs/DESIGN_SYSTEM.md
     * §4 `card`). `accent` highlights the daily-action metric (pending
     * approvals); `muted` + `phaseTag` render a not-yet-live metric (e.g. MRR,
     * a Phase 2 dependency) without implying a capability that isn't shipped.
     */
    $base = 'relative overflow-hidden rounded-2xl border p-5 shadow-sm transition duration-200 ease-out';

    if ($muted) {
        $surface = 'border-dashed border-mist-200 bg-white/60 dark:border-ink-600 dark:bg-ink-800/50';
    } elseif ($accent) {
        $surface = 'border-emerald-400/40 bg-white ring-1 ring-emerald-400/30 dark:bg-ink-800';
    } else {
        $surface = 'border-mist-200 bg-white hover:border-mist-300 dark:border-ink-600 dark:bg-ink-800 dark:hover:border-ink-500';
    }
@endphp

<div {{ $attributes->class([$base, $surface]) }}>
    @if ($accent)
        <div class="pointer-events-none absolute -top-8 -end-8 h-24 w-24 rounded-full bg-emerald-400/20 blur-2xl"></div>
    @endif

    <div class="relative flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="truncate text-sm font-medium {{ $muted ? 'text-mist-400 dark:text-mist-500' : 'text-mist-500 dark:text-mist-400' }}">
                {{ $label }}
            </p>
            <p class="mt-2 font-display text-3xl font-bold {{ $muted ? 'text-mist-400 dark:text-mist-500' : 'text-ink-900 dark:text-ink-50' }}">
                {{ $value }}
            </p>
        </div>

        @if ($icon)
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $accent ? 'bg-emerald-400/15 text-emerald-500 dark:text-emerald-400' : 'bg-mist-100 text-mist-500 dark:bg-ink-700 dark:text-mist-300' }}">
                {!! $icon !!}
            </span>
        @endif
    </div>

    <div class="relative mt-3 flex items-center gap-2">
        @if ($phaseTag)
            <span class="rounded-full bg-mist-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-mist-500 dark:bg-ink-700 dark:text-mist-400">
                {{ $phaseTag }}
            </span>
        @elseif (! is_null($delta))
            <span class="inline-flex items-center gap-1 text-xs font-semibold {{ $trend === 'down' ? 'text-danger-solid' : 'text-emerald-600 dark:text-emerald-400' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 {{ $trend === 'down' ? 'rotate-180' : '' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5m0 0-6 6m6-6 6 6" />
                </svg>
                {{ $delta }}
            </span>
            <span class="text-xs text-mist-400 dark:text-mist-500">مقارنة بالفترة السابقة</span>
        @endif
    </div>
</div>
