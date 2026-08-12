{{--
    Reply preview bar — sits between the thread and the composer controls.

    ── CONTRACT ────────────────────────────────────────────────────────────
    Reads `reply` from the enclosing `veyraMessenger` scope: either null, or
    `{ id, author, excerpt }`. `startReply()` sets it, `cancelReply()` clears
    it, and `send()` posts `reply.id` as `parent_id`.
    ────────────────────────────────────────────────────────────────────────

    Rendered unconditionally and hidden by `x-show`, not `x-if`: the bar has to
    appear the instant "رد" is chosen, with no server round trip, and an
    element that only exists in the markup once a reply is in progress cannot
    animate in — it has to be constructed first.

    The accent rule is on the inline-START edge (`border-s-2`), which is the
    right edge in RTL — the same side the quote block inside a bubble uses, so
    the composer bar and the sent message read as the same object.
--}}
<div
    x-show="reply"
    x-cloak
    x-transition.opacity.duration.150ms
    {{-- `border-b`, not `border-t`: the composer's own top border already
         separates it from the thread, and a second rule at the same edge
         renders as a visible double line. --}}
    class="flex items-start gap-2 border-b border-mist-100 bg-emerald-400/[0.07] px-3 py-2 dark:border-ink-700 dark:bg-emerald-400/10"
    data-testid="messenger-reply-bar"
>
    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-700 rtl:-scale-x-100 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
    </svg>

    <div class="min-w-0 flex-1 border-s-2 border-emerald-400 ps-2">
        <p class="text-[11px] font-semibold text-emerald-700 dark:text-emerald-400">
            الرد على <span x-text="reply?.author"></span>
        </p>
        {{-- `truncate` rather than a clamp: a two-line preview pushes the
             composer down far enough on a phone to hide the thread. --}}
        <p class="truncate text-xs text-mist-500 dark:text-mist-400" x-text="reply?.excerpt"></p>
    </div>

    <button
        type="button"
        @click="cancelReply()"
        class="shrink-0 rounded-lg p-1 text-mist-500 transition duration-200 hover:bg-mist-100 hover:text-danger-solid focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50 dark:text-mist-400 dark:hover:bg-ink-700"
        aria-label="إلغاء الرد"
        title="إلغاء الرد (Esc)"
        data-testid="messenger-reply-cancel"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
    </button>
</div>
