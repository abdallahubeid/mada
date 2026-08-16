@php
    /*
     * ─────────────────────────────────────────────────────────────────────────
     * TITLE FIELD WITH DECORATION MARKERS
     *
     * Landing headlines carry hand-drawn decorations that are encoded IN THE
     * STRING, not in a separate column:
     *
     *     **phrase**   ((phrase))   __phrase__
     *
     * `x-marketing.hero` and `x-marketing.section-heading` parse those out at
     * render time. That worked — and was invisible. The admin screen showed a
     * bare <input> containing literal asterisks, with nothing saying what they
     * were, so the two available outcomes were "retype the title and silently
     * lose the highlight" or "leave the field alone". Neither is editing.
     *
     * This partial is a drop-in replacement for that bare input. It keeps the
     * SAME `name`, so `LandingSettingController` is untouched and the value is
     * still one plain string in the key/value store — the markers are a text
     * convention, and turning them into structured columns would mean a
     * parallel read path for every heading on the site.
     *
     * ─────────────────────────────────────────────────────────────────────────
     * `**` DOES NOT MEAN THE SAME THING IN BOTH PLACES
     *
     * In the hero it paints the orange marker swash. In a section heading it
     * paints the blue double underline — the hero gets the loud device, the
     * eleven section headings get the quiet one, which is what stops the page
     * reading as the same effect stamped twelve times.
     *
     * So the preview is driven by `$context`, and it renders with the REAL
     * `.mada-*` classes from app.css rather than an approximation. An admin
     * comparing the preview to the live page has to see the same mark, or the
     * preview is worse than none.
     * ─────────────────────────────────────────────────────────────────────────
     *
     * Required: $key, $label
     * Optional: $context — 'hero' (all three markers) | 'section' (default, `**` only)
     */
    $context = $context ?? 'section';
    $isHero = $context === 'hero';

    /*
     * Mirrors the parse in `hero.blade.php`. Kept as a single alternation for
     * the same reason it is there: fragments stay in source order, which a
     * per-marker pass would scramble in RTL.
     */
    $markers = $isHero
        ? [
            ['open' => '**', 'close' => '**', 'class' => 'mada-marker', 'name' => 'تظليل', 'hint' => 'قلم برتقالي'],
            ['open' => '((', 'close' => '))', 'class' => 'mada-circle', 'name' => 'تطويق', 'hint' => 'دائرة فيروزية'],
            ['open' => '__', 'close' => '__', 'class' => 'mada-underline-double', 'name' => 'تسطير', 'hint' => 'خط أزرق مزدوج'],
        ]
        : [
            ['open' => '**', 'close' => '**', 'class' => 'mada-underline-double', 'name' => 'تسطير', 'hint' => 'خط أزرق مزدوج'],
        ];
@endphp

<div
    x-data="decoratedTitleField({
        value: @js($val($key)),
        context: @js($context),
    })"
    {{--
        Not decoration: `data-decorated-key` is the hook the test suite uses to
        assert that every marker-parsed setting is edited through THIS partial
        and not through a bare <input>. Adding a new section heading with a
        plain text field is the exact regression that made the markers
        uneditable in the first place, and it is invisible in review.
    --}}
    data-decorated-key="{{ $key }}"
    data-decorated-context="{{ $context }}"
    class="space-y-2"
>
    <div class="flex flex-wrap items-center justify-between gap-2">
        <label :for="fieldId" class="{{ $labelClass }} mb-0">{{ $label }}</label>

        {{--
            The toolbar wraps the CURRENT SELECTION. Buttons that only append a
            marker pair at the end would force the admin to cut and paste the
            phrase into it, which is the same manual string surgery this
            partial exists to remove.
        --}}
        <div class="flex flex-wrap items-center gap-1">
            @foreach ($markers as $marker)
                <button
                    type="button"
                    @click="wrap(@js($marker['open']), @js($marker['close']))"
                    title="{{ $marker['name'] }} — {{ $marker['hint'] }} ({{ $marker['open'] }}…{{ $marker['close'] }})"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-mist-200 bg-white px-2 py-1 text-xs font-medium text-ink-700 transition-colors duration-150 hover:border-brand-400 hover:text-brand-600"
                >
                    <span class="{{ $marker['class'] }}">{{ $marker['name'] }}</span>
                </button>
            @endforeach

            <button
                type="button"
                @click="stripAll()"
                x-show="hasMarkers"
                x-cloak
                title="إزالة كل الزخارف من هذا العنوان"
                class="rounded-lg border border-mist-200 bg-white px-2 py-1 text-xs font-medium text-mist-500 transition-colors duration-150 hover:border-critical-400 hover:text-critical-600"
            >إزالة الزخرفة</button>
        </div>
    </div>

    <input
        type="text"
        :id="fieldId"
        x-ref="input"
        name="{{ $key }}"
        x-model="value"
        dir="auto"
        class="{{ $inputClass }}"
    >

    {{--
        The preview is the point of the whole partial. It is rendered from the
        live field value on every keystroke, so an admin who deletes an
        asterisk sees the highlight disappear immediately instead of finding
        out on the public page.

        `aria-hidden` — it duplicates the input's own text for a screen reader,
        and the decorations are purely visual.
    --}}
    <div class="rounded-xl border border-dashed border-mist-200 bg-mist-50 px-3 py-3">
        <div class="mb-1.5 text-xs font-medium text-mist-500">المعاينة</div>
        <div
            aria-hidden="true"
            dir="auto"
            class="font-display text-xl font-extrabold leading-relaxed tracking-tight text-ink-900"
            x-html="preview"
        ></div>
    </div>

    <p class="text-xs leading-relaxed text-mist-500">
        @foreach ($markers as $marker)
            <code class="rounded bg-mist-100 px-1 py-0.5 font-mono text-ink-700">{{ $marker['open'] }}نص{{ $marker['close'] }}</code>
            <span>{{ $marker['hint'] }}</span>@if (! $loop->last)<span class="mx-1 text-mist-300">·</span>@endif
        @endforeach
        <span class="mx-1 text-mist-300">·</span>
        <span>عنوان بلا علامات يظهر بلا زخرفة.</span>
    </p>
