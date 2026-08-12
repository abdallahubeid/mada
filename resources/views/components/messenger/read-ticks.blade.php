@props([
    /** JS expression for the message id — literal on server bubbles, `m.id` inside x-for. */
    'id',
])

{{--
    Read receipt for one of my own messages.

    ── TWO STATES, NOT THREE ───────────────────────────────────────────────
    One tick = sent. Two emerald ticks = read by everyone else in the thread.

    There is deliberately no grey double tick. In every other messenger that
    means "delivered to the device", and Veyra has no delivery signal to base
    it on — there is no per-recipient ack, only a read watermark. Rendering the
    middle state would be inventing a fact, so the jump is straight from sent
    to read.
    ────────────────────────────────────────────────────────────────────────

    Driven entirely by `isRead()` on the messenger scope, which returns false
    whenever `readUpTo` is null — and the server sends null whenever EITHER
    side has switched read receipts off. So the privacy rule needs no
    representation here: the component simply never gets a truthy answer.

    `x-cloak` on both: without it the un-evaluated markup would flash both
    icons for the frame before Alpine initialises.
--}}
{{-- No id attribute on the wrapper: `$id` is a JS expression, so on a live
     bubble it would render the literal string "m.id". The read state is read
     from Alpine, and the test hook is on the read icon itself. --}}
<span class="inline-flex items-center">
    {{-- Sent --}}
    <svg
        x-show="! isRead({{ $id }})"
        x-cloak
        xmlns="http://www.w3.org/2000/svg"
        class="h-3 w-3"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2.5"
        aria-label="أُرسلت"
        role="img"
    >
        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
    </svg>

    {{-- Read. Emerald on both bubble colours: the recipient bubble is neutral
         and the sender bubble is emerald-400, and emerald-900 keeps contrast
         on the latter without going grey on the former. --}}
    <svg
        x-show="isRead({{ $id }})"
        x-cloak
        xmlns="http://www.w3.org/2000/svg"
        class="h-3.5 w-3.5 text-emerald-900 dark:text-emerald-900"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2.5"
        aria-label="تمت القراءة"
        role="img"
        data-testid="messenger-read-tick"
    >
        <path stroke-linecap="round" stroke-linejoin="round" d="m1.5 12.75 4 4 7.5-10.5" />
        <path stroke-linecap="round" stroke-linejoin="round" d="m10.5 15.75 1.75 1.75L20 6.75" />
    </svg>
</span>
