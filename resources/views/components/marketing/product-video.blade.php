@props([
    'src' => null,
    'poster' => null,
])

@php
    /*
     * Product reel — a full-bleed, edge-to-edge band of product footage that
     * runs straight out of the hero.
     *
     * ─────────────────────────────────────────────────────────────────────
     * THIS IS NOT A VIDEO PLAYER. IT IS A MOVING IMAGE.
     *
     * The previous version wrapped the footage in a mock browser window —
     * traffic-light dots, a URL pill, a rounded card with its own shadow —
     * inside a max-width container, with a caption underneath. All of that
     * read as a media box pasted onto the page rather than as part of it.
     *
     * What replaces it: no frame, no border, no chrome, no controls, no
     * caption, no overlay copy. The footage bleeds to both edges and is
     * feathered into the canvas top and bottom with a mask, so there is no
     * boundary anywhere for the eye to catch on. It behaves like a still that
     * happens to move.
     *
     * `controls` is deliberately absent: a control bar is the single loudest
     * "this is an embedded player" signal, and a decorative reel has nothing
     * for a user to control. It is muted, looping and inert — which is also
     * why it carries aria-hidden and is removed from the tab order.
     * ─────────────────────────────────────────────────────────────────────
     *
     * PLAYBACK IS DRIVEN BY IntersectionObserver, NOT BY `autoplay`.
     *
     * A bare `autoplay` starts the video on page load, off-screen, burning
     * bandwidth for a visitor who may never scroll to it. The observer starts
     * it once the band is genuinely in view and pauses it on the way out.
     *
     * `muted` and `playsinline` are load-bearing, not stylistic: browsers
     * block programmatic `play()` on a video with audio, and iOS Safari takes
     * an unmuted video fullscreen instead of playing it in place.
     *
     * With no asset present the section renders NOTHING — not a placeholder,
     * not a caption. Apologetic copy sitting inside the media box is exactly
     * what is being removed here; a missing reel should simply close the gap
     * between the hero and the next section.
     */
    /*
     * ─────────────────────────────────────────────────────────────────────
     * SOURCE AND VISIBILITY BOTH COME FROM SETTINGS
     *
     * The path was hardcoded to `media/mada-product-tour.mp4`. It is now
     * resolved through `Setting::videoSource()`, which prefers an external
     * `video_url` and falls back to the uploaded `previews_video`.
     *
     * Two independent reasons to render nothing, and they are NOT the same:
     *   · the admin switched the section off  → `is_video_section_active`
     *   · no source is configured at all      → nothing to play
     *
     * Either one short-circuits, so the band closes the gap between the hero
     * and the next section rather than leaving an empty frame.
     * ─────────────────────────────────────────────────────────────────────
     */
    $active = \App\Models\Setting::isVideoSectionActive();
    $src ??= \App\Models\Setting::videoSource();
    $hasVideo = $active && filled($src);

    $videoTitle = $settings['video_title'] ?? null;
    $videoDescription = $settings['video_description'] ?? null;

    /*
     * The poster is emitted ONLY when the file is really there. A `poster`
     * pointing at a 404 is not fatal — the browser falls back to the first
     * decoded frame — but it costs a failed request on every page load and
     * shows a blank box until the video has buffered enough to decode.
     */
    $posterExists = $poster !== null || file_exists(public_path('media/mada-product-tour.jpg'));
    $poster ??= asset('media/mada-product-tour.jpg');
@endphp

@if ($hasVideo)
    {{--
        `-mt-*` pulls the band up into the hero's trailing space so the two
        share one continuous ground, and the section paints the canvas colour
        itself so the feathered video edges dissolve into it rather than into
        whatever happens to sit behind.
    --}}
    <section class="relative -mt-4 overflow-hidden bg-mist-50 sm:-mt-8" data-scroll-video>
        {{--
            Optional heading, rendered only when the admin has filled it in.
            The reel works as a silent band with no copy at all, so an empty
            title must produce no markup rather than an empty heading element.
        --}}
        @if (filled($videoTitle) || filled($videoDescription))
            <div class="mx-auto mb-10 max-w-3xl px-4 text-center sm:px-6">
                @if (filled($videoTitle))
                    <h2 class="font-display text-3xl font-extrabold tracking-tight text-ink-900 sm:text-4xl">{{ $videoTitle }}</h2>
                @endif
                @if (filled($videoDescription))
                    <p class="mx-auto mt-4 max-w-2xl text-lg leading-relaxed font-medium text-mist-500">{{ $videoDescription }}</p>
                @endif
            </div>
        @endif

        {{--
            The feather. A mask is used rather than stacked gradient overlays
            because an overlay has to match the canvas colour exactly to be
            invisible — and would stop matching the moment the canvas token
            changes. A mask removes the pixels instead, so it stays correct
            against any ground.

            `-webkit-mask-image` is duplicated for Safari, which still needs
            the prefix here.
        --}}
        <div
            class="mx-auto w-full max-w-[1600px]"
            style="
                -webkit-mask-image: linear-gradient(to bottom, transparent 0, #000 12%, #000 88%, transparent 100%);
                mask-image: linear-gradient(to bottom, transparent 0, #000 12%, #000 88%, transparent 100%);
            "
        >
            <video
                data-scroll-video-el
                class="block h-auto w-full object-cover"
                muted
                playsinline
                loop
                preload="metadata"
                @if ($posterExists) poster="{{ $poster }}" @endif
                aria-hidden="true"
                tabindex="-1"
            >
                <source src="{{ $src }}" type="video/mp4">
            </video>
        </div>
    </section>

    @once
        {{--
            Inline rather than pushed to a stack: this layout defines no
            `@stack('scripts')`, so an `@push` here would compile to nothing
            and the reel would never play.
        --}}
        <script>
            (function () {
                function initScrollVideos() {
                    var bands = document.querySelectorAll('[data-scroll-video]');

                    if (! bands.length || typeof IntersectionObserver === 'undefined') {
                        return;
                    }

                    // A playing video either moves or it does not, so the OS
                    // motion preference is honoured by never observing at all.
                    // The poster frame remains, which is the whole point of
                    // setting one — the band still reads as product footage.
                    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                        return;
                    }

                    var observer = new IntersectionObserver(function (entries) {
                        entries.forEach(function (entry) {
                            var video = entry.target.querySelector('[data-scroll-video-el]');

                            if (! video) {
                                return;
                            }

                            if (entry.isIntersecting) {
                                // play() rejects when the tab is backgrounded
                                // or the browser declines autoplay. Left
                                // unhandled that logs a console error for a
                                // purely decorative element.
                                var played = video.play();

                                if (played !== undefined && typeof played.catch === 'function') {
                                    played.catch(function () {});
                                }
                            } else if (! video.paused) {
                                video.pause();
                            }
                        });
                    }, { threshold: 0.25 });

                    bands.forEach(function (band) {
                        // Guard against double-observing after a wire:navigate
                        // swap re-runs this initialiser.
                        if (band.dataset.scrollVideoBound === '1') {
                            return;
                        }

                        band.dataset.scrollVideoBound = '1';
                        observer.observe(band);
                    });
                }

                initScrollVideos();
                document.addEventListener('livewire:navigated', initScrollVideos);
            })();
        </script>
    @endonce
@endif
