@props([
    'value' => 0,
    'prefix' => '',
    'suffix' => '',
    'decimals' => 0,
    'duration' => 1800,
    'separator' => true,
])

{{--
    Animated number ticker. Counts from 0 → target when the element enters the
    viewport. Alpine.data('madaStatCounter') is registered once in the marketing
    layout. Supports prefixes/suffixes (+, %, K), decimals, and thousand separators.
--}}
<span
    {{ $attributes->class(['tabular-nums']) }}
    x-data="madaStatCounter({
        value: {{ json_encode((float) $value) }},
        prefix: {{ json_encode((string) $prefix) }},
        suffix: {{ json_encode((string) $suffix) }},
        decimals: {{ (int) $decimals }},
        duration: {{ (int) $duration }},
        separator: {{ $separator ? 'true' : 'false' }},
    })"
    x-text="display"
>{{ $prefix }}{{ number_format((float) $value, (int) $decimals, '.', $separator ? ',' : '') }}{{ $suffix }}</span>
