{{--
    Bidi isolation for latin-script values (dates, times, IDs, emails, money)
    inside the Arabic RTL UI.

    Put the LTR direction on THIS span, never on the surrounding <td>. A
    `dir="ltr"` table cell also flips what `text-start` resolves to — left
    instead of right — so the value drifts to the far edge while its <th> stays
    at the RTL start, which is exactly how table columns end up looking
    misaligned. Isolating the value keeps the cell RTL (so header and cell share
    one alignment) while digits stay in reading order.
--}}
<span dir="ltr" {{ $attributes->merge(['class' => 'inline-block tabular-nums']) }}>{{ $slot }}</span>
