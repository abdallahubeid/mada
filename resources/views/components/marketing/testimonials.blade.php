@props([
    'testimonials' => null,
])

@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Testimonial>|\Illuminate\Database\Eloquent\Collection<int, \App\Models\Testimonial> $testimonials */
    $testimonials ??= collect();
@endphp

@if ($testimonials->isNotEmpty())
<section class="bg-mist-50 py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-marketing.section-heading
            :eyebrow="$settings['testimonials_badge_text'] ?? 'قصص نجاح'"
            :title="$settings['testimonials_title'] ?? ''"
            :subtitle="$settings['testimonials_sub_title'] ?? ''"
        />

        {{--
            Odoo's testimonial layout, and a deliberate departure from the
            previous three-up grid of equal cards.

            The quote LEADS at a size you actually read, with an oversized
            quote mark behind it; the attribution block sits underneath as a
            distinct footer band carrying avatar, name, role and the company
            badge. A grid of three small quotes reads as filler — one large
            quote reads as a reference.

            `lg:grid-cols-2` rather than three: at three columns the quote text
            drops below ~40 characters per line and stops being readable at
            this size, which is the whole point of leading with it.
        --}}
        <div class="mt-16 grid grid-cols-1 gap-6 lg:grid-cols-2">
            @foreach ($testimonials as $testimonial)
                @php
                    $avatar = $testimonial->relationLoaded('images')
                        ? ($testimonial->images->firstWhere('collection', 'avatar') ?? $testimonial->images->firstWhere('collection', 'logo'))
                        : ($testimonial->image('avatar')->first() ?? $testimonial->image('logo')->first());
                    $logo = $testimonial->relationLoaded('images')
                        ? $testimonial->images->firstWhere('collection', 'logo')
                        : $testimonial->image('logo')->first();
                    $initial = mb_substr($testimonial->client_name, 0, 1);
                    $rating = max(1, min(5, (int) ($testimonial->rate ?? 5)));
                @endphp

                <figure class="mada-surface relative flex h-full flex-col overflow-hidden p-7 sm:p-9">
                    {{--
                        The oversized quote mark. `select-none` + aria-hidden
                        because a screen reader announcing a lone quotation
                        glyph before every testimonial is noise, and because a
                        user dragging to copy the quote should not catch it.

                        Positioned at the inline START so it opens the quote in
                        both directions — in RTL that is the right edge, which
                        is where an Arabic quotation actually begins.
                    --}}
                    <span
                        class="pointer-events-none absolute -top-6 start-4 select-none font-display text-[7rem] leading-none font-medium text-brand-500/10"
                        aria-hidden="true"
                    >”</span>

                    <div class="relative flex gap-1 text-marker-500" aria-label="{{ $rating }} من 5">
                        @for ($i = 0; $i < $rating; $i++)
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.5.04.703.662.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345l2.125-5.111Z" /></svg>
                        @endfor
                    </div>

                    <blockquote class="relative mt-5 flex-1 text-lg leading-relaxed font-medium text-ink-800 sm:text-xl">
                        {{ $testimonial->quote }}
                    </blockquote>

                    <figcaption class="relative mt-8 flex items-center gap-4 border-t border-mist-200 pt-6">
                        @if ($avatar)
                            <img
                                src="{{ $avatar->url() }}"
                                alt="{{ $avatar->alt_text ?? $testimonial->client_name }}"
                                class="h-12 w-12 shrink-0 rounded-full object-cover ring-2 ring-white outline outline-1 outline-mist-200"
                                loading="lazy"
                            >
                        @else
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-brand-500/12 font-display text-base font-bold text-brand-600">{{ $initial }}</span>
                        @endif

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-bold text-ink-900">{{ $testimonial->client_name }}</p>
                            @if ($testimonial->client_role || $testimonial->organization_name)
                                <p class="truncate text-xs text-mist-500">
                                    {{ collect([$testimonial->client_role, $testimonial->organization_name])->filter()->implode(' · ') }}
                                </p>
                            @endif
                        </div>

                        {{--
                            Company badge. Only rendered when a `logo` image
                            exists AND it is not the same record already used
                            as the avatar — otherwise the identical picture
                            appears twice in one row, which is what the
                            avatar's own logo-fallback would otherwise cause.
                        --}}
                        @if ($logo && (! $avatar || $logo->id !== $avatar->id))
                            <span class="hidden shrink-0 items-center rounded-lg border border-mist-200 bg-mist-50 px-3 py-2 sm:inline-flex">
                                <img
                                    src="{{ $logo->url() }}"
                                    alt="{{ $logo->alt_text ?? $testimonial->organization_name }}"
                                    class="h-5 max-w-[5.5rem] object-contain"
                                    loading="lazy"
                                >
                            </span>
                        @endif
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</section>
@endif
