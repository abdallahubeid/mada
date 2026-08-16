@props([
    'variant' => null,
])

{{--
    Visual anchor for a split feature row — a stylised fragment of real product
    chrome that shows the claim rather than illustrating it.

    Every variant is decorative and `aria-hidden`: the argument is carried
    entirely by the heading and body copy beside it, so a screen reader loses
    nothing by skipping these. Numerals are Arabic-Indic to match the site.

    An unrecognised variant renders a neutral chrome fragment rather than
    nothing, so a row never collapses to a lone column of text.
--}}
<div {{ $attributes->merge(['class' => 'pointer-events-none select-none']) }} aria-hidden="true">
    <div class="rounded-xl bg-mist-50 p-4 ring-1 ring-ink-900/5 sm:p-5">
        @switch($variant)

            {{-- Audit log: an append-only trail with actor, action and time. --}}
            @case('audit')
                <div class="space-y-2">
                    @foreach ([['اعتماد مسيرة', 'ن. العتيبي', '١٠:٤٢'], ['تعديل عقد', 'س. القحطاني', '٠٩:١٥'], ['إنشاء موظف', 'م. الشمري', '٠٨:٠٣']] as $i => [$action, $actor, $time])
                        <div class="flex items-center gap-3 rounded-lg bg-white px-3 py-2.5 ring-1 ring-ink-900/5">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-success-50 text-success-500">
                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            </span>
                            <span class="min-w-0 flex-1 truncate text-xs font-semibold text-ink-900">{{ $action }}</span>
                            <span class="hidden truncate text-xs text-mist-400 sm:block">{{ $actor }}</span>
                            <span class="shrink-0 text-xs tabular text-mist-400">{{ $time }}</span>
                        </div>
                    @endforeach
                </div>
                @break

            {{-- RTL: the same field set, laid out right-to-left, with a matched pair of labels. --}}
            @case('rtl')
                <div class="rounded-lg bg-white p-4 ring-1 ring-ink-900/5">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-bold text-ink-900">بيانات الموظف</span>
                        <span class="rounded bg-brand-500/10 px-2 py-0.5 text-xs font-semibold text-brand-600">RTL</span>
                    </div>
                    <div class="mt-4 space-y-3">
                        @foreach ([['الاسم', 'عبدالله عبيد'], ['القسم', 'الموارد البشرية'], ['الرقم الوظيفي', '١٠٤٨']] as [$label, $value])
                            <div class="flex items-baseline justify-between gap-3 border-b border-mist-100 pb-2">
                                <span class="shrink-0 text-xs text-mist-400">{{ $label }}</span>
                                <span class="truncate text-xs font-semibold tabular text-ink-900">{{ $value }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                @break

            {{-- Export: a progress meter mid-run with a file chip. --}}
            @case('export')
                <div class="rounded-lg bg-white p-4 ring-1 ring-ink-900/5">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-500/10 text-brand-600">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-bold text-ink-900">payroll-2026-08.xlsx</p>
                            <p class="text-xs tabular text-mist-400">١٤٨ سجلاً</p>
                        </div>
                    </div>
                    <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-mist-100">
                        {{-- Static width: a looping progress bar on a marketing page is motion for its own sake. --}}
                        <span class="block h-full w-3/4 rounded-full bg-brand-500"></span>
                    </div>
                    <p class="mt-2 text-xs tabular text-mist-400">٧٥٪ — جارٍ التصدير</p>
                </div>
                @break

            {{-- Speed: a response-time readout with a sparkline. --}}
            @case('speed')
                <div class="rounded-lg bg-white p-4 ring-1 ring-ink-900/5">
                    <div class="flex items-baseline justify-between">
                        <span class="text-xs text-mist-400">زمن الاستجابة</span>
                        <span class="inline-flex items-center gap-1 rounded bg-success-50 px-2 py-0.5 text-xs font-semibold text-success-500">
                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" d="M12 19V5m0 0-6 6m6-6 6 6" /></svg>
                            مستقر
                        </span>
                    </div>
                    <p class="mt-1 font-display text-2xl font-bold tabular text-ink-900">٨٩<span class="text-base font-bold text-mist-400"> ms</span></p>
                    <svg class="mt-3 h-10 w-full text-brand-500" viewBox="0 0 200 40" fill="none" preserveAspectRatio="none">
                        <path d="M0 30 L25 26 L50 31 L75 18 L100 24 L125 14 L150 20 L175 11 L200 16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M0 30 L25 26 L50 31 L75 18 L100 24 L125 14 L150 20 L175 11 L200 16 L200 40 L0 40 Z" fill="currentColor" opacity="0.08" />
                    </svg>
                </div>
                @break

            {{-- Neutral fallback so a row never renders as a lone text column. --}}
            @default
                <div class="space-y-2.5 rounded-lg bg-white p-4 ring-1 ring-ink-900/5">
                    <div class="flex items-center gap-2">
                        <span class="h-7 w-7 rounded-lg bg-brand-500/10"></span>
                        <span class="h-1.5 w-24 rounded-full bg-mist-200"></span>
                    </div>
                    @foreach ([100, 82, 64] as $w)
                        <span class="block h-1.5 rounded-full bg-mist-200" style="width: {{ $w }}%"></span>
                    @endforeach
                </div>
        @endswitch
    </div>
</div>
