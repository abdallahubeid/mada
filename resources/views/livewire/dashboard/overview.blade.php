@php
    $user = auth()->user();
    $tenant = $user?->tenant;
    $statusLabel = match ($tenant?->status?->value) {
        'active' => 'نشط',
        'pending_approval' => 'بانتظار الموافقة',
        'pending_verification' => 'بانتظار التحقق',
        'suspended' => 'موقوف',
        'cancelled' => 'ملغى',
        default => $tenant?->status?->label() ?? '—',
    };
@endphp

<div class="space-y-6">
    <div>
        <h2 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">
            مرحباً بعودتك، {{ $user?->name }}
        </h2>
        <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">
            <span class="font-medium text-ink-700 dark:text-mist-200">{{ $tenant?->name }}</span>
            <span class="mx-1.5 text-mist-400 dark:text-mist-600" aria-hidden="true">·</span>
            {{ $statusLabel }}
        </p>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="ui-card p-5">
            <p class="text-sm font-medium text-mist-500 dark:text-mist-400">الموظفون</p>
            <p class="mt-2 font-display text-3xl font-medium text-ink-900 dark:text-ink-50">0</p>
        </div>
        <div class="ui-card p-5">
            <p class="text-sm font-medium text-mist-500 dark:text-mist-400">المشاريع النشطة</p>
            <p class="mt-2 font-display text-3xl font-medium text-ink-900 dark:text-ink-50">0</p>
        </div>
        <div class="ui-card p-5">
            <p class="text-sm font-medium text-mist-500 dark:text-mist-400">طلبات الإجازة</p>
            <p class="mt-2 font-display text-3xl font-medium text-ink-900 dark:text-ink-50">0</p>
        </div>
    </div>

    <div class="ui-card p-5 sm:p-6">
        <h3 class="font-display text-lg font-medium text-ink-900 dark:text-ink-50">مساحة عملك جاهزة</h3>
        <p class="mt-2 text-sm leading-relaxed text-mist-500 dark:text-mist-400">
            ابدأ من إعدادات المؤسسة والأقسام — ستظهر مؤشرات لوحة التحكم هنا مع كل موديول جديد.
        </p>
        <div class="mt-4 flex flex-wrap gap-3">
            @can('tenant.settings.view')
                <a
                    href="{{ route('settings.company') }}"
                    wire:navigate
                    class="ui-btn ui-btn-primary"
                >
                    إعدادات المؤسسة
                </a>
            @endcan
            @can('hr.departments.view_any')
                <a
                    href="{{ route('hr.departments.index') }}"
                    wire:navigate
                    class="ui-btn ui-btn-secondary"
                >
                    الأقسام
                </a>
            @endcan
        </div>
    </div>
</div>
