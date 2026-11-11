@props([
    'eyebrow' => 'موثوق من قبل مؤسسات رائدة',
    'brands' => null,
])

@php
    $brands ??= ['TechNova', 'Al-Manar', 'Global Corp', 'Saudi Vision', 'Emirates Lux', 'Nova Bank', 'Riyadh Tech'];
@endphp

{{--
    Template brand strip: a band between two hairlines, under the hero, with
    the names muted until hovered. The names scroll continuously; the second
    copy exists only to make the loop seamless, so it is hidden from
    assistive technology.
--}}
<section class="site-container-xl">
    <div class="border-y border-site-border py-6">
        @if (filled($eyebrow))
            <p class="text-center">{{ $eyebrow }}</p>
        @endif

        <div class="group relative mt-4 overflow-hidden [-webkit-mask-image:linear-gradient(to_right,transparent,black_12%,black_88%,transparent)] [mask-image:linear-gradient(to_right,transparent,black_12%,black_88%,transparent)]">
            <div dir="ltr" class="flex w-max animate-marquee group-hover:[animation-play-state:paused] motion-reduce:animate-none">
                @foreach (['a', 'b'] as $copy)
                    <ul @if ($copy === 'b') aria-hidden="true" @endif class="flex shrink-0 items-center gap-x-16 pe-16">
                        @foreach ($brands as $brand)
                            <li class="whitespace-nowrap py-3 text-xl font-semibold text-site-text transition duration-300 hover:text-site-primary-ink">{{ $brand }}</li>
                        @endforeach
                    </ul>
                @endforeach
            </div>
        </div>
    </div>
</section>
