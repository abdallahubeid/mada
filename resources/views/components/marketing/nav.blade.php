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
                <img src="{{ $logoUrl }}" alt="مدى" class="h-10 max-h-10 w-auto max-w-[220px] shrink-0 object-contain object-start">
            @else
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-500 font-display text-sm font-bold text-white shadow-glow">م</span>
                <span class="font-display text-xl font-bold text-ink-900 dark:text-white">مدى <span class="text-brand-600 dark:text-brand-300">ERP</span></span>
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
                        'text-brand-600 dark:text-brand-300' => $active,
                        'text-ink-600 hover:text-brand-600 dark:text-mist-300 dark:hover:text-brand-300' => ! $active,
                    ])
                >{{ $link['label'] }}</a>
            @endforeach
        </nav>

        <div class="hidden items-center gap-4 lg:flex">

            @auth
                <a
                    href="{{ $workspaceUrl }}"
                    class="rounded-md bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white shadow-glow transition duration-200 ease-in-out hover:bg-brand-600 active:scale-[0.98]"
                >
                    {{ $workspaceLabel }}
                </a>
            @endauth

            @guest
                <a href="{{ route('login') }}" class="text-sm font-medium text-ink-600 transition duration-200 hover:text-brand-600 dark:text-mist-300 dark:hover:text-brand-300">
                    تسجيل الدخول
                </a>
                <a
                    href="{{ route('register') }}"
                    class="rounded-md bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white shadow-glow transition duration-200 ease-in-out hover:bg-brand-600 active:scale-[0.98]"
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
        class="border-t border-mist-200 bg-white px-4 py-4 lg:hidden dark:border-ink-800"
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
                <a href="{{ $workspaceUrl }}" class="mt-1 rounded-md bg-brand-500 px-5 py-2.5 text-center text-sm font-semibold text-white shadow-glow">{{ $workspaceLabel }}</a>
            @endauth

            @guest
                <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-ink-600 hover:bg-mist-100 dark:text-mist-300 dark:hover:bg-ink-800">تسجيل الدخول</a>
                <a href="{{ route('register') }}" class="mt-1 rounded-md bg-brand-500 px-5 py-2.5 text-center text-sm font-semibold text-white shadow-glow">ابدأ التجربة المجانية</a>
            @endguest
        </nav>
    </div>
</header>
