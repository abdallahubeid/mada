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
    <div class="site-card p-4 shadow-[0_10px_50px_rgb(0_0_0/0.08)] sm:p-5">
        @switch($variant)

            {{-- Audit log: an append-only trail with actor, action and time. --}}
            @case('audit')
                <div class="space-y-2">
                    @foreach ([['اعتماد مسيرة', 'ن. العتيبي', '١٠:٤٢'], ['تعديل عقد', 'س. القحطاني', '٠٩:١٥'], ['إنشاء موظف', 'م. الشمري', '٠٨:٠٣']] as $i => [$action, $actor, $time])
                        <div class="flex items-center gap-3 rounded-md bg-site-cream px-3 py-2.5">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white text-site-primary">
                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            </span>
                            <span class="min-w-0 flex-1 truncate text-xs font-semibold text-site-dark">{{ $action }}</span>
                            <span class="hidden truncate text-xs text-site-text sm:block">{{ $actor }}</span>
                            <span class="shrink-0 text-xs tabular text-site-text">{{ $time }}</span>
                        </div>
                    @endforeach
                </div>
                @break

            {{-- RTL: the same field set, laid out right-to-left, with a matched pair of labels. --}}
            @case('rtl')
                <div class="rounded-md bg-site-cream p-4">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-bold text-site-dark">بيانات الموظف</span>
                        <span class="rounded bg-white px-2 py-0.5 text-xs font-semibold text-site-primary-ink">RTL</span>
                    </div>
                    <div class="mt-4 space-y-3">
                        @foreach ([['الاسم', 'عبدالله عبيد'], ['القسم', 'الموارد البشرية'], ['الرقم الوظيفي', '١٠٤٨']] as [$label, $value])
                            <div class="flex items-baseline justify-between gap-3 border-b border-white pb-2">
                                <span class="shrink-0 text-xs text-site-text">{{ $label }}</span>
                                <span class="truncate text-xs font-semibold tabular text-site-dark">{{ $value }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                @break

            {{-- Export: a progress meter mid-run with a file chip. --}}
            @case('export')
                <div class="rounded-md bg-site-cream p-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-site-primary">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-bold text-site-dark">payroll-2026-08.xlsx</p>
                            <p class="text-xs tabular text-site-text">١٤٨ سجلاً</p>
                        </div>
                    </div>
                    <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-white">
                        {{-- Static width: a looping progress bar on a marketing page is motion for its own sake. --}}
                        <span class="block h-full w-3/4 rounded-full bg-site-primary"></span>
                    </div>
                    <p class="mt-2 text-xs tabular text-site-text">٧٥٪ — جارٍ التصدير</p>
                </div>
                @break

            {{-- Speed: a response-time readout with a sparkline. --}}
            @case('speed')
                <div class="rounded-md bg-site-cream p-4">
                    <div class="flex items-baseline justify-between">
                        <span class="text-xs text-site-text">زمن الاستجابة</span>
                        <span class="inline-flex items-center gap-1 rounded bg-white px-2 py-0.5 text-xs font-semibold text-site-primary-ink">
                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" d="M12 19V5m0 0-6 6m6-6 6 6" /></svg>
                            مستقر
                        </span>
                    </div>
                    <p class="mt-1 text-2xl font-bold tabular text-site-dark">٨٩<span class="text-base font-bold text-site-text"> ms</span></p>
                    <svg class="mt-3 h-10 w-full text-site-primary" viewBox="0 0 200 40" fill="none" preserveAspectRatio="none">
                        <path d="M0 30 L25 26 L50 31 L75 18 L100 24 L125 14 L150 20 L175 11 L200 16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M0 30 L25 26 L50 31 L75 18 L100 24 L125 14 L150 20 L175 11 L200 16 L200 40 L0 40 Z" fill="currentColor" opacity="0.08" />
                    </svg>
                </div>
                @break

            {{-- Neutral fallback so a row never renders as a lone text column. --}}
            @default
                <div class="space-y-2.5 rounded-md bg-site-cream p-4">
                    <div class="flex items-center gap-2">
                        <span class="h-7 w-7 rounded-lg bg-white"></span>
                        <span class="h-1.5 w-24 rounded-full bg-site-border-soft"></span>
                    </div>
                    @foreach ([100, 82, 64] as $w)
                        <span class="block h-1.5 rounded-full bg-site-border-soft" style="width: {{ $w }}%"></span>
                    @endforeach
                </div>
        @endswitch
    </div>
</div>
