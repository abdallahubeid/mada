@props([
    'footer' => null,
])

@php
    $socialIcons = [
        'x' => '<path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />',
        'linkedin' => '<path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.225 0z" />',
        'facebook' => '<path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />',
        'github' => '<path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.073z" />',
        'youtube' => '<path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z" />',
    ];

    $link = static function (string $textKey, string $urlKey) use ($settings): ?array {
        $label = $settings[$textKey] ?? null;
        $url = $settings[$urlKey] ?? null;

        if (! filled($label) || ! filled($url)) {
            return null;
        }

        return ['label' => $label, 'url' => $url];
    };

    $columns = array_values(array_filter([
        [
            'title' => $settings['footer_title1'] ?? null,
            'links' => array_values(array_filter([
                $link('footer_btn1_text', 'footer_btn1_link'),
                $link('footer_btn2_text', 'footer_btn2_link'),
                $link('footer_btn3_text', 'footer_btn3_link'),
                $link('footer_btn4_text', 'footer_btn4_link'),
            ])),
        ],
        [
            'title' => $settings['footer_title2'] ?? null,
            'links' => array_values(array_filter([
                $link('footer_btn5_text', 'footer_btn5_link'),
                $link('footer_btn6_text', 'footer_btn6_link'),
                $link('footer_btn7_text', 'footer_btn7_link'),
            ])),
        ],
        [
            'title' => $settings['footer_title3'] ?? null,
            'links' => array_values(array_filter([
                $link('footer_btn8_text', 'footer_btn8_link'),
                $link('footer_btn9_text', 'footer_btn9_link'),
            ])),
        ],
    ], static fn (array $column): bool => filled($column['title']) && $column['links'] !== []));

    $socials = collect(range(1, 5))
        ->map(function (int $i) use ($settings, $socialIcons): ?array {
            $url = $settings["social_btn{$i}_link"] ?? null;

            if (! filled($url)) {
                return null;
            }

            $platform = match ($i) {
                1 => 'x',
                2 => 'linkedin',
                3 => 'facebook',
                4 => 'github',
                5 => 'youtube',
                default => 'x',
            };

            return [
                'label' => $settings["social_btn{$i}_text"] ?? '',
                'url' => $url,
                'icon' => $socialIcons[$platform],
            ];
        })
        ->filter()
        ->values()
        ->all();

    $blurb = $settings['footer_description'] ?? 'نظام إدارة الموارد المؤسسي الذكي الذي يواكب طموحات مؤسستك القادمة.';
    $newsletterTitle = $settings['footer_newsletter_title'] ?? 'البريد الإلكتروني';
    $newsletterBtnText = $settings['footer_newsletter_btn_text'] ?? 'اشتراك';
    $copyright = $footer['copyright'] ?? ('© '.now()->year.' مدى. جميع الحقوق محفوظة.');

    if ($footer !== null) {
        $columns = $footer['columns'] ?? $columns;
        $socials = collect($footer['social'] ?? $socials)->map(fn (array $s): array => [
            'label' => $s['label'],
            'url' => $s['url'],
            'icon' => $socialIcons[$s['platform'] ?? ''] ?? $socialIcons['x'],
        ])->all();
        $blurb = $footer['blurb'] ?? $blurb;
        $newsletterTitle = $footer['newsletter_title'] ?? $newsletterTitle;
        $newsletterBtnText = $footer['newsletter_btn_text'] ?? $newsletterBtnText;
        $copyright = $footer['copyright'] ?? $copyright;
    }
@endphp

<footer class="bg-footer py-16 text-footer-muted" data-surface="dark">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <a href="/" class="flex shrink-0 items-center gap-2.5">
                    @if ($logoUrl = \App\Models\Setting::assetUrl($settings['site_logo'] ?? null))
                        <img src="{{ $logoUrl }}" alt="مدى" class="h-10 max-h-10 w-auto max-w-[220px] shrink-0 object-contain object-start">
                    @else
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-500 font-display text-sm font-bold text-white">م</span>
                        <span class="font-display text-xl font-bold text-footer-heading">مدى <span class="text-brand-300">ERP</span></span>
                    @endif
                </a>
                <p class="mt-4 max-w-xs text-sm leading-relaxed">{{ $blurb }}</p>

                <form method="POST" action="{{ route('marketing.newsletter.subscribe') }}" class="mt-6 max-w-sm">
                    @csrf
                    <div class="flex gap-2">
                        <label for="footer-email" class="sr-only">{{ $newsletterTitle }}</label>
                        <input
                            id="footer-email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            required
                            placeholder="{{ $newsletterTitle }}"
                            class="w-full rounded-md border border-white/10 bg-white/5 px-4 py-2 text-sm text-white placeholder:text-white/40 focus:border-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-300/40"
                        >
                        <button type="submit" class="shrink-0 rounded-md bg-brand-500 px-4 py-2 text-sm font-semibold text-white transition duration-200 hover:bg-brand-600 active:scale-[0.98]">
                            {{ $newsletterBtnText }}
                        </button>
                    </div>
                    @error('email')
                        <p class="mt-2 text-xs text-critical-400">{{ $message }}</p>
                    @enderror
                </form>
            </div>

            @foreach ($columns as $column)
                <div>
                    <p class="text-sm font-semibold text-footer-heading">{{ $column['title'] }}</p>
                    <ul class="mt-4 space-y-2 text-sm">
                        @foreach ($column['links'] as $navLink)
                            <li><a href="{{ $navLink['url'] ?? $navLink['path'] ?? '#' }}" class="text-footer-muted transition duration-150 hover:text-white">{{ $navLink['label'] }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

        <div class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-white/10 pt-8 text-xs sm:flex-row">
            <p>{{ $copyright }}</p>
            @if ($socials !== [])
                <div class="flex items-center gap-3">
                    @foreach ($socials as $social)
                        <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $social['label'] }}" class="flex h-9 w-9 items-center justify-center rounded-full border border-white/10 text-mist-500 transition duration-150 hover:border-brand-300 hover:bg-white/5 hover:text-ink-900">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">{!! $social['icon'] !!}</svg>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</footer>
