{{--
    Shared helper: nudge an open popover back inside the viewport.

    ── WHY POSITION ALONE IS NOT ENOUGH ────────────────────────────────────
    The picker and the message menu are anchored with logical properties so
    they grow away from the edge their trigger sits on. That is correct, and
    it is what keeps them on screen at desktop widths — but it only controls
    which DIRECTION they grow, not whether there is room. On a 375px viewport
    a 208px menu hanging off a bubble that starts near the pane edge still
    runs 13px past it, and a `max-w-*` cap cannot fix that: the element is
    narrower than the viewport already, it is simply in the wrong place.

    So the last few pixels are corrected at runtime, once, on open.
    ────────────────────────────────────────────────────────────────────────

    Uses the `translate` PROPERTY, not `transform`: Alpine's `x-transition`
    animates `transform` (scale 0.95 → 1) and would overwrite a transform set
    here mid-flight. `translate` is a separate property and composes with it.

    Measures via `offsetParent` + `offsetLeft`/`offsetWidth` rather than
    `getBoundingClientRect()` for the same reason — those are layout values
    and are not affected by the in-progress scale, so the clamp lands on the
    final position instead of the 95% one.
--}}
@once
    @push('scripts')
        <script>
            window.veyraClampX = function (el) {
                if (! el) {
                    return;
                }

                // Clear first: the same element is re-clamped every time it
                // opens, and a stale offset would compound.
                el.style.translate = '';

                const parent = el.offsetParent;

                if (! parent) {
                    return;
                }

                const margin = 8;
                const left = parent.getBoundingClientRect().left + el.offsetLeft;
                const right = left + el.offsetWidth;

                let shift = 0;

                if (left < margin) {
                    shift = margin - left;
                } else if (right > window.innerWidth - margin) {
                    shift = window.innerWidth - margin - right;
                }

                if (shift !== 0) {
                    el.style.translate = `${Math.round(shift)}px`;
                }
            };
        </script>
    @endpush
@endonce
