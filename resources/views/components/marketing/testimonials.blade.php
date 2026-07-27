@props([
    'testimonials' => null,
])

@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Testimonial>|\Illuminate\Database\Eloquent\Collection<int, \App\Models\Testimonial> $testimonials */
    $testimonials ??= collect();
@endphp

@if ($testimonials->isNotEmpty())
<section class="bg-white py-24 dark:bg-ink-900">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-marketing.section-heading
            :eyebrow="$settings['testimonials_badge_text'] ?? 'قصص نجاح'"
            :title="$settings['testimonials_title'] ?? ''"
            :subtitle="$settings['testimonials_sub_title'] ?? ''"
        />

        <div class="mt-16 grid gap-6 lg:grid-cols-3">
            @foreach ($testimonials as $testimonial)
                @php
                    $avatar = $testimonial->relationLoaded('images')
                        ? ($testimonial->images->firstWhere('collection', 'avatar') ?? $testimonial->images->firstWhere('collection', 'logo'))
                        : ($testimonial->image('avatar')->first() ?? $testimonial->image('logo')->first());
                    $initial = mb_substr($testimonial->client_name, 0, 1);
                    $position = collect([$testimonial->client_role, $testimonial->organization_name])->filter()->implode(' · ');
                    $rating = max(1, min(5, (int) ($testimonial->rate ?? 5)));
                @endphp
                <figure class="flex h-full flex-col rounded-2xl border border-mist-200 bg-ink-50/40 p-6 dark:border-ink-800 dark:bg-ink-800/60">
                    <div class="flex gap-1 text-emerald-400" aria-label="{{ $rating }} من 5">
                        @for ($i = 0; $i < $rating; $i++)
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.5.04.703.662.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345l2.125-5.111Z" /></svg>
                        @endfor
                    </div>
                    <blockquote class="mt-4 flex-1 text-sm leading-relaxed text-mist-600 dark:text-mist-300">«{{ $testimonial->quote }}»</blockquote>
                    <figcaption class="mt-6 flex items-center gap-3 border-t border-mist-200 pt-4 dark:border-ink-800">
                        @if ($avatar)
                            <img src="{{ $avatar->url() }}" alt="{{ $avatar->alt_text ?? $testimonial->client_name }}" class="h-10 w-10 shrink-0 rounded-full object-cover ring-1 ring-emerald-400/20">
                        @else
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-400/15 font-display text-sm font-bold text-emerald-600 dark:text-emerald-400">{{ $initial }}</span>
                        @endif
                        <div>
                            <p class="text-sm font-semibold text-ink-900 dark:text-ink-50">{{ $testimonial->client_name }}</p>
                            @if ($position !== '')
                                <p class="text-xs text-mist-500 dark:text-mist-400">{{ $position }}</p>
                            @endif
                        </div>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</section>
@endif