</div>

@once
    @push('scripts')
        <script>
            /*
             * Registered on `alpine:init` so the component exists before Alpine
             * walks the DOM. Defining it inline in `x-data` instead would mean
             * re-parsing the same function body once per decorated field.
             */
            document.addEventListener('alpine:init', () => {
                Alpine.data('decoratedTitleField', ({ value, context }) => ({
                    value,
                    context,
                    fieldId: 'decorated-' + Math.random().toString(36).slice(2, 9),

                    /*
                     * Must stay in step with the alternation in
                     * `resources/views/components/marketing/hero.blade.php`.
                     * Non-greedy, so `**a** and **b**` yields two marks rather
                     * than one spanning mark.
                     */
                    get pattern() {
                        return /(\*\*.+?\*\*|\(\(.+?\)\)|__.+?__)/gu;
                    },

                    get hasMarkers() {
                        return this.pattern.test(this.value ?? '');
                    },

                    /*
                     * `**` is the orange marker in the hero and the blue double
                     * underline in a section heading — same syntax, different
                     * mark, because the two roles are deliberately distinct.
                     */
                    classFor(token) {
                        if (token.startsWith('((')) {
                            return 'mada-circle';
                        }

                        if (token.startsWith('__')) {
                            return 'mada-underline-double';
                        }

                        return this.context === 'hero' ? 'mada-marker' : 'mada-underline-double';
                    },

                    // Every delimiter is exactly two characters on each side,
                    // `((`/`))` included, so one slice covers all three.
                    strip(token) {
                        return token.slice(2, -2);
                    },

                    escape(text) {
                        const el = document.createElement('div');
                        el.textContent = text;

                        return el.innerHTML;
                    },

                    /*
                     * Built as an escaped string rather than with innerHTML on
                     * raw input: this value is admin-authored, but it is still
                     * user input being reflected back into the page, and the
                     * public renderer escapes every fragment too.
                     */
                    get preview() {
                        const raw = this.value ?? '';

                        if (raw.trim() === '') {
                            return '<span class="text-mist-400 font-sans text-sm font-normal">لا يوجد نص بعد.</span>';
                        }

                        return raw
                            .split(this.pattern)
                            .filter((part) => part !== '' && part !== undefined)
                            .map((part) => {
                                const isToken = /^(\*\*.+?\*\*|\(\(.+?\)\)|__.+?__)$/su.test(part);

                                if (! isToken) {
                                    return this.escape(part);
                                }

                                return '<span class="' + this.classFor(part) + '">'
                                    + this.escape(this.strip(part))
                                    + '</span>';
                            })
                            .join('');
                    },

                    /*
                     * Wrapping the selection, not appending at the end. With no
                     * selection this drops an empty marker pair and parks the
                     * caret inside it, so typing lands in the decorated slot.
                     */
                    wrap(open, close) {
                        const input = this.$refs.input;
                        const start = input.selectionStart ?? 0;
                        const end = input.selectionEnd ?? 0;
                        const text = this.value ?? '';
                        const selected = text.slice(start, end);

                        this.value = text.slice(0, start) + open + selected + close + text.slice(end);

                        // Restore focus and place the caret around the phrase.
                        // Deferred to $nextTick because x-model has not written
                        // the new value into the element yet.
                        this.$nextTick(() => {
                            input.focus();
                            input.setSelectionRange(start + open.length, start + open.length + selected.length);
                        });
                    },

                    stripAll() {
                        this.value = (this.value ?? '').replace(this.pattern, (token) => this.strip(token));
                    },
                }));
            });
        </script>
    @endpush
@endonce
