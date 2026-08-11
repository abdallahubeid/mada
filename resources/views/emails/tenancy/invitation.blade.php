<x-mail::message>
# دعوة للانضمام إلى {{ $tenantName }}

تمت دعوتك للانضمام إلى مؤسسة **{{ $tenantName }}** على Veyra بدور **{{ $role }}**.

البريد المدعو: {{ $email }}

تنتهي صلاحية الدعوة في: {{ $expiresAt->timezone(config('app.timezone'))->format('Y-m-d H:i') }}

رمز الدعوة (للمراحل اللاحقة من القبول): `{{ $token }}`

شكراً،<br>
{{ config('app.name') }}
</x-mail::message>
