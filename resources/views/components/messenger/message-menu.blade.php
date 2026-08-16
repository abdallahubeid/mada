@props([
    /*
     * `id` and `mine` are JAVASCRIPT EXPRESSIONS, not values — see the note
     * below for why, and for what to pass from each of the two call sites.
     */
    'id',
    'mine' => 'false',
    'palette' => [],
])

<x-messenger.popover-clamp />

{{--
    Per-message hover cluster: quick reactions + the overflow menu.

    ── THE PROPS ARE JS EXPRESSIONS ────────────────────────────────────────
    The same cluster has to sit on server-rendered bubbles and on bubbles
    appended live inside an `x-for`, where the message is a JS object and no
    PHP value exists. Taking expressions lets one component serve both:

        server:  id="{{ $message->id }}"   mine="{{ $mine ? 'true' : 'false' }}"
        live:    id="m.id"                 mine="m.sender_id === config.userId"

    A component taking PHP values would have needed a hand-written JS twin,
    and the two would have drifted the first time a menu item changed.
    ────────────────────────────────────────────────────────────────────────

    ── CONTRACT ────────────────────────────────────────────────────────────
    The PARENT ROW owns the open state, declaring:

        x-data="{ quick: false, menu: false, up: true }"

    It lives on the row rather than in here so the bubble's `@contextmenu` can
    open the menu too — a right-click handler on the bubble cannot reach a
    sibling's scope, and `$dispatch` bubbles upward, never sideways.

    The row must also be inside the `madaMessenger` scope, which supplies
    placeMenu / react / pin / startReply / openForward / copyMessage /
    deleteMessage.
    ────────────────────────────────────────────────────────────────────────
--}}
<div
    class="flex shrink-0 items-center gap-0.5 opacity-0 transition duration-200 group-hover:opacity-100 focus-within:opacity-100"
    :class="(quick || menu) && 'opacity-100'"
