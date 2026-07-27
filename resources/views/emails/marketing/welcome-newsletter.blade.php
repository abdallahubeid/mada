<x-mail::message>
# مرحبًا بك في نشرة {{ config('app.name') }}

شكرًا لاشتراكك عبر البريد **{{ $email }}**. سنشاركك التحديثات والمنتجات الجديدة قريبًا.

<x-mail::button :url="$unsubscribeUrl" color="error">
إلغاء الاشتراك
</x-mail::button>

أو انسخ الرابط: {{ $unsubscribeUrl }}
</x-mail::message>
