<x-mail::message>
# استفسار جديد من موقع Veyra

**الاسم:** {{ $inquiry['name'] }}

**البريد:** {{ $inquiry['email'] }}

@if (! empty($inquiry['company']))
**المؤسسة:** {{ $inquiry['company'] }}
@endif

**الموضوع:** {{ $subjectLabel }}

---

{{ $inquiry['message'] }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
