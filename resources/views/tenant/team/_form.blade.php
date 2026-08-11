@php
    $inputClass = 'w-full rounded-xl border border-mist-200 bg-white px-3 py-2.5 text-sm text-ink-700 shadow-sm transition placeholder:text-mist-400 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50';
    $labelClass = 'mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200';
    $errorClass = 'mt-1.5 text-xs text-danger-solid';
    $isEdit = ($method ?? 'POST') !== 'POST';
    $rolePermissionsMap = $rolePermissionsMap ?? [];
    $permissionGroups = $permissionGroups ?? [];
    $directPermissions = $directPermissions ?? [];

    $selectedRole = old('role', $member->exists
        ? ($member->relationLoaded('roles') ? $member->roles->first()?->name : $member->getRoleNames()->first())
        : (array_key_first($rolePermissionsMap) ?: ''));

    $hadOldPermissions = old('permissions') !== null;
    $selectedPermissions = old(
        'permissions',
        $isEdit
            ? $directPermissions
            : ($rolePermissionsMap[$selectedRole] ?? [])
    );
    $applyRoleOnInit = ! $isEdit && ! $hadOldPermissions;
@endphp

<form
    method="POST"
    action="{{ $action }}"
    class="space-y-6"
    x-data="{
        autoGenerate: {{ old('auto_generate_password', ! $isEdit) ? 'true' : 'false' }},
        resetPassword: {{ old('reset_password', false) ? 'true' : 'false' }},
        role: @js($selectedRole ?? ''),
        roleMap: @js($rolePermissionsMap),
        selectedPermissions: @js(array_values($selectedPermissions)),
        applyOnInit: @js($applyRoleOnInit),
        permissionsOpen: true,
        init() {
            if (this.applyOnInit && this.role) {
                this.applyRole();
            }
        },
        applyRole() {
            this.selectedPermissions = [...(this.roleMap[this.role] || [])];
        },
    }"
>
    @csrf
    @if ($isEdit)
        @method($method)
    @endif

    <div class="space-y-4 rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800">
        <div>
            <label for="name" class="{{ $labelClass }}">الاسم الكامل</label>
            <input id="name" type="text" name="name" value="{{ old('name', $member->name) }}" required class="{{ $inputClass }}">
            @error('name')
                <p class="{{ $errorClass }}">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="{{ $labelClass }}">البريد الإلكتروني</label>
            <input id="email" type="email" name="email" dir="ltr" value="{{ old('email', $member->email) }}" required class="{{ $inputClass }}">
            @error('email')
                <p class="{{ $errorClass }}">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="department_id" class="{{ $labelClass }}">القسم</label>
                <select id="department_id" name="department_id" class="{{ $inputClass }}">
                    <option value="">— بدون قسم —</option>
                    @foreach ($departments as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('department_id', $member->department_id) === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('department_id')
                    <p class="{{ $errorClass }}">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="role" class="{{ $labelClass }}">الدور</label>
                <select
                    id="role"
                    name="role"
                    required
                    class="{{ $inputClass }}"
                    x-model="role"
                    @change="applyRole()"
                >
                    <option value="" disabled>اختر الدور</option>
                    @foreach ($roles as $roleName)
                        <option value="{{ $roleName }}">
                            {{ $roleLabels[$roleName] ?? $roleName }}
                        </option>
                    @endforeach
                </select>
                @error('role')
                    <p class="{{ $errorClass }}">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
        <button
            type="button"
            @click="permissionsOpen = ! permissionsOpen"
            class="flex w-full items-center justify-between gap-3 px-4 py-3 text-start text-sm font-semibold text-ink-800 dark:text-ink-100"
        >
            <span>صلاحيات مباشرة (تُحدّث تلقائياً حسب الدور)</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 transition" :class="permissionsOpen && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
            </svg>
        </button>
        <div x-show="permissionsOpen" x-cloak x-transition class="border-t border-mist-200 p-4 dark:border-ink-600">
            <p class="mb-4 text-xs text-mist-500 dark:text-mist-400">
                عند اختيار الدور تُفعّل صلاحياته افتراضياً، ويمكنك تعديلها يدوياً قبل الحفظ.
            </p>
            <div class="grid gap-4 lg:grid-cols-2">
                @foreach ($permissionGroups as $domain => $group)
                    <section class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-900/40">
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
                                            x-model="selectedPermissions"
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
            @error('permissions')
                <p class="{{ $errorClass }} mt-3">{{ $message }}</p>
            @enderror
            @error('permissions.*')
                <p class="{{ $errorClass }} mt-3">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="space-y-4 rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800">
        @if ($isEdit)
            <label class="flex items-center gap-2 rounded-xl border border-mist-200 px-3 py-2.5 text-sm text-ink-700 dark:border-ink-600 dark:text-mist-200">
                <input type="hidden" name="reset_password" value="0">
                <input type="checkbox" name="reset_password" value="1" class="rounded border-mist-300 text-emerald-500 focus:ring-emerald-400" x-model="resetPassword">
                إعادة تعيين كلمة المرور وإرسالها بالبريد
            </label>
        @else
            <input type="hidden" name="reset_password" value="0">
        @endif

        <div class="space-y-3" x-show="! {{ $isEdit ? 'true' : 'false' }} || resetPassword" x-cloak>
            <label class="flex items-center gap-2 text-sm text-ink-700 dark:text-mist-200">
                <input type="hidden" name="auto_generate_password" value="0">
                <input type="checkbox" name="auto_generate_password" value="1" class="rounded border-mist-300 text-emerald-500 focus:ring-emerald-400" x-model="autoGenerate">
                توليد كلمة مرور تلقائياً
            </label>

            <div class="grid gap-4 sm:grid-cols-2" x-show="! autoGenerate" x-cloak>
                <div>
                    <label for="password" class="{{ $labelClass }}">كلمة المرور</label>
                    <input id="password" type="password" name="password" dir="ltr" class="{{ $inputClass }}" autocomplete="new-password">
                    @error('password')
                        <p class="{{ $errorClass }}">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="{{ $labelClass }}">تأكيد كلمة المرور</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" dir="ltr" class="{{ $inputClass }}" autocomplete="new-password">
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-end gap-3">
        <a href="{{ route('team.index') }}" class="rounded-xl px-4 py-2 text-sm font-semibold text-mist-600 transition hover:text-ink-700 dark:text-mist-400 dark:hover:text-mist-200">إلغاء</a>
        <button type="submit" class="rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-glow transition hover:bg-emerald-300">حفظ</button>
    </div>
</form>
