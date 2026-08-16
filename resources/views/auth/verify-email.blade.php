<x-layouts.auth-centered title="تحقق من بريدك الإلكتروني — مدى">
    <div class="rounded-xl border border-mist-200 bg-white p-8 text-center shadow-sm">
        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-500/10 text-brand-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
            </svg>
        </span>

        <h1 class="mt-6 font-display text-2xl font-medium text-ink-900">تحقق من بريدك الإلكتروني</h1>

        <p class="mt-3 text-sm leading-relaxed text-mist-500">
            أرسلنا رابط تفعيل إلى
            <span class="font-medium text-ink-700">{{ auth()->user()->email }}</span>
            — يرجى فتح الرسالة والضغط على الرابط لتفعيل حسابك ومتابعة إعداد مؤسستك.
        </p>

        @if (session('status') === 'verification-link-sent')
            <p class="mt-4 rounded-xl bg-brand-500/10 px-3 py-2 text-sm font-medium text-brand-600">
                تم إرسال رابط تفعيل جديد إلى بريدك الإلكتروني.
            </p>
        @endif

        <div class="mt-8 flex flex-col gap-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-md bg-brand-500 px-6 py-3 text-sm font-semibold text-white transition duration-200 ease-in-out hover:bg-brand-600 active:translate-y-px"
                >
                    إعادة إرسال رابط التفعيل
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-md border border-mist-300 px-6 py-3 text-sm font-semibold text-ink-700 transition duration-200 ease-in-out hover:border-brand-500 hover:text-brand-600 active:translate-y-px"
                >
                    تسجيل الخروج
                </button>
            </form>
        </div>
    </div>
</x-layouts.auth-centered>
