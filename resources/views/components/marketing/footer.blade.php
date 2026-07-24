@props([
    'footer' => null,
])

@php
    $defaultColumns = [
        [
            'title' => 'المنتج',
            'links' => [
                ['label' => 'المميزات', 'url' => '/features'],
                ['label' => 'الحلول', 'url' => '/solutions'],
                ['label' => 'الأسعار', 'url' => '/pricing'],
                ['label' => 'الأمان والامتثال', 'url' => '/security'],
            ],
        ],
        [
            'title' => 'الشركة',
            'links' => [
                ['label' => 'من نحن', 'url' => '/about'],
                ['label' => 'تواصل معنا', 'url' => '/contact'],
                ['label' => 'الأسئلة الشائعة', 'url' => '/faq'],
            ],
        ],
        [
            'title' => 'القانونية',
            'links' => [
                ['label' => 'سياسة الخصوصية', 'url' => '/privacy'],
                ['label' => 'الشروط والأحكام', 'url' => '/terms'],
            ],
        ],
    ];

    $socialIcons = [
        'x' => '<path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />',
        'linkedin' => '<path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.225 0z" />',
    ];

    $blurb = $footer['blurb'] ?? 'نظام إدارة الموارد المؤسسي الذكي الذي يواكب طموحات مؤسستك القادمة.';
    $copyright = $footer['copyright'] ?? ('© '.now()->year.' Veyra ERP. جميع الحقوق محفوظة.');
    $columns = $footer['columns'] ?? $defaultColumns;
    $socials = collect($footer['social'] ?? [
        ['platform' => 'x', 'label' => 'X', 'url' => '#'],
        ['platform' => 'linkedin', 'label' => 'LinkedIn', 'url' => '#'],
    ])->map(fn (array $s): array => [
        'label' => $s['label'],
        'url' => $s['url'],
        'icon' => $socialIcons[$s['platform'] ?? ''] ?? $socialIcons['x'],
    ])->all();
@endphp

<footer class="bg-ink-950 py-16 text-mist-400">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <a href="/" class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500 font-display text-sm font-bold text-ink-950 shadow-glow">V</span>
                    <span class="font-display text-xl font-bold text-white">Veyra <span class="text-emerald-400">ERP</span></span>
                </a>
                <p class="mt-4 max-w-xs text-sm leading-relaxed">{{ $blurb }}</p>

                <form method="POST" action="{{ route('marketing.newsletter.store') }}" class="mt-6 max-w-sm">
                    @csrf
                    @if (session('newsletter_status'))
                        <p class="mb-3 text-sm font-medium text-emerald-400" role="status">{{ session('newsletter_status') }}</p>
                    @endif
                    <div class="flex gap-2">
                        <label for="footer-email" class="sr-only">البريد الإلكتروني</label>
                        <input
                            id="footer-email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            required
                            placeholder="اشترك في نشرتنا البريدية"
                            class="w-full rounded-full border border-ink-800 bg-ink-900 px-4 py-2 text-sm text-white placeholder:text-mist-500 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30"
                        >
                        <button type="submit" class="shrink-0 rounded-full bg-emerald-500 px-4 py-2 text-sm font-semibold text-ink-950 transition duration-200 hover:bg-emerald-400 active:scale-[0.98]">
                            اشتراك
                        </button>
                    </div>
                    @error('email')
                        <p class="mt-2 text-xs text-danger-solid">{{ $message }}</p>
                    @enderror
                </form>
            </div>

            @foreach ($columns as $column)
                <div>
                    <p class="text-sm font-semibold text-white">{{ $column['title'] }}</p>
                    <ul class="mt-4 space-y-2 text-sm">
                        @foreach ($column['links'] as $link)
                            <li><a href="{{ $link['url'] ?? $link['path'] ?? '#' }}" class="transition duration-200 hover:text-emerald-400">{{ $link['label'] }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

        <div class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-ink-800 pt-8 text-xs sm:flex-row">
            <p>{{ $copyright }}</p>
            <div class="flex items-center gap-3">
                @foreach ($socials as $social)
                    <a href="{{ $social['url'] }}" aria-label="{{ $social['label'] }}" class="flex h-9 w-9 items-center justify-center rounded-full border border-ink-800 text-mist-400 transition duration-200 hover:border-emerald-400 hover:text-emerald-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">{!! $social['icon'] !!}</svg>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</footer>
