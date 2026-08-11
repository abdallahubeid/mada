{{--
    Notification email body — published from the framework and translated.

    ─────────────────────────────────────────────────────────────────────────
    WHY THIS VIEW IS PUBLISHED

    Every MailMessage-based notification renders through here, including the
    two the framework ships itself (ResetPassword, VerifyEmail). Their subject,
    greeting, body and salutation are supplied in Arabic from
    AppServiceProvider::localizeFrameworkNotifications() — but three strings
    live in THIS view and reach the reader through `@lang()`:

        "Hello!" · "Whoops!" · "Regards," · the subcopy fallback paragraph

    There is no `lang/` directory in this application and the app locale is
    `en`, so `@lang()` returned English. The result was an English subcopy —
    "If you're having trouble clicking the … button" — sitting under Arabic
    body copy, right-aligned inside an RTL shell.

    Translating here rather than adding a `lang/ar` set is deliberate: a
    translation set only takes effect once the locale is actually switched to
    `ar`, which is a wider change than these strings warrant and would alter
    date and number formatting across the product too.
    ─────────────────────────────────────────────────────────────────────────
--}}
<x-mail::message>
{{-- Greeting --}}
@if (! empty($greeting))
# {{ $greeting }}
@else
@if ($level === 'error')
# عذراً!
@else
# مرحباً!
@endif
@endif

{{-- Intro Lines --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Action Button --}}
@isset($actionText)
<?php
    $color = match ($level) {
        'success', 'error' => $level,
        default => 'primary',
    };
?>
<x-mail::button :url="$actionUrl" :color="$color">
{{ $actionText }}
</x-mail::button>
@endisset

{{-- Outro Lines --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

{{-- Salutation --}}
@if (! empty($salutation))
{{ $salutation }}
@else
مع التقدير،<br>
فريق {{ config('app.name') }}
@endif

{{--
    Subcopy.

    `dir="ltr"` on the URL span, with the link isolated on its own line: the
    reset URL is Latin text inside a right-to-left paragraph, and without the
    override the bidirectional algorithm reorders its trailing punctuation and
    query string — producing a link that looks subtly wrong and, when copied
    by hand from a client that honours the visual order, is wrong.
--}}
@isset($actionText)
<x-slot:subcopy>
إذا واجهت صعوبة في الضغط على زر «{{ $actionText }}»، انسخ الرابط التالي والصقه في متصفحك:

<span class="break-all" dir="ltr" style="direction: ltr; unicode-bidi: isolate; display: inline-block; text-align: left;">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endisset
</x-mail::message>
