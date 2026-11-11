@php
    /*
     * Shared marketing top nav (docs/MARKETING.md §5.1), styled after the
     * Andromeda header: fixed white bar that tightens and gains a shadow once
     * the page scrolls, slides away on scroll-down and returns on scroll-up.
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

<div class="site-header-spacer" aria-hidden="true"></div>

<header
    x-data="{
        mobileOpen: false,
        sticky: false,
        hidden: false,
        lastY: 0,
        track() {
            const y = window.scrollY;
            this.sticky = y > 0;
            this.hidden = ! this.mobileOpen && y > this.$el.offsetHeight + 200 && y > this.lastY;
            this.lastY = y;
        },
    }"
    x-init="track()"
    @scroll.window.passive="track()"
    @focusin="hidden = false"
    :class="{ 'is-sticky': sticky, 'is-unpinned': hidden }"
    class="site-header"
>
    <div class="site-container-xl flex items-center justify-between gap-6">
        <a href="{{ route('landing') }}" class="flex shrink-0 items-center gap-2.5" aria-label="مدى — الصفحة الرئيسية">
            @if ($logoUrl = \App\Models\Setting::assetUrl($settings['site_logo'] ?? null))
                <img src="{{ $logoUrl }}" alt="مدى" class="h-10 max-h-10 w-auto max-w-[220px] shrink-0 object-contain object-start">
            @else
                <span class="flex h-9 w-9 items-center justify-center rounded-md bg-site-primary text-lg font-semibold text-white">م</span>
                <span class="text-2xl font-semibold text-site-dark">مدى</span>
            @endif
        </a>

        <nav class="hidden xl:block" aria-label="التنقّل الرئيسي">
            <ul class="flex items-center">
                @foreach ($links as $link)
                    @php
                        $active = ($link['route'] ?? null)
                            ? request()->routeIs($link['route'])
                            : false;
                    @endphp
                    <li>
                        <a
                            href="{{ $link['path'] }}"
                            class="site-nav-link whitespace-nowrap"
                            @if ($active) aria-current="page" @endif
                        >{{ $link['label'] }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="flex items-center gap-2">
            <div class="hidden items-center gap-2 whitespace-nowrap xl:flex">
                @auth
                    <a href="{{ $workspaceUrl }}" class="site-btn site-btn--compact site-btn-primary">{{ $workspaceLabel }}</a>
                @endauth

                @guest
                    <a href="{{ route('login') }}" class="site-nav-link">تسجيل الدخول</a>
                    <a href="{{ route('register') }}" class="site-btn site-btn--compact site-btn-primary">ابدأ التجربة المجانية</a>
                @endguest
            </div>

            {{-- The template's toggler: a 3×3 grid of squares, swapped for a cross when open. --}}
            <button
                type="button"
                @click="mobileOpen = ! mobileOpen"
                class="flex h-11 w-11 items-center justify-center text-site-dark xl:hidden"
                :aria-expanded="mobileOpen ? 'true' : 'false'"
                aria-controls="site-mobile-menu"
                :aria-label="mobileOpen ? 'إغلاق القائمة' : 'فتح القائمة'"
                aria-label="فتح القائمة"
            >
                <svg x-show="! mobileOpen" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" class="h-7 w-7" aria-hidden="true">
                    <path fill="currentColor" d="M 5 5 L 5 11 L 11 11 L 11 5 L 5 5 z M 13 5 L 13 11 L 19 11 L 19 5 L 13 5 z M 21 5 L 21 11 L 27 11 L 27 5 L 21 5 z M 7 7 L 9 7 L 9 9 L 7 9 L 7 7 z M 15 7 L 17 7 L 17 9 L 15 9 L 15 7 z M 23 7 L 25 7 L 25 9 L 23 9 L 23 7 z M 5 13 L 5 19 L 11 19 L 11 13 L 5 13 z M 13 13 L 13 19 L 19 19 L 19 13 L 13 13 z M 21 13 L 21 19 L 27 19 L 27 13 L 21 13 z M 7 15 L 9 15 L 9 17 L 7 17 L 7 15 z M 15 15 L 17 15 L 17 17 L 15 17 L 15 15 z M 23 15 L 25 15 L 25 17 L 23 17 L 23 15 z M 5 21 L 5 27 L 11 27 L 11 21 L 5 21 z M 13 21 L 13 27 L 19 27 L 19 21 L 13 21 z M 21 21 L 21 27 L 27 27 L 27 21 L 21 21 z M 7 23 L 9 23 L 9 25 L 7 25 L 7 23 z M 15 23 L 17 23 L 17 25 L 15 25 L 15 23 z M 23 23 L 25 23 L 25 25 L 23 25 L 23 23 z" />
                </svg>
                <svg x-show="mobileOpen" x-cloak xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" d="M6 6l12 12M18 6 6 18" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Mobile menu: the template drops the links into a centred full-width list. --}}
    <div
        id="site-mobile-menu"
        x-show="mobileOpen"
        x-cloak
        x-transition.opacity.duration.200ms
        class="site-container-xl xl:hidden"
    >
        <nav class="border-t border-site-border pt-3 pb-2 text-center" aria-label="التنقّل للجوال">
            <ul>
                @foreach ($links as $link)
                    @php
                        $active = ($link['route'] ?? null) ? request()->routeIs($link['route']) : false;
                    @endphp
                    <li>
                        <a href="{{ $link['path'] }}" class="site-nav-link" @if ($active) aria-current="page" @endif>{{ $link['label'] }}</a>
                    </li>
                @endforeach
            </ul>

            {{--
                Mirrors the desktop bar: one CTA when signed in, the
                login/register pair when not. A mobile menu still offering
                "تسجيل الدخول" to a signed-in user would be the same defect on
                a narrower screen.
            --}}
            <div class="mt-3 flex flex-col items-stretch gap-2 px-2">
                @auth
                    <a href="{{ $workspaceUrl }}" class="site-btn site-btn--compact site-btn-primary">{{ $workspaceLabel }}</a>
                @endauth

                @guest
                    <a href="{{ route('login') }}" class="site-nav-link">تسجيل الدخول</a>
                    <a href="{{ route('register') }}" class="site-btn site-btn--compact site-btn-primary">ابدأ التجربة المجانية</a>
                @endguest
            </div>
        </nav>
    </div>
</header>
