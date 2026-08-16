<x-mail::message>
# مرحباً {{ $userName }}

تم إنشاء حسابك في مؤسسة **{{ $tenantName }}** على مدى بدور **{{ $roleLabel }}**.

**بيانات الدخول:**

- البريد: {{ $email }}
- كلمة المرور المؤقتة: `{{ $plainPassword }}`

<x-mail::button :url="$loginUrl">
تسجيل الدخول
</x-mail::button>

يُفضّل تغيير كلمة المرور بعد أول تسجيل دخول.

شكراً،<br>
{{ config('app.name') }}
</x-mail::message>
