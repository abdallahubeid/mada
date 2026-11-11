@props([
    'set' => 'section',
])

@php
    /*
     * The scattered circles from the Andromeda template, one preset per place
     * they appear there. Each entry is [position classes, diameter in px,
     * filled]. Filled circles take the accent; unfilled ones the soft peach.
     *
     * Positions use logical `start`/`end`, so the arrangement mirrors in RTL
     * along with the rest of the layout instead of sitting on the wrong side.
     */
    $presets = [
        'banner' => [
            ['start-[10%] top-12', 32, false],
            ['start-[2.5%] top-[29%]', 85, true],
            ['bottom-[48%] start-[22%]', 20, true],
            ['bottom-[37%] start-[15%]', 47, false],
            ['bottom-[13%] start-[6%]', 62, false],
            ['end-[12%] top-[15%]', 20, true],
            ['end-[2%] top-[30%]', 73, false],
            ['end-[19%] top-[48%]', 37, false],
            ['end-[33%] top-[54%]', 20, true],
            ['bottom-[20%] end-[3%]', 65, true],
        ],
        'single' => [
            ['start-[15%] top-[18%]', 32, false],
            ['bottom-[27%] start-[4%]', 73, true],
            ['bottom-[27%] start-[39.5%]', 20, true],
            ['bottom-[24%] start-[22%]', 47, false],
            ['start-[31%] top-[10%]', 62, false],
            ['end-[27%] top-[15%]', 20, false],
            ['bottom-[17%] end-[3%]', 73, false],
            ['bottom-[50%] end-[38%]', 20, false],
            ['end-[13%] top-[30%]', 20, true],
            ['bottom-[29%] end-[20%]', 65, true],
            ['bottom-[12%] end-[35%]', 37, false],
        ],
        'section' => [
            ['start-[10%] top-12', 32, false],
            ['start-[3%] top-[30%]', 85, true],
            ['bottom-[52%] start-[22%]', 20, true],
            ['bottom-[35%] start-[15%]', 47, false],
            ['bottom-[6%] start-[6%]', 62, false],
            ['end-[12%] top-[12%]', 20, true],
            ['end-[2%] top-[30%]', 73, false],
            ['end-[19%] top-[50%]', 37, false],
            ['bottom-[18%] end-[5%]', 65, true],
        ],
        'cta' => [
            ['start-[10%] top-12', 32, false],
            ['bottom-[13%] start-[3%]', 85, true],
            ['bottom-[35%] start-[15%]', 47, false],
            ['end-[12%] top-[12%]', 20, true],
            ['bottom-[30%] end-[2%]', 73, false],
            ['bottom-[16%] end-[19%]', 37, false],
        ],
        'figure' => [
            ['start-4 top-4 -z-10', 85, true],
            ['end-10 top-20 -z-10', 37, false],
            ['end-12 top-1/2 -z-10', 24, true],
            ['bottom-6 end-6 -z-10', 85, true],
            ['start-12 top-1/2 -z-10', 20, true],
            ['bottom-12 start-8 -z-10', 47, false],
        ],
    ];

    $circles = $presets[$set] ?? $presets['section'];

    /*
     * On a phone the section panels are narrow and tall, so circles land on
     * top of the copy. Below `md` only the small soft (peach) ones stay: the
     * filled orange dots are high-contrast and read as stray marks on the
     * text. The circles around figures sit behind a card and always show.
     */
    $phoneSafe = static fn (int $size, bool $filled): bool => $set === 'figure' || ($size <= 32 && ! $filled);
@endphp

@foreach ($circles as [$position, $size, $filled])
    <span
        @class(['site-circle', 'site-circle--soft' => ! $filled, 'max-md:hidden' => ! $phoneSafe($size, $filled), $position])
        style="width: {{ $size }}px; height: {{ $size }}px;"
        aria-hidden="true"
    ></span>
@endforeach
