{{--
    Lightweight holding screen for a verified user whose tenant is still
    `pending_approval` (docs/ARCHITECTURE.md §3, BR-203). This is
    intentionally minimal — the full onboarding/setup wizard experience is
    separate, forthcoming work per docs/DEVELOPMENT_ROADMAP.md.
--}}
<x-layouts.guest title="حسابك قيد المراجعة — Veyra ERP">
    <div class="rounded-3xl border border-mist-200 bg-white p-8 text-center shadow-sm dark:border-ink-700 dark:bg-ink-800/60">
        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-400/10 text-emerald-600 dark:text-emerald-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        </span>

        <h1 class="mt-6 font-display text-2xl font-bold text-ink-900 dark:text-ink-50">حسابك قيد المراجعة</h1>

        <p class="mt-3 text-sm leading-relaxed text-mist-500 dark:text-mist-400">
            @if ($tenant)
                تم تفعيل بريدك الإلكتروني بنجاح. مؤسستك
                <span class="font-medium text-ink-700 dark:text-mist-200">{{ $tenant->name }}</span>
                الآن قيد مراجعة فريق Veyra، وسنرسل لك بريداً إلكترونياً فور تفعيل مساحة عملك.
            @else
                حسابك غير مرتبط بأي مؤسسة حالياً. يرجى التواصل مع فريق الدعم لمتابعة الإعداد.
            @endif
        </p>

        <div class="mt-8">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-full border border-mist-300 px-6 py-3 text-sm font-semibold text-ink-700 transition duration-200 ease-in-out hover:border-emerald-400 hover:text-emerald-600 active:scale-[0.98] dark:border-ink-600 dark:text-mist-200 dark:hover:border-emerald-400 dark:hover:text-emerald-400"
                >
                    تسجيل الخروج
                </button>
            </form>
        </div>
    </div>
</x-layouts.guest>
