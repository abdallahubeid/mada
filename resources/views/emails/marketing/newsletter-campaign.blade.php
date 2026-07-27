<x-mail::message>
{!! $bodyHtml !!}

---

<x-mail::button :url="$unsubscribeUrl" color="error">
إلغاء الاشتراك
</x-mail::button>
</x-mail::message>
