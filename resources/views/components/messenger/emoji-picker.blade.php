@props([
    /*
     * Which edge the popover pins to. `start` is the correct default for a
     * control sitting at the inline-start of its row: in RTL that pins the
     * popover's RIGHT edge to the button and grows it leftwards, which keeps
     * it on screen. Pinning `end-0` there would grow it off the right of a
     * phone viewport — logical properties reverse, but overflow does not.
     */
    'align' => 'start',
    /* The event the picker dispatches. Bubbles to this component's root, so
       the consumer listens with `x-on:emoji-picked` on the tag itself. */
    'event' => 'emoji-picked',
    'label' => 'رموز تعبيرية',
    'testid' => null,
])

@php
    $categories = \App\Domain\Messaging\Support\EmojiCatalog::categories();
@endphp

<x-messenger.popover-clamp />

{{--
    Categorised emoji picker.

    Self-contained: it owns its open state, its tab state and its recents, and
    reports a choice by dispatching `$event` upwards. It deliberately does NOT
    reach into the composer — a picker that knows about a textarea can only
    ever serve one textarea.

    Usage:
        <x-messenger.emoji-picker x-on:emoji-picked="draft += $event.detail" />
--}}
<div
    {{ $attributes->merge(['class' => 'relative shrink-0']) }}
    x-data="madaEmojiPicker(@js(['categories' => $categories, 'event' => $event]))"
>
    <button
        type="button"
        x-ref="trigger"
        @click="toggle()"
        :aria-expanded="open ? 'true' : 'false'"
        aria-haspopup="dialog"
        class="rounded-xl p-2.5 text-mist-500 transition duration-200 hover:bg-mist-100 hover:text-ink-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/50 dark:text-mist-400 dark:hover:bg-ink-700 dark:hover:text-mist-100"
        :class="open && 'bg-mist-100 text-ink-700 dark:bg-ink-700 dark:text-mist-100'"
        aria-label="{{ $label }}"
        title="{{ $label }}"
        @if ($testid) data-testid="{{ $testid }}" @endif
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 0 1-6.364 0M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z" /></svg>
    </button>

    {{--
        z-40 clears the composer row and the per-message menus (z-20/z-30).
        `max-w-[calc(100vw-2rem)]` is the mobile guard: the popover is 19rem,
        which is wider than the gutter left beside the button on a 375px
        screen once the pane padding is counted.
    --}}
    <div
        x-show="open"
        x-cloak
        x-transition.origin.bottom
        {{-- Anchoring picks the direction; this corrects the last few pixels
             when even the correct direction does not fit. --}}
        x-effect="open && $nextTick(() => window.madaClampX($el))"
        @click.outside="open = false"
        {{--
            Closes when focus lands anywhere outside the picker — which is how
            "shift focus back to the message input" ends the session. Scoped to
            `$root` rather than this popover so clicking a category tab or an
            emoji (both inside the component, both focusable) does not count as
            leaving. Picking an emoji deliberately does NOT move focus, so a
            run of emoji can be inserted without reopening between each.
        --}}
        @focusin.window="if (open && ! $root.contains($event.target)) open = false"
        @keydown.escape.stop="open = false; $refs.trigger?.focus()"
        role="dialog"
        aria-label="{{ $label }}"
        @class([
            'absolute bottom-full z-40 mb-3 w-[19rem] max-w-[calc(100vw-2rem)] overflow-hidden rounded-2xl border border-mist-200 bg-white shadow-2xl dark:border-ink-600 dark:bg-ink-900',
            'start-0' => $align === 'start',
            'end-0' => $align === 'end',
        ])
    >
        {{-- Tab strip. `role=tab` + `aria-selected` rather than styled divs so
             a screen reader announces which group is showing. --}}
        <div role="tablist" aria-label="فئات الرموز" class="flex items-center gap-0.5 border-b border-mist-100 px-1.5 py-1.5 dark:border-ink-700">
            <template x-for="category in categories" :key="category.key">
                <button
                    type="button"
                    role="tab"
                    :aria-selected="active === category.key ? 'true' : 'false'"
                    :title="category.label"
                    :aria-label="category.label"
                    @click="active = category.key"
                    class="flex-1 rounded-lg py-1.5 text-base leading-none transition duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/50"
                    :class="active === category.key
                        ? 'bg-brand-500/15 ring-1 ring-inset ring-brand-500/40'
                        : 'opacity-60 hover:bg-mist-100 hover:opacity-100 dark:hover:bg-ink-800'"
                    x-text="category.icon"
                ></button>
            </template>
        </div>

        {{-- Recents. Hidden until there is something in it rather than shown
             empty, so the picker does not open onto a blank strip. --}}
        <div x-show="recent.length > 0" x-cloak class="border-b border-mist-100 px-2.5 pb-2 pt-2 dark:border-ink-700">
            <p class="mb-1 text-xs font-semibold text-mist-500 dark:text-mist-400">المستخدمة مؤخراً</p>
            <div class="grid grid-cols-8 gap-0.5">
                <template x-for="emoji in recent" :key="'recent-' + emoji">
                    <button
                        type="button"
                        @click="pick(emoji)"
                        class="rounded-lg py-1 text-lg leading-none transition duration-150 hover:scale-125 hover:bg-mist-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/50 dark:hover:bg-ink-800"
                        x-text="emoji"
                    ></button>
                </template>
            </div>
        </div>

        <div class="max-h-52 overflow-y-auto p-2.5">
            <p class="mb-1 text-xs font-semibold text-mist-500 dark:text-mist-400" x-text="activeLabel"></p>
            <div class="grid grid-cols-8 gap-0.5">
                <template x-for="emoji in emojis" :key="active + '-' + emoji">
                    <button
                        type="button"
                        @click="pick(emoji)"
                        :aria-label="emoji"
                        class="rounded-lg py-1 text-lg leading-none transition duration-150 hover:scale-125 hover:bg-mist-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/50 dark:hover:bg-ink-800"
                        x-text="emoji"
                    ></button>
                </template>
            </div>
        </div>
    </div>
