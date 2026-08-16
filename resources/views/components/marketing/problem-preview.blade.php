@props([
    'key' => null,
])

{{--
    Micro-UI preview for a pain-point card.

    Each variant is a small, stylised fragment of REAL product chrome — a
    broken link between tables, a stalled approval queue, a flat metric, a
    permissions row — rather than an illustration. A pain-point card that shows
    the shape of the problem lands harder than one that describes it next to a
    generic icon, and it is what separates a Linear/Rippling grid from a
    four-across row of icon-and-paragraph tiles.

    Keyed on `icon_key`, which the CMS already stores per problem — so no
    schema change, no new column, and an unrecognised key simply renders
    nothing rather than breaking the card.

    Everything here is decorative: `aria-hidden`, no text a screen reader needs,
    and every card's meaning is carried entirely by its real heading and body
    copy above it. The numerals are Arabic-Indic to match the rest of the site.
--}}
@php
    $variant = match ($key) {
        'ph:link-bold' => 'disconnected',
        'ph:clock-bold' => 'queue',
        'ph:chart-bar-bold' => 'metric',
        'ph:warning-bold' => 'permissions',
        default => null,
    };
@endphp

@if ($variant)
    <div {{ $attributes->merge(['class' => 'pointer-events-none select-none']) }} aria-hidden="true">
        @switch($variant)

            {{-- Two record tables with a severed link between them. --}}
            @case('disconnected')
                <div class="flex items-center gap-2">
                    <div class="flex-1 rounded-lg border border-mist-200 bg-white p-2.5">
                        <div class="h-1.5 w-10 rounded-full bg-mist-300"></div>
                        <div class="mt-2 space-y-1.5">
                            <div class="h-1.5 w-full rounded-full bg-mist-200"></div>
                            <div class="h-1.5 w-3/4 rounded-full bg-mist-200"></div>
                        </div>
                    </div>

                    {{-- The break: a dashed rule interrupted by a slashed link glyph. --}}
                    <div class="flex shrink-0 flex-col items-center gap-1">
                        <span class="block h-px w-6 border-t border-dashed border-critical-500/50"></span>
                        <svg class="h-4 w-4 text-critical-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" d="M9 15 15 9M4.5 4.5l15 15" />
                        </svg>
                        <span class="block h-px w-6 border-t border-dashed border-critical-500/50"></span>
                    </div>

                    <div class="flex-1 rounded-lg border border-mist-200 bg-white p-2.5">
                        <div class="h-1.5 w-8 rounded-full bg-mist-300"></div>
                        <div class="mt-2 space-y-1.5">
                            <div class="h-1.5 w-full rounded-full bg-mist-200"></div>
                            <div class="h-1.5 w-2/3 rounded-full bg-mist-200"></div>
                        </div>
                    </div>
                </div>
                @break

            {{-- A stalled approval queue: two rows waiting, one aging. --}}
            @case('queue')
                <div class="space-y-1.5">
                    @foreach ([['٣ أيام', true], ['يومان', true], ['ساعات', false]] as [$age, $stale])
                        <div class="flex items-center gap-2 rounded-lg border border-mist-200 bg-white px-2.5 py-2">
                            <span class="h-5 w-5 shrink-0 rounded-full bg-mist-100"></span>
                            <span class="h-1.5 flex-1 rounded-full bg-mist-200"></span>
                            <span @class([
                                'shrink-0 rounded px-1.5 py-0.5 text-xs font-semibold tabular',
                                'bg-critical-50 text-critical-500' => $stale,
                                'bg-mist-100 text-mist-500' => ! $stale,
                            ])>{{ $age }}</span>
                        </div>
                    @endforeach
                </div>
                @break

            {{-- A metric card with no trend — the number without the context. --}}
            @case('metric')
                <div class="rounded-lg border border-mist-200 bg-white p-3">
                    <div class="flex items-baseline justify-between gap-2">
                        <span class="h-1.5 w-14 rounded-full bg-mist-200"></span>
                        <span class="rounded bg-mist-100 px-1.5 py-0.5 text-xs font-semibold text-mist-400">—</span>
                    </div>
                    <p class="mt-2 font-display text-xl font-bold text-mist-300 tabular">؟؟؟٬؟؟؟</p>
                    {{-- Flat, greyed bars: data exists, insight does not. --}}
                    <div class="mt-2.5 flex h-8 items-end gap-1">
                        @foreach ([40, 55, 45, 50, 42, 48] as $h)
                            <span class="w-full rounded-t-xs bg-mist-200" style="height: {{ $h }}%"></span>
                        @endforeach
                    </div>
                </div>
                @break

            {{-- A permissions row with an access grant left switched on. --}}
            @case('permissions')
                <div class="space-y-1.5">
                    @foreach ([['كل البيانات', true], ['تصدير', true], ['السجلات', false]] as [$label, $on])
                        <div class="flex items-center justify-between gap-2 rounded-lg border border-mist-200 bg-white px-2.5 py-2">
                            <span class="text-xs font-medium text-mist-500">{{ $label }}</span>
                            <span @class([
                                'relative h-4 w-7 shrink-0 rounded-full transition',
                                'bg-critical-500/70' => $on,
                                'bg-mist-200' => ! $on,
                            ])>
                                <span @class([
                                    'absolute top-0.5 h-3 w-3 rounded-full bg-white',
                                    'end-0.5' => $on,
                                    'start-0.5' => ! $on,
                                ])></span>
                            </span>
                        </div>
                    @endforeach
                </div>
                @break

        @endswitch
    </div>
@endif
