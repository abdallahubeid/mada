@php
    /*
     * Shared marketing top nav (docs/MARKETING.md §5.1). Sticky + blurred,
     * RTL-native, dark-elevated theme with a persistent trial CTA and theme
     * toggle. Links use final marketing paths; active state via request()->is().
     */
    $links = [
        ['label' => 'الصفحة الرئيسية', 'path' => '/', 'route' => 'landing'],
        ['label' => 'من نحن', 'path' => '/about', 'route' => 'marketing.about'],
        ['label' => 'الوحدات', 'path' => '/#modules', 'route' => null],
        ['label' => 'المميزات', 'path' => '/features', 'route' => 'marketing.features'],
        ['label' => 'الأسعار', 'path' => '/pricing', 'route' => 'marketing.pricing'],
        ['label' => 'تواصل معنا', 'path' => '/contact', 'route' => 'marketing.contact'],
    ];

    /*
     * ─────────────────────────────────────────────────────────────────────
     * WHERE "GO TO MY WORKSPACE" ACTUALLY LANDS
     *
     * A signed-in visitor previously saw THREE controls here: the admin link
     * (operators only), plus "تسجيل الدخول" and "ابدأ التجربة المجانية" —
     * inviting someone who is already authenticated to log in again and start
     * a second trial. All three now collapse into one CTA.
     *
     * The destination is resolved rather than hardcoded to `dashboard`,
     * because that route sits behind `tenant.active`: sending a tenant that is
     * still verifying or awaiting approval there produces a 403, which is a
     * dead end reached from a button that promised a dashboard. Onboarding
     * tenants go to the setup wizard instead, which is reachable precisely
     * because those routes sit under `tenant.context` and never `tenant.active`.
     *
     * Suspended, rejected and cancelled tenants DO go to `dashboard` and get
     * the 403 — its message is per-status and explains the actual situation
     * ("تم إيقاف حساب مؤسستك مؤقتاً…"), which the setup wizard would not.
     *
     * Operators route through preferredAdminHomeRoute() — the same helper the
     * 403 page uses — so an admin whose role lacks `dashboard.view` lands on a
     * console page they can actually open rather than another 403.
     * ─────────────────────────────────────────────────────────────────────
     */
    $authUser = auth()->user();
    $workspaceUrl = null;
    $workspaceLabel = 'انتقل إلى لوحة التحكم';

    if ($authUser !== null) {
        if ($authUser->canAccessPlatformConsole()) {
            $workspaceUrl = route($authUser->preferredAdminHomeRoute());
        } elseif ($authUser->tenant?->status->isOnboarding()) {
            $workspaceUrl = route('dashboard.setup');
            $workspaceLabel = 'أكمل إعداد مؤسستك';
        } else {
            $workspaceUrl = route('dashboard');
        }
    }
@endphp

<header
    x-data="{ mobileOpen: false }"
    class="sticky top-0 z-40 border-b border-mist-200/70 bg-white/80 backdrop-blur-md dark:border-ink-800 dark:bg-ink-900/80"
