@php
    $inputClass = 'w-full rounded-xl border border-mist-200 bg-white px-3 py-2.5 text-sm text-ink-700 placeholder:text-mist-400 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50';
    $labelClass = 'mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200';
    $isEdit = isset($admin);
    $selectedRole = old('role', $assignedRole ?? ($roles->first()?->name ?? \App\Domain\Platform\PlatformPermissionCatalog::ROLE_CONTENT_MANAGER));
    $hadOldPermissions = old('permissions') !== null;
    $selectedPermissions = old(
        'permissions',
        $isEdit
            ? ($directPermissions ?? [])
            : ($rolePermissionsMap[$selectedRole] ?? [])
    );
    $applyRoleOnInit = ! $isEdit && ! $hadOldPermissions;
@endphp

<div
    class="space-y-6"
    x-data="{
        role: @js($selectedRole),
        roleMap: @js($rolePermissionsMap),
        selected: @js(array_values($selectedPermissions)),
        applyOnInit: @js($applyRoleOnInit),
        open: true,
        init() {
            if (this.applyOnInit) {
                this.applyRole();
            }
        },
        applyRole() {
            this.selected = [...(this.roleMap[this.role] || [])];
        },
    }"
>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="{{ $labelClass }}" for="name">الاسم</label>
            <input id="name" name="name" type="text" value="{{ old('name', $admin->name ?? '') }}" required class="{{ $inputClass }}">
            @error('name') <p class="mt-1 text-xs text-danger-solid">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="{{ $labelClass }}" for="email">البريد الإلكتروني</label>
            <input id="email" name="email" type="email" value="{{ old('email', $admin->email ?? '') }}" required class="{{ $inputClass }}">
            @error('email') <p class="mt-1 text-xs text-danger-solid">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="{{ $labelClass }}" for="password">كلمة المرور{{ $isEdit ? ' (اختياري)' : '' }}</label>
            <input id="password" name="password" type="password" @unless($isEdit) required @endunless autocomplete="new-password" class="{{ $inputClass }}">
            @error('password') <p class="mt-1 text-xs text-danger-solid">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="{{ $labelClass }}" for="password_confirmation">تأكيد كلمة المرور</label>
            <input id="password_confirmation" name="password_confirmation" type="password" @unless($isEdit) required @endunless autocomplete="new-password" class="{{ $inputClass }}">
        </div>
    </div>

    <div>
        <label class="{{ $labelClass }}" for="role">الدور</label>
        <select
            id="role"
            name="role"
            required
            class="{{ $inputClass }}"
            x-model="role"
            @change="applyRole()"
        >
            @foreach ($roles as $roleOption)
                <option value="{{ $roleOption->name }}" @selected($selectedRole === $roleOption->name)>
                    {{ $roleLabels[$roleOption->name] ?? $roleOption->name }}
                </option>
            @endforeach
        </select>
        @error('role') <p class="mt-1 text-xs text-danger-solid">{{ $message }}</p> @enderror
    </div>

    <div class="rounded-2xl border border-mist-200 dark:border-ink-600">
        <button
            type="button"
            @click="open = ! open"
            class="flex w-full items-center justify-between gap-3 px-4 py-3 text-start text-sm font-semibold text-ink-800 dark:text-ink-100"
        >
            <span>صلاحيات مباشرة (تُحدَّث تلقائيًا حسب الدور)</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition" :class="open && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
            </svg>
        </button>
        <div x-show="open" x-cloak x-transition class="border-t border-mist-200 p-4 dark:border-ink-600">
            <p class="mb-4 text-xs text-mist-500">عند اختيار الدور تُفعَّل صلاحياته افتراضيًا، ويمكنك تعديلها يدويًا قبل الحفظ.</p>
            <div class="grid gap-4 lg:grid-cols-2">
                @foreach ($groups as $domain => $group)
                    <section class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                        <h3 class="mb-3 font-display text-sm font-bold text-ink-900 dark:text-ink-50">{{ $group['label'] }}</h3>
                        <div class="space-y-2">
                            @foreach ($group['permissions'] as $permission => $permissionLabel)
                                <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-mist-200 px-3 py-2.5 transition hover:border-emerald-300 dark:border-ink-600 dark:hover:border-emerald-500/40">
                                    <span class="text-sm text-ink-700 dark:text-mist-200">{{ $permissionLabel }}</span>
                                    <span class="relative inline-flex shrink-0">
                                        <input
                                            type="checkbox"
                                            name="permissions[]"
                                            value="{{ $permission }}"
                                            class="peer sr-only"
                                            x-model="selected"
                                        >
                                        <span class="h-6 w-11 rounded-full bg-mist-200 transition peer-checked:bg-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-400/40 dark:bg-ink-700"></span>
                                        <span class="pointer-events-none absolute start-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5 rtl:peer-checked:-translate-x-5"></span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    </div>
</div>