>
    {{-- Quick reactions: the six-emoji whitelist, one tap. Kept separate from
         the overflow menu because reacting is the most frequent action on a
         message and burying it two clicks deep would be a regression. --}}
    <div class="relative">
        <button
            type="button"
            @click="menu = false; up = placeMenu($event.currentTarget); quick = ! quick"
            :aria-expanded="quick ? 'true' : 'false'"
            aria-haspopup="true"
            class="rounded-full p-1.5 text-mist-500 transition duration-200 hover:bg-mist-100 hover:text-ink-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/50 dark:text-mist-400 dark:hover:bg-ink-700 dark:hover:text-mist-100"
            aria-label="تفاعل"
            title="تفاعل"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 0 1-6.364 0M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z" /></svg>
        </button>

        <div
            x-show="quick"
            x-cloak
            x-transition
            x-effect="quick && $nextTick(() => window.madaClampX($el))"
            @click.outside="quick = false"
            @keydown.escape.stop="quick = false"
            role="menu"
            aria-label="تفاعل سريع"
            class="absolute z-30 flex gap-0.5 rounded-full border border-mist-200 bg-white p-1 shadow-lg dark:border-ink-600 dark:bg-ink-900"
            :class="up ? 'bottom-full mb-1' : 'top-full mt-1'"
        >
            @foreach ($palette as $emoji)
                <button
                    type="button"
                    role="menuitem"
                    @click="react({{ $id }}, @js($emoji)); quick = false"
                    class="rounded-md px-1.5 py-0.5 text-base transition duration-150 hover:scale-125 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/50"
                    aria-label="{{ $emoji }}"
                >{{ $emoji }}</button>
            @endforeach
        </div>
    </div>

    {{-- Overflow menu --}}
    <div class="relative">
        <button
            type="button"
            @click="quick = false; up = placeMenu($event.currentTarget); menu = ! menu"
            :aria-expanded="menu ? 'true' : 'false'"
            aria-haspopup="menu"
            class="rounded-full p-1.5 text-mist-500 transition duration-200 hover:bg-mist-100 hover:text-ink-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/50 dark:text-mist-400 dark:hover:bg-ink-700 dark:hover:text-mist-100"
            aria-label="خيارات الرسالة"
            title="خيارات الرسالة"
            data-testid="messenger-message-menu"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z" /></svg>
        </button>

        {{--
            Side is chosen so the menu opens OVER THE BUBBLE, which is the one
            direction guaranteed to have room: the cluster always sits on the
            bubble's inner edge, and the bubble runs up to 75% of the pane.

            For my own messages the bubble is on the inline-end side of the
            cluster, so the menu pins `start-0` and grows toward it; for a
            colleague's it is the mirror. In RTL both resolve to the physical
            opposite, which is exactly the point of using logical properties.
        --}}
        <div
            x-show="menu"
            x-cloak
            x-transition
            {{-- Vertical side is chosen by `placeMenu`; this handles the
                 horizontal, which on a phone can still run off the pane even
                 when the anchoring side is right. --}}
            x-effect="menu && $nextTick(() => window.madaClampX($el))"
            @click.outside="menu = false"
            @keydown.escape.stop="menu = false"
            role="menu"
            aria-orientation="vertical"
            aria-label="خيارات الرسالة"
            class="absolute z-30 w-52 overflow-hidden rounded-xl border border-mist-200 bg-white py-1 shadow-2xl dark:border-ink-600 dark:bg-ink-900"
            :class="[
                up ? 'bottom-full mb-1' : 'top-full mt-1',
                ({{ $mine }}) ? 'start-0' : 'end-0',
            ]"
        >
            <button
                type="button"
                role="menuitem"
                @click="startReply({{ $id }}); menu = false"
                class="flex w-full items-center gap-2.5 px-3.5 py-2 text-start text-sm text-ink-700 transition duration-150 hover:bg-mist-50 focus-visible:bg-mist-50 focus-visible:outline-none dark:text-mist-200 dark:hover:bg-ink-800 dark:focus-visible:bg-ink-800"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-mist-500 rtl:-scale-x-100 dark:text-mist-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" /></svg>
                رد على الرسالة
            </button>

            <button
                type="button"
                role="menuitem"
                @click="openForward({{ $id }}); menu = false"
                class="flex w-full items-center gap-2.5 px-3.5 py-2 text-start text-sm text-ink-700 transition duration-150 hover:bg-mist-50 focus-visible:bg-mist-50 focus-visible:outline-none dark:text-mist-200 dark:hover:bg-ink-800 dark:focus-visible:bg-ink-800"
                data-testid="messenger-forward"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-mist-500 rtl:-scale-x-100 dark:text-mist-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m15 15 6-6m0 0-6-6m6 6H9a6 6 0 0 0 0 12h3" /></svg>
                إعادة توجيه
            </button>

            <button
                type="button"
                role="menuitem"
                @click="copyMessage({{ $id }}); menu = false"
                class="flex w-full items-center gap-2.5 px-3.5 py-2 text-start text-sm text-ink-700 transition duration-150 hover:bg-mist-50 focus-visible:bg-mist-50 focus-visible:outline-none dark:text-mist-200 dark:hover:bg-ink-800 dark:focus-visible:bg-ink-800"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-mist-500 dark:text-mist-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" /></svg>
                نسخ النص
            </button>

            {{-- Pin is a toggle on one endpoint — "pin this instead" is what the
                 gesture means, so there is no separate unpin item here. The
                 pinned bar carries its own dismiss. --}}
            <button
                type="button"
                role="menuitem"
                @click="pin({{ $id }}); menu = false"
                class="flex w-full items-center gap-2.5 px-3.5 py-2 text-start text-sm text-ink-700 transition duration-150 hover:bg-mist-50 focus-visible:bg-mist-50 focus-visible:outline-none dark:text-mist-200 dark:hover:bg-ink-800 dark:focus-visible:bg-ink-800"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-mist-500 dark:text-mist-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2Z" /></svg>
                تثبيت الرسالة
            </button>

            {{-- Author-only, and hidden rather than disabled: the action does
                 not exist for anyone else, so offering a greyed control would
                 imply a permission that could be granted. The route enforces
                 it regardless — this is presentation, not the check. --}}
            <template x-if="{{ $mine }}">
                <button
                    type="button"
                    role="menuitem"
                    @click="deleteMessage({{ $id }}); menu = false"
                    {{-- A rose pair, not the `text-danger-solid` the sidebar's
                         delete item uses: danger-solid is a single Figma tone
                         (#fc7c78) with no light-mode override, and it measures
                         2.53:1 on white — below AA. The ~40 other
                         danger-solid call sites have the same problem and want
                         a token-level fix, not a patch here.

                         `rose-700` rather than the `rose-600` used in
                         status-badge, because this item has a red hover tint
                         sitting underneath it: rose-600 measures 4.53:1 at
                         rest but 3.91:1 once `bg-rose-500/10` is composited in
                         — i.e. it drops below AA exactly when the pointer is
                         on a destructive control. Measured, not assumed. --}}
                    class="flex w-full items-center gap-2.5 border-t border-mist-100 px-3.5 py-2 text-start text-sm text-rose-700 transition duration-150 hover:bg-rose-500/10 focus-visible:bg-rose-500/10 focus-visible:outline-none dark:border-ink-700 dark:text-rose-400"
                    data-testid="messenger-delete-message"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                    حذف الرسالة
                </button>
            </template>
        </div>
    </div>
</div>
