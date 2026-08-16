@php
    $inputClass = 'w-full rounded-xl border border-mist-200 bg-white px-3 py-2 text-sm text-ink-700 shadow-sm transition placeholder:text-mist-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50';
    $labelClass = 'mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200';
    $errorClass = 'mt-1.5 text-xs text-danger-solid';
    $isProtected = $isProtected ?? false;
    $isOwnerRole = $isOwnerRole ?? false;
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
        <label for="name" class="{{ $labelClass }}">اسم الدور</label>
        @if ($isProtected)
            <input id="name" type="text" value="{{ $roleLabel ?? $role->name }}" disabled class="{{ $inputClass }} opacity-60">
            <input type="hidden" name="name" value="{{ $role->name }}">
            <p class="mt-1.5 text-xs text-mist-500">دور نظامي — لا يمكن تغيير الاسم.</p>
        @else
            <input id="name" type="text" name="name" value="{{ old('name', $role->name) }}" required class="{{ $inputClass }}" placeholder="مثال: Operations Lead">
            @error('name')
                <p class="{{ $errorClass }}">{{ $message }}</p>
            @enderror
        @endif
    </div>

    @if ($isOwnerRole)
        <div class="rounded-2xl border border-brand-500/40 bg-brand-500/10 px-4 py-3 text-sm text-brand-700 dark:text-brand-200">
            دور المالك يمتلك صلاحية كاملة تلقائياً على كل قدرات المستأجر (الحالية والمستقبلية) عبر تجاوز التفويض — لا حاجة لمزامنة يدوية عند إضافة صلاحيات جديدة.
        </div>
    @endif

    <div @class(['pointer-events-none opacity-70' => $isOwnerRole])>
        <h3 class="mb-3 font-display text-sm font-medium text-ink-900 dark:text-ink-50">مصفوفة الصلاحيات</h3>
        <x-admin.permission-domain-cards
            :groups="$groups"
            :assigned="$assigned ?? old('permissions', [])"
        />
        @error('permissions')
            <p class="{{ $errorClass }}">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex justify-end gap-3">
        <a href="{{ route('roles.index') }}" class="rounded-xl px-4 py-2 text-sm font-semibold text-mist-600 transition hover:text-ink-700 dark:text-mist-400">إلغاء</a>
        <button type="submit" class="rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-glow transition hover:bg-brand-600">حفظ</button>
    </div>
</form>