>
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <a href="{{ route('landing') }}" class="flex shrink-0 items-center gap-2.5">
            @if ($logoUrl = \App\Models\Setting::assetUrl($settings['site_logo'] ?? null))
                <img src="{{ $logoUrl }}" alt="Veyra ERP" class="h-10 max-h-10 w-auto max-w-[220px] shrink-0 object-contain object-start">
            @else
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500 font-display text-sm font-bold text-ink-950 shadow-glow">V</span>
                <span class="font-display text-xl font-bold text-ink-900 dark:text-white">Veyra <span class="text-emerald-600 dark:text-emerald-400">ERP</span></span>
            @endif
        </a>

        <nav class="hidden items-center gap-8 lg:flex" aria-label="التنقّل الرئيسي">
            @foreach ($links as $link)
                @php
                    $active = ($link['route'] ?? null)
                        ? request()->routeIs($link['route'])
                        : false;
                @endphp
                <a
                    href="{{ $link['path'] }}"
                    @class([
                        'text-sm font-medium transition duration-200',
                        'text-emerald-600 dark:text-emerald-400' => $active,
                        'text-ink-600 hover:text-emerald-600 dark:text-mist-300 dark:hover:text-emerald-400' => ! $active,
                    ])
                >{{ $link['label'] }}</a>
            @endforeach
        </nav>

        <div class="hidden items-center gap-4 lg:flex">
            <button
                type="button"
                x-data
                @click="
                    const root = document.documentElement;
                    const nextDark = ! root.classList.contains('dark');
                    root.classList.toggle('dark', nextDark);
                    localStorage.setItem('veyra-theme', nextDark ? 'dark' : 'light');
                "
                class="rounded-lg p-2 text-mist-500 transition duration-200 ease-in-out hover:bg-mist-100 active:scale-90 dark:text-mist-400 dark:hover:bg-ink-800"
                aria-label="تبديل المظهر"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                </svg>
            </button>

            @auth
                <a
                    href="{{ $workspaceUrl }}"
                    class="rounded-full bg-emerald-500 px-5 py-2.5 text-sm font-semibold text-ink-950 shadow-glow transition duration-200 ease-in-out hover:bg-emerald-400 active:scale-[0.98]"
                >
                    {{ $workspaceLabel }}
                </a>
            @endauth

            @guest
                <a href="{{ route('login') }}" class="text-sm font-medium text-ink-600 transition duration-200 hover:text-emerald-600 dark:text-mist-300 dark:hover:text-emerald-400">
                    تسجيل الدخول
                </a>
                <a
                    href="{{ route('register') }}"
                    class="rounded-full bg-emerald-500 px-5 py-2.5 text-sm font-semibold text-ink-950 shadow-glow transition duration-200 ease-in-out hover:bg-emerald-400 active:scale-[0.98]"
                >
                    ابدأ التجربة المجانية
                </a>
            @endguest
        </div>

        <button
            type="button"
            @click="mobileOpen = !mobileOpen"
            class="rounded-lg p-2 text-mist-500 transition duration-200 hover:bg-mist-100 active:scale-90 lg:hidden dark:text-mist-400 dark:hover:bg-ink-800"
            aria-label="فتح القائمة"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
        </button>
    </div>

    {{-- Mobile menu --}}
    <div
        x-show="mobileOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="border-t border-mist-200 bg-white px-4 py-4 lg:hidden dark:border-ink-800 dark:bg-ink-900"
    >
        <nav class="flex flex-col gap-1" aria-label="التنقّل للجوال">
            @foreach ($links as $link)
                <a href="{{ $link['path'] }}" class="rounded-lg px-3 py-2 text-sm font-medium text-ink-600 hover:bg-mist-100 dark:text-mist-300 dark:hover:bg-ink-800">{{ $link['label'] }}</a>
            @endforeach
            {{-- Mirrors the desktop bar above: one CTA when signed in, the
                 login/register pair when not. Kept in step deliberately — a
                 mobile menu still offering "تسجيل الدخول" to a signed-in user
                 is the same defect, just on a narrower screen. --}}
            @auth
                <a href="{{ $workspaceUrl }}" class="mt-1 rounded-full bg-emerald-500 px-5 py-2.5 text-center text-sm font-semibold text-ink-950 shadow-glow">{{ $workspaceLabel }}</a>
            @endauth

            @guest
                <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-ink-600 hover:bg-mist-100 dark:text-mist-300 dark:hover:bg-ink-800">تسجيل الدخول</a>
                <a href="{{ route('register') }}" class="mt-1 rounded-full bg-emerald-500 px-5 py-2.5 text-center text-sm font-semibold text-ink-950 shadow-glow">ابدأ التجربة المجانية</a>
            @endguest
        </nav>
    </div>
</header>
