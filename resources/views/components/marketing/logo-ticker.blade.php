@props([
    'eyebrow' => 'موثوق من قبل مؤسسات رائدة',
    'brands' => null,
])

@php
    $brands ??= ['TechNova', 'Al-Manar', 'Global Corp', 'Saudi Vision', 'Emirates Lux', 'Nova Bank', 'Riyadh Tech'];
@endphp

<section class="border-y border-mist-200 bg-mist-50 py-8 dark:border-ink-800">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <p class="text-center text-xs font-semibold uppercase tracking-wider text-mist-400">{{ $eyebrow }}</p>

        <div class="group relative mt-6 overflow-hidden [-webkit-mask-image:linear-gradient(to_right,transparent,black_12%,black_88%,transparent)] [mask-image:linear-gradient(to_right,transparent,black_12%,black_88%,transparent)]">
            <div
                dir="ltr"
                class="flex w-max animate-marquee group-hover:[animation-play-state:paused] motion-reduce:animate-none"
            >
                @foreach (['a', 'b'] as $copy)
                    <ul @if ($copy === 'b') aria-hidden="true" @endif class="flex shrink-0 items-center gap-x-12 pe-12">
                        @foreach ($brands as $brand)
                            <li class="whitespace-nowrap font-display text-lg font-bold text-mist-400 opacity-60 transition duration-200 hover:opacity-100 dark:text-mist-500">{{ $brand }}</li>
                        @endforeach
                    </ul>
                @endforeach
            </div>
        </div>
    </div>
</section>
