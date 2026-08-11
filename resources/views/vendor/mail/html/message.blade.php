<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ config('app.name') }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{--
    Footer.

    Built from named routes guarded by Route::has() rather than hardcoded
    paths: this template renders inside queued jobs and tests as well as web
    requests, and a route() call on a name that has been renamed would throw
    while BUILDING a message — turning a copy change into a failed
    notification. A missing route simply drops its link instead.
--}}
<x-slot:footer>
<x-mail::footer>
{{--
    The sign-off stays in the footer, not only in the notification salutation.

    MailMessage-based notifications supply their own `->salutation(...)`, but
    plain markdown Mailables — the newsletter, the contact reply, the interview
    invitation — have no salutation slot at all. Dropping this line while
    rewriting the footer left every one of those messages ending abruptly on
    its last body paragraph.
--}}
تحياتنا، فريق عمل {{ config('app.name') }}

{{ config('app.name') }} — منصّة إدارة موارد المؤسسات: التوظيف، الموارد البشرية، الحضور والإجازات، الرواتب والمصروفات.

@if (\Illuminate\Support\Facades\Route::has('marketing.contact')){{ '['.'تواصل مع الدعم'.']('.route('marketing.contact').')' }} @endif @if (\Illuminate\Support\Facades\Route::has('landing')){{ ' · ['.'الموقع الرسمي'.']('.route('landing').')' }} @endif

© {{ date('Y') }} {{ config('app.name') }}. جميع الحقوق محفوظة.
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
