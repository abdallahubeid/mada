@props([
    'modules' => null,
])

@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Module>|\Illuminate\Database\Eloquent\Collection<int, \App\Models\Module> $modules */
    $modules ??= collect();
@endphp

<section id="modules" class="bg-mist-50 py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-marketing.section-heading
            :eyebrow="$settings['modules_badge_text'] ?? 'الوحدات'"
            :title="$settings['modules_title'] ?? ''"
            :subtitle="$settings['modules_sub_title'] ?? ''"
        />

        {{--
            Bento, not a uniform 3-across. Every fifth module runs double width
            and carries a status strip, so the rhythm restarts down the grid
            rather than presenting seven identical tiles. The span is derived
            from position, so the CMS can add a module without breaking the
            composition.
        --}}
        <div class="mt-16 grid gap-5 sm:grid-cols-2 lg:grid-cols-6">
            @foreach ($modules as $i => $module)
                @php $wide = $i % 5 === 0; @endphp

                <article @class([
                    'mada-surface group flex flex-col p-7',
                    'mada-surface-feature lg:col-span-4' => $wide,
                    'lg:col-span-2' => ! $wide,
                ])>
                    <div class="flex items-start gap-4">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-500/8 text-brand-600 ring-1 ring-brand-500/10 transition duration-200 group-hover:bg-brand-500 group-hover:text-white group-hover:ring-brand-500">
                            @if ($module->icon)
                                <x-ui.icon :name="$module->icon" class="h-5 w-5" />
                            @endif
                        </span>

                        <div class="min-w-0 flex-1">
                            <h3 @class([
                                'font-display font-bold tracking-tight text-ink-900',
                                'text-xl' => $wide,
                                'text-lg' => ! $wide,
                            ])>{{ $module->title }}</h3>
                            <p class="mt-2.5 text-base leading-[1.7] text-mist-600">{{ $module->description }}</p>
                        </div>
                    </div>

                    @if ($wide)
                        {{--
                            Status strip — the module presented as something
                            already running rather than as a bullet point.
                            Decorative, so it is hidden from assistive tech: the
                            claims it implies ("مُفعّل") are marketing framing,
                            not data, and should not be announced as fact.
                        --}}
                        <div class="mt-auto flex flex-wrap items-center gap-2 pt-6" aria-hidden="true">
                            @foreach ([['مُفعّل', 'success'], ['يعمل الآن', 'brand'], ['١٤٨ سجلاً', 'mist']] as [$label, $tone])
                                <span @class([
                                    'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1',
                                    'bg-success-50 text-success-500 ring-success-500/15' => $tone === 'success',
                                    'bg-brand-500/8 text-brand-600 ring-brand-500/15' => $tone === 'brand',
                                    'bg-mist-100 text-mist-500 ring-ink-900/5' => $tone === 'mist',
                                ])>
                                    @if ($tone === 'success')
                                        <span class="h-1.5 w-1.5 rounded-full bg-success-500"></span>
                                    @endif
                                    {{ $label }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</section>
