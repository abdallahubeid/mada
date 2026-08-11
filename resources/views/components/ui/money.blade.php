@props([
    'amount' => 0,
    'currency' => null,
    'signed' => false,
])

{{--
    Renders a monetary value stored in MINOR UNITS (halalas/cents) — ADR-20.

    Wraps <x-ui.ltr> so the DESIGN_SYSTEM.md §8 rule is structural rather than
    something each of the module's ~40 money cells has to remember: there is no
    way to render an amount here and end up with dir="ltr" on the <td>.

    `signed` forces an explicit + on positive values, for columns where the
    direction of the effect matters (line items) rather than just the magnitude.

    Assumes a 2-decimal currency, which covers SAR/AED/USD. A 3-decimal currency
    (KWD, BHD, OMR) would need the exponent to come from the currency itself —
    out of scope while each tenant runs a single configured currency.
--}}
@php
    $minor = (int) $amount;
    $formatted = number_format(abs($minor) / 100, 2);

    $prefix = match (true) {
        $minor < 0 => '-',
        $signed && $minor > 0 => '+',
        default => '',
    };
@endphp

<x-ui.ltr {{ $attributes }}>{{ $prefix }}{{ $formatted }}@if ($currency)<span class="ms-1 text-xs text-mist-500 dark:text-mist-400">{{ $currency }}</span>@endif</x-ui.ltr>
