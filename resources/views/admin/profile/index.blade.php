@extends('layouts.admin')

@section('title', 'الملف الشخصي')

@section('breadcrumbs')
    <span class="text-mist-500 dark:text-mist-400">الحساب والوصول</span>
    <span class="mx-1.5 text-mist-300 dark:text-mist-600">/</span>
    <span class="text-ink-700 dark:text-mist-200">الملف الشخصي</span>
@endsection

@section('content')
    @php
        $inputClass =
            'w-full rounded-xl border border-mist-200 bg-white px-3 py-2.5 text-sm text-ink-700 placeholder:text-mist-400 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50';
        $labelClass = 'mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200';
        $cardClass =
            'rounded-2xl border border-mist-200 bg-white p-5 shadow-sm sm:p-6 dark:border-ink-600 dark:bg-ink-800';
        $initial = mb_substr($profile['name'], 0, 1);
    @endphp

    <div
        x-data="{
            avatar: null,
            theme: localStorage.getItem('veyra-theme') || '{{ $profile['theme'] }}',
            toast: false,
            toastTimer: null,
            notifications: {
                @foreach ($notificationPreferences as $pref)
                    {{ $pref['key'] }}: {{ $pref['enabled'] ? 'true' : 'false' }}, @endforeach
            },
            previewAvatar(event) {
                const file = event.target.files[0];
                if (!file) { return; }
                this.avatar = URL.createObjectURL(file);
            },
            save() {
                this.toast = true;
                clearTimeout(this.toastTimer);
                this.toastTimer = setTimeout(() => (this.toast = false), 3200);
            },
        }"
        class="mx-auto max-w-4xl pb-28 sm:pb-32">
        {{-- Header --}}
        <div>
            <h2 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">الملف الشخصي</h2>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">إدارة معلوماتك الشخصية، تفضيلات النظام، والتنبيهات.</p>
        </div>

        <div class="mt-6 space-y-4">
            {{-- Card 1: Personal Details --}}
            <div class="{{ $cardClass }}">
                <h3 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">المعلومات الشخصية</h3>

                <div class="mt-5 flex flex-col gap-6 sm:flex-row sm:items-start">
                    {{-- Avatar dropzone --}}
                    <div class="flex shrink-0 flex-col items-center">
                        <div class="relative h-24 w-24">
                            <div
                                class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-full border border-mist-200 bg-emerald-400/15 dark:border-ink-600">
                                <template x-if="avatar">
                                    <img :src="avatar" alt="الصورة الشخصية" class="h-full w-full object-cover">
                                </template>
                                <span x-show="! avatar"
                                    class="font-display text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ $initial }}</span>
                            </div>
                            <label
                                for="avatar-upload"
                                class="absolute bottom-0 end-0 cursor-pointer rounded-full border-2 border-white bg-emerald-500 p-1.5 text-ink-950 shadow-md transition duration-200 hover:bg-emerald-400 active:scale-90 dark:border-ink-800"
                                title="تغيير الصورة">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z" />
                                </svg>
                            </label>
                            <input id="avatar-upload" type="file" accept="image/*" class="sr-only"
                                @change="previewAvatar($event)">
                        </div>
                        <p class="mt-2 text-center text-xs text-mist-400 dark:text-mist-500">JPG أو PNG بحد أقصى 2MB</p>
                    </div>

                    {{-- Fields --}}
                    <div class="grid min-w-0 flex-1 grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="{{ $labelClass }}">الاسم الكامل</label>
                            <input type="text" value="{{ $profile['name'] }}" class="{{ $inputClass }}">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="{{ $labelClass }}">البريد الإلكتروني</label>
                            <div class="relative">
                                <input type="email" dir="ltr" value="{{ $profile['email'] }}"
                                    class="{{ $inputClass }} pe-28">
                                @if ($profile['email_verified'])
                                    <span
                                        class="absolute inset-y-0 end-2 my-auto flex h-6 items-center gap-1 rounded-full bg-emerald-500/10 px-2 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                        متحقق منه
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">رقم الهاتف</label>
                            <input type="tel" dir="ltr" value="{{ $profile['phone'] }}"
                                class="{{ $inputClass }}">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">الدور</label>
                            <div class="pt-1">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-500/20 bg-emerald-500/10 px-3 py-1.5 text-sm font-medium text-emerald-600 dark:text-emerald-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    {{ $profile['role_label'] }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card 2: System Preferences --}}
            <div class="{{ $cardClass }}">
                <h3 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">تفضيلات النظام والواجهة</h3>

                <div class="mt-5 grid grid-cols-1 gap-6 sm:grid-cols-2">
                    {{-- Interface language --}}
                    <div>
                        <label class="{{ $labelClass }}">لغة الواجهة</label>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach (['ar' => 'العربية', 'en' => 'English'] as $code => $label)
                                <label
                                    class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border px-3 py-2.5 text-sm font-medium transition-colors has-[:checked]:border-emerald-400 has-[:checked]:bg-emerald-500/10 has-[:checked]:text-emerald-600 dark:has-[:checked]:text-emerald-400 {{ 'border-mist-200 text-ink-700 hover:bg-mist-100 dark:border-ink-600 dark:text-mist-200 dark:hover:bg-ink-700/50' }}">
                                    <input type="radio" name="language" value="{{ $code }}"
                                        @checked($profile['language'] === $code) class="sr-only">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Theme preference --}}
                    <div>
                        <label class="{{ $labelClass }}">مظهر الواجهة</label>
                        <div class="grid grid-cols-3 gap-2">
                            @php
                                $themes = [
                                    'light' => [
                                        'label' => 'فاتح',
                                        'icon' =>
                                            '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />',
                                    ],
                                    'dark' => [
                                        'label' => 'داكن',
                                        'icon' =>
                                            '<path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />',
                                    ],
                                    'system' => [
                                        'label' => 'النظام',
                                        'icon' =>
                                            '<path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 12V5.25" />',
                                    ],
                                ];
                            @endphp
                            @foreach ($themes as $code => $meta)
                                <button
                                    type="button"
                                    @click="theme = '{{ $code }}'"
                                    :class="theme === '{{ $code }}'
                                        ?
                                        'border-emerald-400 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' :
                                        'border-mist-200 text-ink-600 hover:bg-mist-100 dark:border-ink-600 dark:text-mist-300 dark:hover:bg-ink-700/50'"
                                    class="flex flex-col items-center gap-1.5 rounded-xl border px-2 py-3 text-xs font-medium transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor"
                                        stroke-width="1.5">{!! $meta['icon'] !!}</svg>
                                    {{ $meta['label'] }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card 3: Notification Preferences --}}
            <div class="{{ $cardClass }}">
                <h3 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">تفضيلات الإشعارات</h3>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">اختر التنبيهات التي ترغب باستقبالها عبر البريد
                    الإلكتروني.</p>

                <ul class="mt-4 divide-y divide-mist-100 dark:divide-ink-700">
                    @foreach ($notificationPreferences as $pref)
                        @php $key = $pref['key']; @endphp
                        <li class="flex items-center justify-between gap-4 py-3.5">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-ink-900 dark:text-ink-50">{{ $pref['label'] }}</p>
                                <p class="mt-0.5 text-xs text-mist-500 dark:text-mist-400">{{ $pref['desc'] }}</p>
                            </div>
                            <button
                                type="button"
                                @click="notifications.{{ $key }} = ! notifications.{{ $key }}"
                                :class="notifications.{{ $key }} ? 'bg-emerald-500' : 'bg-mist-300 dark:bg-ink-700'"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-emerald-400/40 focus:ring-offset-0"
                                role="switch"
                                :aria-checked="notifications.{{ $key }}"
                                aria-label="{{ $pref['label'] }}">
                                <span
                                    aria-hidden="true"
                                    :class="notifications.{{ $key }} ? 'ltr:translate-x-5 rtl:-translate-x-5' :
                                        'translate-x-0'"
                                    class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- Action footer --}}
        <div class="mt-8 flex items-center justify-end gap-3 border-t border-mist-200 pt-6 dark:border-ink-800">
            <button type="button"
                class="rounded-xl px-4 py-2 text-sm font-semibold text-mist-500 transition duration-200 hover:text-ink-900 dark:text-mist-400 dark:hover:text-white">إلغاء</button>
            <button type="button" @click="save()"
                class="rounded-xl bg-emerald-400 px-5 py-2 text-sm font-semibold text-emerald-900 shadow-glow transition duration-200 hover:bg-emerald-300 active:scale-95">حفظ
                التغييرات</button>
        </div>

        {{-- Success toast --}}
        <div
            x-show="toast"
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-4"
            class="fixed bottom-6 start-6 z-50 flex items-center gap-3 rounded-xl border border-emerald-500/30 bg-white px-4 py-3 shadow-xl dark:bg-ink-800"
            role="status">
            <span
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </span>
            <div>
                <p class="text-sm font-semibold text-ink-900 dark:text-ink-50">تم الحفظ بنجاح</p>
                <p class="text-xs text-mist-500 dark:text-mist-400">تم تحديث بيانات ملفك الشخصي.</p>
            </div>
        </div>
    </div>
@endsection
