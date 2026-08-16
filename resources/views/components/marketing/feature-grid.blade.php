@props([
    'title' => null,
    'subtitle' => null,
    'offerings' => null,
])

@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Offering>|\Illuminate\Database\Eloquent\Collection<int, \App\Models\Offering> $offerings */
    $offerings ??= collect();

    $sectionTitle = $title ?? ($settings['offerings_title'] ?? '');
    $sectionSubtitle = $subtitle ?? ($settings['offerings_sub_title'] ?? '');

    /*
     * Bento spans, derived from position so the composition survives the CMS.
     * The lead tile runs 3 of 6 columns and carries the product-chrome
     * preview; the rest pair off. A fifth offering falls through to a
     * half-width tile rather than breaking the row.
     */
    $spans = [0 => 'lg:col-span-3', 1 => 'lg:col-span-3', 2 => 'lg:col-span-2', 3 => 'lg:col-span-2', 4 => 'lg:col-span-2'];
@endphp

<section id="features" class="mada-mesh bg-mist-50 py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-marketing.section-heading :title="$sectionTitle" :subtitle="$sectionSubtitle" />

        <div class="mt-16 grid gap-5 sm:grid-cols-2 lg:grid-cols-6">
            @foreach ($offerings as $i => $offering)
                @php
                    $span = $spans[$i] ?? 'lg:col-span-2';
                    $lead = $i < 2;
                @endphp

                <article @class([
                    'mada-surface group flex flex-col p-7 text-start sm:p-8',
                    'mada-surface-feature' => $lead,
                    $span,
                ])>
                    <div class="flex items-center gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-500/8 text-brand-600 ring-1 ring-brand-500/10 transition duration-200 group-hover:bg-brand-500 group-hover:text-white group-hover:ring-brand-500">
                            @if ($offering->icon)
                                <x-ui.icon :name="$offering->icon" class="h-5 w-5" />
                            @endif
                        </span>

                        {{--
                            A contextual badge rather than decoration: it names
                            what the reader is actually looking at. Only on the
                            lead tiles, so it reads as a label and not as a
                            pattern repeated down the grid.
                        --}}
                        @if ($lead)
                            <span class="ms-auto inline-flex items-center gap-1.5 rounded-full bg-success-50 px-2.5 py-1 text-xs font-semibold text-success-500 ring-1 ring-success-500/15">
                                <span class="h-1.5 w-1.5 rounded-full bg-success-500"></span>
                                مُفعّل اليوم
                            </span>
                        @endif
                    </div>

                    <h3 @class([
                        'mt-5 font-display font-bold tracking-tight text-ink-900',
                        'text-xl' => $lead,
                        'text-lg' => ! $lead,
                    ])>{{ $offering->title }}</h3>

                    <p class="mt-3 text-base leading-[1.7] text-mist-600">{{ $offering->description }}</p>

                    @if ($lead)
                        {{--
                            Floating product chrome. Anchored to the bottom with
                            `mt-auto` so both lead tiles line their previews up
                            regardless of copy length, and clipped by a mask so
                            the fragment reads as a window onto a larger screen
                            rather than as a tiny complete widget.
                        --}}
                        <div
                            class="mt-auto pt-7"
                            style="-webkit-mask-image: linear-gradient(to bottom, #000 55%, transparent 100%); mask-image: linear-gradient(to bottom, #000 55%, transparent 100%);"
                            aria-hidden="true"
                        >
                            <div class="rounded-t-xl bg-mist-50 p-3 ring-1 ring-ink-900/5">
                                <div class="flex items-center gap-2">
                                    <span class="h-6 w-6 rounded-md bg-brand-500/12"></span>
                                    <span class="h-1.5 w-20 rounded-full bg-mist-200"></span>
                                    <span class="ms-auto h-4 w-10 rounded bg-success-500/15"></span>
                                </div>
                                <div class="mt-3 space-y-2">
                                    @foreach ([100, 78, 88] as $w)
                                        <div class="flex items-center gap-2">
                                            <span class="h-1.5 rounded-full bg-mist-200" style="width: {{ $w }}%"></span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</section>
