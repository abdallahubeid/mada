@props([
    'testimonials' => null,
])

@php
    $testimonials ??= collect();
    $count = $testimonials->count();
@endphp

@if ($testimonials->isNotEmpty())
{{--
    Template `Testimonial`: one quote at a time, white text on the orange
    blob, with the author on a white card below it.

    The slider advances on its own, so it follows WCAG 2.2.2: it pauses
    while hovered or focused, has an explicit pause button, and never starts
    for visitors who ask for reduced motion. Every quote stays in the DOM, so
    the content is also reachable without the slider at all.
--}}
<section class="site-section">
    <div class="site-container">
        <x-marketing.section-heading
            :eyebrow="$settings['testimonials_badge_text'] ?? 'قصص نجاح'"
            :title="$settings['testimonials_title'] ?? ''"
            :subtitle="$settings['testimonials_sub_title'] ?? ''"
        />

        <div
            x-data="{
                active: 0,
                count: {{ $count }},
                playing: false,
                timer: null,
                init() {
                    if (this.count > 1 && ! window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                        this.play();
                    }
                },
                play() {
                    this.stop();
                    this.playing = true;
                    // Skips while the tab is hidden: browsers freeze transitions in
                    // background tabs, and queued slide changes would pile up behind them.
                    this.timer = setInterval(() => {
                        if (! document.hidden) {
                            this.active = (this.active + 1) % this.count;
                        }
                    }, 6000);
                },
                stop() {
                    clearInterval(this.timer);
                    this.timer = null;
                },
                pause() {
                    this.stop();
                    this.playing = false;
                },
            }"
            @mouseenter="stop()"
            @mouseleave="playing && play()"
            @focusin="stop()"
            @focusout="playing && play()"
            class="mt-10 grid items-center justify-center gap-8 lg:grid-cols-[1fr_minmax(0,480px)_1fr]"
            role="region"
            aria-roledescription="carousel"
            aria-label="آراء العملاء"
        >
            {{-- Side compositions, standing in for the template's avatar collages. --}}
            <div class="relative hidden h-80 lg:block" aria-hidden="true">
                <span class="site-circle start-[20%] top-[10%]" style="width: 85px; height: 85px;"></span>
                <span class="site-circle site-circle--soft end-[15%] top-[35%]" style="width: 62px; height: 62px;"></span>
                <span class="site-circle site-circle--soft start-[35%] bottom-[12%]" style="width: 47px; height: 47px;"></span>
                <span class="site-circle end-[40%] bottom-[30%]" style="width: 20px; height: 20px;"></span>
            </div>

            <div class="mx-auto w-full max-w-[480px]">
                <div class="site-testimonial-blob rounded-[3rem]">
                    @foreach ($testimonials as $i => $testimonial)
                        @php
                            $avatar = $testimonial->relationLoaded('images')
                                ? ($testimonial->images->firstWhere('collection', 'avatar') ?? $testimonial->images->firstWhere('collection', 'logo'))
                                : ($testimonial->image('avatar')->first() ?? $testimonial->image('logo')->first());
                            $initial = mb_substr($testimonial->client_name, 0, 1);
                            $rating = max(1, min(5, (int) ($testimonial->rate ?? 5)));
                        @endphp

                        <figure
                            x-show="active === {{ $i }}"
                            @if ($i > 0) x-cloak @endif
                            x-transition.opacity.duration.300ms
                            class="px-8 py-10 text-center sm:px-12 sm:py-12"
                            role="group"
                            aria-roledescription="slide"
                            aria-label="{{ $i + 1 }} من {{ $count }}"
                        >
                            <svg class="mx-auto h-12 w-12 text-white sm:h-16 sm:w-16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M10 11H6.21c.32-2.28 1.66-3.6 3.79-4V5c-3.6.42-6 2.97-6 7v7h6v-8Zm10 0h-3.79c.32-2.28 1.66-3.6 3.79-4V5c-3.6.42-6 2.97-6 7v7h6v-8Z" />
                            </svg>

                            <blockquote class="mt-5 text-[17px] leading-relaxed font-medium text-white lg:text-lg">
                                {{ $testimonial->quote }}
                            </blockquote>

                            <figcaption class="mt-8 inline-block min-w-[14rem] rounded-md bg-white p-7 shadow-[0_10px_50px_rgb(0_0_0/0.08)]">
                                @if ($avatar)
                                    <img
                                        src="{{ $avatar->url() }}"
                                        alt="{{ $avatar->alt_text ?? $testimonial->client_name }}"
                                        class="mx-auto h-[90px] w-[90px] rounded-full object-cover"
                                        loading="lazy"
                                    >
                                @else
                                    <span class="mx-auto flex h-[90px] w-[90px] items-center justify-center rounded-full bg-site-cream text-3xl font-semibold text-site-primary-ink" aria-hidden="true">{{ $initial }}</span>
                                @endif

                                <p class="site-h6 mt-3 font-semibold text-site-dark">{{ $testimonial->client_name }}</p>
                                @if ($testimonial->client_role || $testimonial->organization_name)
                                    <p>{{ collect([$testimonial->client_role, $testimonial->organization_name])->filter()->implode(' · ') }}</p>
                                @endif

                                <div class="mt-2 flex justify-center gap-0.5 text-site-primary" role="img" aria-label="التقييم {{ $rating }} من 5">
                                    @for ($star = 0; $star < $rating; $star++)
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.5.04.703.662.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345l2.125-5.111Z" /></svg>
                                    @endfor
                                </div>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>

                @if ($count > 1)
                    <div class="mt-6 flex items-center justify-center gap-3">
                        @foreach ($testimonials as $i => $testimonial)
                            <button
                                type="button"
                                @click="active = {{ $i }}"
                                :class="active === {{ $i }} ? 'bg-site-primary' : 'bg-site-circle'"
                                :aria-current="active === {{ $i }} ? 'true' : 'false'"
                                class="relative h-[15px] w-[15px] rounded-full transition-colors before:absolute before:-inset-[5px] before:content-['']"
                                aria-label="عرض الرأي {{ $i + 1 }}"
                            ></button>
                        @endforeach

                        <button
                            type="button"
                            @click="playing ? pause() : play()"
                            class="ms-2 flex h-8 w-8 items-center justify-center rounded-full border border-site-border text-site-dark transition hover:border-site-primary hover:text-site-primary-ink"
                            :aria-label="playing ? 'إيقاف التبديل التلقائي' : 'تشغيل التبديل التلقائي'"
                            aria-label="تشغيل التبديل التلقائي"
                        >
                            <svg x-show="playing" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 5h3v14H7zM14 5h3v14h-3z" /></svg>
                            <svg x-show="! playing" x-cloak class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z" /></svg>
                        </button>
                    </div>
                @endif
            </div>

            <div class="relative hidden h-80 lg:block" aria-hidden="true">
                <span class="site-circle site-circle--soft start-[15%] top-[20%]" style="width: 73px; height: 73px;"></span>
                <span class="site-circle end-[25%] top-[45%]" style="width: 37px; height: 37px;"></span>
                <span class="site-circle start-[30%] bottom-[10%]" style="width: 65px; height: 65px;"></span>
                <span class="site-circle site-circle--soft end-[10%] top-[8%]" style="width: 20px; height: 20px;"></span>
            </div>
        </div>
    </div>
</section>
@endif
