<x-mail::message>
# رد على رسالتك

مرحبًا {{ $thread->sender_name }}،

وصلنا رد من فريق الدعم بخصوص موضوع **{{ $thread->subject }}**:

<x-mail::panel>
{{ $message->body }}
</x-mail::panel>

يمكنك الرد على هذا البريد للمتابعة.

شكرًا لتواصلك،<br>
{{ config('app.name') }}
</x-mail::message>
