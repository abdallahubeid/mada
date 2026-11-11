{{-- Email verification notice, in the public site's Andromeda identity (docs/DESIGN_SYSTEM.md §1.3). --}}
<x-layouts.auth-centered title="تحقق من بريدك الإلكتروني — مدى">
    <div class="site-card p-8 text-center">
        <span class="site-feature-card-icon">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
            </svg>
        </span>

        <h1 class="site-section-title site-h3 mt-6">تحقق من بريدك الإلكتروني</h1>
        <p class="mt-8">
            أرسلنا رابط تفعيل إلى
            <span class="font-medium text-site-dark" dir="ltr">{{ auth()->user()->email }}</span>
            — يرجى فتح الرسالة والضغط على الرابط لتفعيل حسابك ومتابعة إعداد مؤسستك.
        </p>

        @if (session('status') === 'verification-link-sent')
            <p class="site-notice mt-5 text-start" role="status">تم إرسال رابط تفعيل جديد إلى بريدك الإلكتروني.</p>
        @endif

        <div class="mt-8 flex flex-col gap-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="site-btn site-btn-primary w-full">إعادة إرسال رابط التفعيل</button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="site-btn site-btn-outline w-full">تسجيل الخروج</button>
            </form>
        </div>
    </div>
</x-layouts.auth-centered>