</div>

{{-- @once: several pickers may render on one page, and the factory only needs
     defining once. --}}
@once
    @push('scripts')
        <script>
            function madaEmojiPicker(config) {
                return {
                    open: false,
                    categories: config.categories,
                    active: config.categories[0]?.key ?? '',
                    recent: [],

                    /*
                     * Recents live in localStorage, not on the user row.
                     * Which emoji someone reaches for is a keyboard
                     * preference, not company data, and persisting it
                     * server-side would mean a write on every emoji tap.
                     */
                    storageKey: 'mada.messenger.recent-emoji',

                    init() {
                        this.recent = this.readRecent();
                    },

                    get emojis() {
                        return this.categories.find((c) => c.key === this.active)?.emoji ?? [];
                    },

                    get activeLabel() {
                        return this.categories.find((c) => c.key === this.active)?.label ?? '';
                    },

                    toggle() {
                        this.open = ! this.open;

                        // Re-read on open: another tab may have added to the
                        // list since this page was rendered.
                        if (this.open) {
                            this.recent = this.readRecent();
                        }
                    },

                    /*
                     * Stays open. Picking an emoji is rarely a single act —
                     * people fire off two or three — and a picker that closed
                     * on every choice made the common case four clicks instead
                     * of two. It is dismissed by clicking away, by Esc, or by
                     * putting the caret back in the composer.
                     *
                     * The consumer must not steal focus when handling this
                     * event, or the focusin listener above will read its own
                     * insertion as the user leaving.
                     */
                    pick(emoji) {
                        this.remember(emoji);
                        this.$dispatch(config.event, emoji);
                    },

                    readRecent() {
                        try {
                            const raw = window.localStorage.getItem(this.storageKey);
                            const parsed = raw ? JSON.parse(raw) : [];

                            return Array.isArray(parsed) ? parsed.slice(0, 16) : [];
                        } catch (error) {
                            // Private browsing, or a value someone hand-edited.
                            return [];
                        }
                    },

                    remember(emoji) {
                        this.recent = [emoji, ...this.recent.filter((e) => e !== emoji)].slice(0, 16);

                        try {
                            window.localStorage.setItem(this.storageKey, JSON.stringify(this.recent));
                        } catch (error) {
                            // Storage disabled or full — the picker still works,
                            // it just forgets. Not worth interrupting anyone over.
                        }
                    },
                };
            }
        </script>
    @endpush
@endonce
