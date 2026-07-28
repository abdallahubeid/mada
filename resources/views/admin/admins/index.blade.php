@extends('layouts.admin')

@section('title', 'إدارة المشرفين')

@section('breadcrumbs')
    <span class="text-mist-500 dark:text-mist-400">الحساب والوصول</span>
    <span class="mx-1.5 text-mist-300 dark:text-mist-600">/</span>
    <span class="text-ink-700 dark:text-mist-200">مديرو المنصّة</span>
@endsection

@section('content')
    @php
        $inputClass = 'w-full rounded-xl border border-mist-200 bg-white px-3 py-2.5 text-sm text-ink-700 placeholder:text-mist-400 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50';
        $labelClass = 'mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200';
        $roleMeta = [
            'super_admin' => ['label' => 'مشرف عام', 'badge' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'],
            'support_admin' => ['label' => 'مشرف دعم', 'badge' => 'bg-sky-500/10 text-sky-600 dark:text-sky-400'],
        ];
        $permissionGroups = [
            'المستأجرون' => [
                ['key' => 'view_tenants', 'label' => 'عرض المستأجرين وتفاصيلهم'],
                ['key' => 'manage_tenants', 'label' => 'إدارة المستأجرين وتحوّلات الحالة'],
            ],
            'الباقات' => [
                ['key' => 'view_plans', 'label' => 'عرض الباقات والحدود'],
                ['key' => 'manage_plans', 'label' => 'إدارة الباقات والحدود'],
            ],
            'الرسائل والدعم' => [
                ['key' => 'reply_support', 'label' => 'الرد على تذاكر الدعم'],
                ['key' => 'manage_support', 'label' => 'تغيير حالة التذاكر'],
            ],
            'سجل النشاط' => [
                ['key' => 'view_audit_log', 'label' => 'الاطلاع على سجل النشاط'],
            ],
            'الإعدادات العامة' => [
                ['key' => 'manage_settings', 'label' => 'إدارة إعدادات المنصّة'],
            ],
        ];
    @endphp

    <div x-data="{
        drawer: false,
        editing: null,
        menu: null,
        role: 'support_admin',
        password: '',
        passwordConfirm: '',
        showPassword: false,
        forceChange: true,
        supportDefaults: {
            view_tenants: true, manage_tenants: false,
            view_plans: false, manage_plans: false,
            reply_support: true, manage_support: false,
            view_audit_log: false,
            manage_settings: false,
        },
        permissions: {
            view_tenants: true, manage_tenants: false,
            view_plans: false, manage_plans: false,
            reply_support: true, manage_support: false,
            view_audit_log: false,
            manage_settings: false,
        },
        openDrawer(admin) {
            this.editing = admin;
            this.role = admin ? admin.role : 'support_admin';
            this.applyRole();
            this.password = '';
            this.passwordConfirm = '';
            this.showPassword = false;
            this.forceChange = true;
            this.drawer = true;
        },
        applyRole() {
            if (this.role === 'super_admin') {
                Object.keys(this.permissions).forEach((k) => (this.permissions[k] = true));
            } else {
                this.permissions = { ...this.supportDefaults };
            }
        },
        generatePassword() {
            const letters = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz';
            const digits = '23456789';
            const symbols = '@#!$%&';
            const pick = (set, n) => Array.from({ length: n }, () => set[Math.floor(Math.random() * set.length)]).join('');
            const pwd = 'Veyra' + pick(symbols, 1) + pick(digits, 4) + pick(letters, 4);
            this.password = pwd;
            this.passwordConfirm = pwd;
            this.showPassword = true;
        },
    }">
        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">إدارة المشرفين</h2>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">حسابات مشغّلي المنصّة — إنشاء مباشر مع تحقق بخطوتين إلزامي (BR-807).</p>
            </div>
            <button type="button" @click="openDrawer(null)" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-400 px-4 py-2.5 text-sm font-semibold text-emerald-900 shadow-glow transition duration-200 hover:bg-emerald-300 active:scale-95">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                إضافة مشرف جديد
            </button>
        </div>

        {{-- Metrics --}}
        <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($metrics as $metric)
                <div class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                    <p class="text-sm text-mist-500 dark:text-mist-400">{{ $metric['label'] }}</p>
                    <p class="mt-1.5 font-display text-2xl font-bold text-ink-900 dark:text-ink-50">{{ $metric['value'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- Admins table --}}
        <div class="mt-4 overflow-x-auto w-full rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <div class="w-full overflow-x-auto">
                <table class="w-full min-w-max text-start text-sm">
                    <thead>
                        <tr class="border-b border-mist-100 text-xs uppercase tracking-wide text-mist-500 dark:border-ink-700 dark:text-mist-400">
                            <th class="px-5 py-3 text-start font-semibold">المشرف</th>
                            <th class="px-5 py-3 text-start font-semibold">الدور</th>
                            <th class="px-5 py-3 text-start font-semibold">الحالة</th>
                            <th class="px-5 py-3 text-start font-semibold">التحقق بخطوتين</th>
                            <th class="px-5 py-3 text-start font-semibold">تاريخ الإنشاء</th>
                            <th class="px-5 py-3 text-end font-semibold">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                        @foreach ($admins as $index => $admin)
                            <tr class="transition duration-150 hover:bg-mist-50 dark:hover:bg-ink-700/40">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-400/15 font-display text-sm font-bold text-emerald-600 dark:text-emerald-400">{{ mb_substr($admin['name'], 0, 1) }}</span>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <p class="font-medium text-ink-900 dark:text-ink-50">{{ $admin['name'] }}</p>
                                                @if ($admin['is_self'])
                                                    <span class="rounded-full bg-mist-100 px-2 py-0.5 text-[10px] font-semibold text-mist-500 dark:bg-ink-700 dark:text-mist-400">أنت</span>
                                                @endif
                                            </div>
                                            <p class="text-xs text-mist-400 dark:text-mist-500" dir="ltr">{{ $admin['email'] }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium {{ $roleMeta[$admin['role']]['badge'] }}">{{ $roleMeta[$admin['role']]['label'] }}</span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <x-admin.status-badge :status="$admin['status']" />
                                </td>
                                <td class="px-5 py-3.5">
                                    @if ($admin['two_factor'])
                                        <span class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                            مُفعّل
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 text-xs font-medium text-mist-400 dark:text-mist-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                                            بانتظار الإعداد
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-mist-500 dark:text-mist-400">{{ $admin['created_at'] }}</td>
                                <td class="px-5 py-3.5 text-end">
                                    <div class="relative inline-block text-start" @click.outside="menu === {{ $index }} && (menu = null)">
                                        <button type="button" @click="menu = (menu === {{ $index }} ? null : {{ $index }})" class="rounded-lg border border-mist-200 p-1.5 text-mist-500 transition hover:border-emerald-400 hover:text-emerald-600 active:scale-90 dark:border-ink-600 dark:text-mist-300 dark:hover:text-emerald-400" aria-label="إجراءات">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 6.75a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM12 13.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM12 20.25a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" /></svg>
                                        </button>
                                        <div
                                            x-show="menu === {{ $index }}"
                                            x-cloak
                                            x-transition:enter="transition ease-out duration-150"
                                            x-transition:enter-start="opacity-0 -translate-y-1"
                                            x-transition:enter-end="opacity-100 translate-y-0"
                                            class="absolute end-0 z-20 mt-1 w-48 overflow-hidden rounded-xl border border-mist-200 bg-white py-1 shadow-lg dark:border-ink-600 dark:bg-ink-800"
                                        >
                                            <button type="button" @click="openDrawer(@js($admin)); menu = null" class="flex w-full items-center gap-2 px-4 py-2 text-start text-sm text-ink-700 transition hover:bg-mist-100 dark:text-mist-200 dark:hover:bg-ink-700">تعديل الدور</button>
                                            @if ($admin['status'] === 'suspended')
                                                <button type="button" class="flex w-full items-center gap-2 px-4 py-2 text-start text-sm text-emerald-600 transition hover:bg-mist-100 dark:text-emerald-400 dark:hover:bg-ink-700">إعادة التفعيل</button>
                                            @else
                                                <button type="button" @disabled($admin['is_self']) @class([
                                                    'flex w-full items-center gap-2 px-4 py-2 text-start text-sm transition',
                                                    'cursor-not-allowed text-mist-300 dark:text-mist-600' => $admin['is_self'],
                                                    'text-amber-600 hover:bg-mist-100 dark:text-amber-400 dark:hover:bg-ink-700' => ! $admin['is_self'],
                                                ])>إيقاف مؤقت</button>
                                            @endif
                                            <div class="my-1 h-px bg-mist-100 dark:bg-ink-700"></div>
                                            <button type="button" @disabled($admin['is_self']) @class([
                                                'flex w-full items-center gap-2 px-4 py-2 text-start text-sm transition',
                                                'cursor-not-allowed text-mist-300 dark:text-mist-600' => $admin['is_self'],
                                                'text-danger-solid hover:bg-danger-solid/10' => ! $admin['is_self'],
                                            ])>إلغاء الوصول</button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Lockout safeguard note (BR-808) --}}
        <p class="mt-3 text-xs text-mist-400 dark:text-mist-500">
            لا يمكنك إيقاف أو إلغاء وصول حسابك، ولا يمكن إيقاف آخر مشرف عام نشط لمنع الإغلاق الكامل للمنصّة (BR-808).
        </p>

        {{-- Add / Edit admin drawer --}}
        <div
            x-show="drawer"
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="drawer = false"
            class="fixed inset-0 z-50 bg-ink-950/60 backdrop-blur-sm"
        ></div>
        <aside
            x-show="drawer"
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-x-full rtl:-translate-x-full opacity-0"
            x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-x-0 opacity-100"
            x-transition:leave-end="translate-x-full rtl:-translate-x-full opacity-0"
            class="fixed inset-y-0 end-0 z-50 flex w-full max-w-md flex-col border-s border-mist-200 bg-white shadow-xl dark:border-ink-600 dark:bg-ink-800"
        >
            <div class="flex items-center justify-between border-b border-mist-100 px-5 py-4 dark:border-ink-700">
                <h3 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50" x-text="editing ? 'تعديل صلاحيات المشرف' : 'إضافة مشرف جديد للنظام'"></h3>
                <button type="button" @click="drawer = false" class="rounded-lg p-1 text-mist-400 transition hover:bg-mist-100 hover:text-mist-600 active:scale-90 dark:hover:bg-ink-700 dark:hover:text-white" aria-label="إغلاق">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="flex-1 space-y-4 overflow-y-auto p-5">
                <div>
                    <label class="{{ $labelClass }}">الاسم الكامل</label>
                    <input type="text" placeholder="مثال: نورة العنزي" class="{{ $inputClass }}" x-bind:value="editing?.name || ''">
                </div>
                <div>
                    <label class="{{ $labelClass }}">البريد الإلكتروني</label>
                    <input type="email" dir="ltr" placeholder="name@veyra.app" class="{{ $inputClass }}" x-bind:value="editing?.email || ''" x-bind:disabled="editing !== null">
                </div>

                {{-- Temporary password (direct account creation) --}}
                <div>
                    <div class="mb-1.5 flex items-center justify-between gap-2">
                        <label class="text-sm font-medium text-ink-700 dark:text-mist-200" x-text="editing ? 'كلمة مرور جديدة (اتركها فارغة للإبقاء على الحالية)' : 'كلمة المرور المؤقتة'"></label>
                        <button type="button" @click="generatePassword()" class="shrink-0 text-xs font-semibold text-emerald-600 transition hover:underline dark:text-emerald-400">توليد كلمة مرور عشوائية</button>
                    </div>
                    <div class="relative">
                        <input
                            :type="showPassword ? 'text' : 'password'"
                            x-model="password"
                            dir="ltr"
                            placeholder="••••••••"
                            autocomplete="new-password"
                            class="{{ $inputClass }} pe-11"
                        >
                        <button
                            type="button"
                            @click="showPassword = ! showPassword"
                            class="absolute inset-y-0 end-1.5 my-auto flex h-8 w-8 items-center justify-center rounded-lg text-mist-400 transition hover:text-ink-700 dark:hover:text-mist-100"
                            :aria-label="showPassword ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'"
                        >
                            <svg x-show="! showPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                            <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="{{ $labelClass }}">تأكيد كلمة المرور</label>
                    <input
                        :type="showPassword ? 'text' : 'password'"
                        x-model="passwordConfirm"
                        dir="ltr"
                        placeholder="••••••••"
                        autocomplete="new-password"
                        class="{{ $inputClass }}"
                    >
                </div>
                <label class="flex cursor-pointer items-start gap-2.5 rounded-xl border border-mist-200 p-3 dark:border-ink-600">
                    <input type="checkbox" x-model="forceChange" class="mt-0.5 h-4 w-4 shrink-0 accent-emerald-500">
                    <span class="text-sm text-ink-700 dark:text-mist-200">إجبار المشرف على تغيير كلمة المرور عند أول تسجيل دخول</span>
                </label>

                <div>
                    <label class="{{ $labelClass }}">الدور</label>
                    <select x-model="role" @change="applyRole()" class="{{ $inputClass }}">
                        <option value="support_admin">مشرف دعم</option>
                        <option value="super_admin">مشرف عام</option>
                    </select>
                </div>

                {{-- Granular permissions matrix --}}
                <div class="rounded-xl border border-mist-200 p-4 dark:border-ink-600">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-mist-500 dark:text-mist-400">مصفوفة الصلاحيات</p>
                        {{-- Super Admin full-access hint --}}
                        <span
                            x-show="role === 'super_admin'"
                            x-cloak
                            class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2.5 py-1 text-[11px] font-medium text-emerald-600 dark:text-emerald-400"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                            وصول كامل للنظام
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-mist-400 dark:text-mist-500" x-show="role === 'support_admin'" x-cloak>
                        حدّد الصلاحيات الوظيفية المسموح بها لمشرف الدعم.
                    </p>

                    <div class="mt-4 space-y-5">
                        @foreach ($permissionGroups as $groupLabel => $items)
                            <div>
                                <p class="mb-2 text-xs font-semibold text-ink-700 dark:text-mist-200">{{ $groupLabel }}</p>
                                <ul class="space-y-1.5">
                                    @foreach ($items as $item)
                                        @php $key = $item['key']; @endphp
                                        <li class="flex items-center justify-between gap-3 rounded-lg px-3 py-2 transition-colors hover:bg-mist-100 dark:hover:bg-ink-800/50">
                                            <span class="text-sm text-ink-700 dark:text-mist-200">{{ $item['label'] }}</span>
                                            <button
                                                type="button"
                                                @click="role !== 'super_admin' && (permissions.{{ $key }} = ! permissions.{{ $key }})"
                                                :disabled="role === 'super_admin'"
                                                :class="permissions.{{ $key }} ? 'bg-emerald-500' : 'bg-mist-300 dark:bg-ink-700'"
                                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-emerald-400/40 focus:ring-offset-0 disabled:cursor-not-allowed disabled:opacity-60"
                                                role="switch"
                                                :aria-checked="permissions.{{ $key }}"
                                                aria-label="{{ $item['label'] }}"
                                            >
                                                <span
                                                    aria-hidden="true"
                                                    :class="permissions.{{ $key }} ? 'ltr:translate-x-5 rtl:-translate-x-5' : 'translate-x-0'"
                                                    class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                                ></span>
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-mist-100 px-5 py-4 dark:border-ink-700">
                <button type="button" @click="drawer = false" class="rounded-xl px-4 py-2 text-sm font-semibold text-mist-600 transition hover:bg-mist-100 dark:text-mist-300 dark:hover:bg-ink-700">إلغاء</button>
                <button type="button" @click="drawer = false" class="rounded-xl bg-emerald-400 px-5 py-2 text-sm font-semibold text-emerald-900 shadow-glow transition duration-200 hover:bg-emerald-300 active:scale-95" x-text="editing ? 'حفظ التغييرات' : 'إنشاء الحساب'"></button>
            </div>
        </aside>
    </div>
@endsection
