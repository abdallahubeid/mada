@extends('layouts.admin')

@section('title', $tenant['name'])

@section('breadcrumbs')
    <a href="{{ route('admin.tenants') }}" class="text-mist-500 hover:text-brand-600 dark:text-mist-400 dark:hover:text-brand-300">المستأجرون</a>
    <span class="mx-1.5 text-mist-300 dark:text-mist-600">/</span>
    <span class="text-ink-700 dark:text-mist-200">{{ $tenant['name'] }}</span>
@endsection

@section('content')
    <div x-data="{ modal: null }">
        {{-- Header --}}
        <div class="flex flex-col gap-4 rounded-2xl border border-mist-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between dark:border-ink-600 dark:bg-ink-800">
            <div class="flex items-center gap-4">
                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-brand-500/15 font-display text-xl font-medium text-brand-600 dark:text-brand-300">
                    {{ mb_substr($tenant['name'], 0, 1) }}
                </span>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-display text-xl font-medium text-ink-900 dark:text-ink-50">{{ $tenant['name'] }}</h2>
                        <x-admin.status-badge :status="$tenant['status']" />
                    </div>
                    <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">{{ $tenant['slug'] }}.mada.app · انضم في {{ $tenant['member_since'] }}</p>
                </div>
            </div>

            {{-- Legal status transitions (only valid next states are shown) --}}
            <div class="flex flex-wrap items-center gap-2">
                @switch($tenant['status'])
                    @case('pending_approval')
                        <button type="button" @click="modal = 'approve'" class="rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-glow transition duration-200 hover:bg-brand-600 active:scale-95">تفعيل</button>
                        <button type="button" @click="modal = 'reject'" class="rounded-xl border border-mist-200 px-4 py-2 text-sm font-semibold text-mist-600 transition duration-200 hover:border-danger-solid hover:text-danger-solid active:scale-95 dark:border-ink-600 dark:text-mist-300">رفض</button>
                        @break
                    @case('active')
                        <button type="button" @click="modal = 'suspend'" class="rounded-xl border border-mist-200 px-4 py-2 text-sm font-semibold text-mist-600 transition duration-200 hover:border-danger-solid hover:text-danger-solid active:scale-95 dark:border-ink-600 dark:text-mist-300">إيقاف</button>
                        @break
                    @case('suspended')
                        <button type="button" @click="modal = 'reactivate'" class="rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-glow transition duration-200 hover:bg-brand-600 active:scale-95">إعادة تفعيل</button>
                        @break
                    @case('cancelled')
                        <span class="rounded-xl bg-mist-100 px-4 py-2 text-sm font-medium text-mist-500 dark:bg-ink-700 dark:text-mist-400">عرض فقط · يُحذف خلال 90 يومًا</span>
                        @break
                @endswitch
            </div>
        </div>

        {{--
            Why this tenant is not live. Reads from the columns the actions
            write, so the console can always answer "why is this customer
            locked out" without anyone opening the audit log.
        --}}
        @if ($tenant['suspension'])
            <div class="mt-6 rounded-2xl border border-danger-solid/30 bg-danger-solid/5 p-4">
                <div class="flex items-start gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 shrink-0 text-danger-solid" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                    <div class="min-w-0">
                        <h3 class="font-display text-base font-medium text-danger-solid">الحساب موقوف</h3>
                        <p class="mt-1.5 whitespace-pre-line text-sm text-ink-700 dark:text-mist-200">{{ $tenant['suspension']['reason'] }}</p>
                        <p class="mt-2 text-xs text-mist-500 dark:text-mist-400">
                            أوقفه {{ $tenant['suspension']['by'] }} · {{ $tenant['suspension']['at'] }}
                        </p>
                    </div>
                </div>
            </div>
        @elseif ($tenant['status'] === 'rejected' && $tenant['rejection_reason'])
            <div class="mt-6 rounded-2xl border border-rose-500/30 bg-rose-500/5 p-4">
                <div class="flex items-start gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 shrink-0 text-rose-600 dark:text-rose-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    <div class="min-w-0">
                        <h3 class="font-display text-base font-medium text-rose-600 dark:text-rose-400">طلب مرفوض</h3>
                        <p class="mt-1.5 whitespace-pre-line text-sm text-ink-700 dark:text-mist-200">{{ $tenant['rejection_reason'] }}</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Marketing opt-in (docs/ADMIN_CMS_ANALYSIS.md) --}}
        <div class="mt-6 rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <h3 class="font-display text-base font-medium text-ink-900 dark:text-ink-50">الظهور في التسويق</h3>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">تفعيل ظهور المستأجر في شريط الشركاء ورفع شعار التسويق.</p>

            @if ($tenant['marketing']['persistable'])
                <form method="POST" action="{{ route('admin.tenants.marketing', $tenant['slug']) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <label class="flex items-center justify-between gap-4 rounded-xl border border-mist-200 px-4 py-3 dark:border-ink-600">
                        <span class="text-sm font-medium text-ink-700 dark:text-mist-200">إظهار على صفحة التسويق</span>
                        <input type="checkbox" name="show_on_marketing" value="1" @checked(old('show_on_marketing', $tenant['marketing']['show_on_marketing'])) class="rounded border-mist-300 text-brand-500 focus:ring-brand-500">
                    </label>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200">شعار التسويق</label>
                        @if ($tenant['marketing']['logo_url'])
                            <div class="mb-2 flex items-center gap-3">
                                <img src="{{ $tenant['marketing']['logo_url'] }}" alt="" class="h-10 w-auto rounded-lg border border-mist-200 dark:border-ink-600">
                                <label class="inline-flex items-center gap-2 text-xs text-mist-500">
                                    <input type="checkbox" name="remove_logo" value="1" class="rounded border-mist-300 text-danger-solid">
                                    إزالة الشعار الحالي
                                </label>
                            </div>
                        @endif
                        <input type="file" name="marketing_logo" accept="image/*" class="w-full rounded-xl border border-mist-200 bg-white px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900">
                        <input type="text" name="alt_text" value="{{ old('alt_text') }}" placeholder="نص بديل للشعار" class="mt-2 w-full rounded-xl border border-mist-200 bg-white px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900">
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-glow transition hover:bg-brand-600">حفظ إعدادات التسويق</button>
                    </div>
                </form>
            @else
                <p class="mt-4 rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-700 dark:text-amber-300">
                    لا يوجد سجل مستأجر حقيقي بهذا المعرّف في قاعدة البيانات بعد — أنشئ المستأجر أولًا لتفعيل حفظ التسويق.
                </p>
            @endif
        </div>

        {{-- Stat cards --}}
        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-admin.stat-card
                label="الموظفون"
                :value="(string) $tenant['employees']"
                icon='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Z" /></svg>'
            />
            <x-admin.stat-card
                label="الأقسام"
                :value="(string) $tenant['departments']"
                icon='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" /></svg>'
            />
            <x-admin.stat-card
                label="الخطة الحالية"
                :value="$tenant['plan']"
                icon='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" /></svg>'
            />
            <x-admin.stat-card
                label="عضو منذ"
                :value="$tenant['member_since']"
                icon='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>'
            />
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
            {{-- Usage snapshot vs plan limits --}}
            <div class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm lg:col-span-2 dark:border-ink-600 dark:bg-ink-800">
                <div class="flex items-center justify-between">
                    <h3 class="font-display text-base font-medium text-ink-900 dark:text-ink-50">الاستهلاك مقابل حدود الخطة</h3>
                    <span class="rounded-md bg-mist-100 px-2 py-0.5 text-xs font-semibold uppercase text-mist-500 dark:bg-ink-700 dark:text-mist-400">مرجعي · المرحلة 4</span>
                </div>

                <div class="mt-5 space-y-5">
                    @foreach ($tenant['usage'] as $usage)
                        @php
                            $pct = $usage['limit'] > 0 ? min(100, round(($usage['used'] / $usage['limit']) * 100)) : 0;
                            $bar = $pct >= 90 ? 'bg-danger-solid' : ($pct >= 75 ? 'bg-amber-500' : 'bg-brand-500');
                        @endphp
                        <div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium text-ink-700 dark:text-mist-200">{{ $usage['label'] }}</span>
                                <span class="text-mist-500 dark:text-mist-400">{{ $usage['used'] }} / {{ $usage['limit'] }}</span>
                            </div>
                            <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-mist-100 dark:bg-ink-700">
                                <div class="h-full rounded-full {{ $bar }} transition-all duration-500 ease-out" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Owner / CEO profile --}}
            <div class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <h3 class="font-display text-base font-medium text-ink-900 dark:text-ink-50">مالك الحساب</h3>

                <div class="mt-4 flex items-center gap-3">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-brand-500/15 font-display text-lg font-medium text-brand-600 dark:text-brand-300">
                        {{ mb_substr($tenant['owner']['name'], 0, 1) }}
                    </span>
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-ink-900 dark:text-ink-50">{{ $tenant['owner']['name'] }}</p>
                        <p class="truncate text-xs text-mist-500 dark:text-mist-400">{{ $tenant['owner']['email'] }}</p>
                    </div>
                </div>

                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-mist-500 dark:text-mist-400">حالة البريد</dt>
                        <dd>
                            @if ($tenant['owner']['verified'])
                                <span class="inline-flex items-center gap-1.5 rounded-md bg-brand-500/10 px-2.5 py-1 text-xs font-medium text-brand-600 dark:text-brand-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                    موثّق
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-md bg-amber-500/10 px-2.5 py-1 text-xs font-medium text-amber-600 dark:text-amber-400">غير موثّق</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-mist-500 dark:text-mist-400">آخر دخول</dt>
                        <dd class="font-medium text-ink-700 dark:text-mist-200">{{ $tenant['owner']['last_login'] }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Tenant audit log slice --}}
            <div class="rounded-2xl border border-mist-200 bg-white shadow-sm lg:col-span-2 dark:border-ink-600 dark:bg-ink-800">
                <div class="flex items-center justify-between border-b border-mist-100 px-5 py-4 dark:border-ink-700">
                    <h3 class="font-display text-base font-medium text-ink-900 dark:text-ink-50">سجل نشاط المستأجر</h3>
                    <a href="{{ Route::has('admin.audit-log') ? route('admin.audit-log') : '#' }}" class="text-sm font-medium text-brand-600 hover:underline dark:text-brand-300">عرض في السجل الكامل</a>
                </div>
                <ul class="divide-y divide-mist-100 dark:divide-ink-700">
                    @foreach ($tenant['audit'] as $entry)
                        <li class="flex items-center justify-between gap-3 px-5 py-3">
                            <div class="flex items-center gap-3">
                                <span class="h-2 w-2 shrink-0 rounded-md bg-mist-300 dark:bg-mist-600"></span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm text-ink-700 dark:text-mist-200">{{ $entry['action'] }}</p>
                                    <p class="truncate text-xs text-mist-400 dark:text-mist-500">{{ $entry['actor'] }}</p>
                                </div>
                            </div>
                            <span class="shrink-0 text-xs text-mist-400 dark:text-mist-500">{{ $entry['time'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Danger zone --}}
            <div class="rounded-2xl border border-danger-solid/30 bg-danger-solid/5 p-4 shadow-sm">
                <h3 class="font-display text-base font-medium text-danger-solid">منطقة الخطر</h3>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">إجراءات حسّاسة تُسجَّل في سجل النشاط.</p>

                <div class="mt-4 space-y-2">
                    @if ($tenant['status'] === 'active')
                        <button type="button" @click="modal = 'suspend'" class="w-full rounded-xl border border-danger-solid/40 px-4 py-2 text-sm font-semibold text-danger-solid transition duration-200 hover:bg-danger-solid/10 active:scale-[0.98]">إيقاف المستأجر</button>
                    @endif
                    @if ($tenant['status'] !== 'cancelled')
                        <button type="button" @click="modal = 'cancel'" class="w-full rounded-xl border border-danger-solid/40 px-4 py-2 text-sm font-semibold text-danger-solid transition duration-200 hover:bg-danger-solid/10 active:scale-[0.98]">إلغاء الحساب</button>
                    @endif

                    {{-- Impersonate — Phase 4 (docs/DEVELOPMENT_ROADMAP.md), disabled with a visible tag --}}
                    <button type="button" disabled title="يتوفّر في المرحلة 4" class="flex w-full cursor-not-allowed items-center justify-between gap-2 rounded-xl border border-mist-200 px-4 py-2 text-sm font-semibold text-mist-400 dark:border-ink-600 dark:text-mist-500">
                        <span class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                            انتحال شخصية المستأجر
                        </span>
                        <span class="rounded-md bg-mist-100 px-2 py-0.5 text-xs font-semibold uppercase tracking-wide text-mist-500 dark:bg-ink-700 dark:text-mist-400">Phase 4</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Confirm modals --}}
        <div
            x-show="modal !== null"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <div @click="modal = null" class="absolute inset-0 bg-ink-950/60 backdrop-blur-sm"></div>

            <div
                x-show="modal !== null"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                class="relative w-full max-w-md rounded-2xl border border-mist-200 bg-white p-6 shadow-xl dark:border-ink-600 dark:bg-ink-800"
            >
                {{--
                    Every modal below was previously a decorative shell whose
                    "تأكيد" button only ran `modal = null`. On a screen that
                    renders a real customer record, a confirm button that does
                    nothing reads as a FAILED action rather than an unbuilt one,
                    so each is now either a real POST or says plainly that it is
                    not available yet.
                --}}

                {{-- Approve — real POST, with plan confirmation --}}
                <form x-show="modal === 'approve'" method="POST" action="{{ route('admin.tenants.approve', $tenant['slug']) }}">
                    @csrf
                    <h3 class="font-display text-lg font-medium text-ink-900 dark:text-ink-50">تفعيل المستأجر</h3>
                    <p class="mt-2 text-sm text-mist-500 dark:text-mist-400">سيتم تفعيل <span class="font-semibold text-ink-700 dark:text-mist-200">{{ $tenant['name'] }}</span> وفتح لوحة التحكم الكاملة له، مع إرسال إشعار بالبريد إلى مالك الحساب.</p>

                    <label for="approve_plan_id" class="mt-4 block text-sm font-medium text-ink-700 dark:text-mist-200">الخطة المعتمدة</label>
                    <select id="approve_plan_id" name="plan_id" class="mt-1.5 w-full rounded-xl border border-mist-200 bg-white p-2.5 text-sm text-ink-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50">
                        <option value="">— إبقاء الخطة المختارة عند التسجيل —</option>
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}" @selected($tenant['plan_id'] === $plan->id)>{{ $plan->name }}</option>
                        @endforeach
                    </select>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <button type="button" @click="modal = null" class="rounded-xl px-4 py-2 text-sm font-semibold text-mist-600 transition duration-200 hover:bg-mist-100 dark:text-mist-300 dark:hover:bg-ink-700">إلغاء</button>
                        <button type="submit" class="rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-glow transition duration-200 hover:bg-brand-600 active:scale-95">تأكيد التفعيل</button>
                    </div>
                </form>

                {{-- Reject — real POST, reason required --}}
                <form x-show="modal === 'reject'" method="POST" action="{{ route('admin.tenants.reject', $tenant['slug']) }}">
                    @csrf
                    <h3 class="font-display text-lg font-medium text-ink-900 dark:text-ink-50">رفض المستأجر</h3>
                    <p class="mt-2 text-sm text-mist-500 dark:text-mist-400">يُرجى توضيح سبب رفض <span class="font-semibold text-ink-700 dark:text-mist-200">{{ $tenant['name'] }}</span>. النص <span class="font-semibold">يُرسل إلى مقدّم الطلب</span> ويُحفظ في سجل التدقيق.</p>
                    <textarea name="rejection_reason" rows="3" required minlength="10" maxlength="2000" placeholder="سبب الرفض..." class="mt-3 w-full rounded-xl border border-mist-200 bg-white p-3 text-sm text-ink-700 placeholder:text-mist-400 focus:border-danger-solid focus:outline-none focus:ring-2 focus:ring-danger-solid/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50"></textarea>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <button type="button" @click="modal = null" class="rounded-xl px-4 py-2 text-sm font-semibold text-mist-600 transition duration-200 hover:bg-mist-100 dark:text-mist-300 dark:hover:bg-ink-700">إلغاء</button>
                        <button type="submit" class="rounded-xl bg-danger-solid px-4 py-2 text-sm font-semibold text-white transition duration-200 hover:opacity-90 active:scale-95">تأكيد الرفض</button>
                    </div>
                </form>

                {{-- Suspend — real POST, reason required --}}
                <form x-show="modal === 'suspend'" method="POST" action="{{ route('admin.tenants.suspend', $tenant['slug']) }}">
                    @csrf
                    <h3 class="font-display text-lg font-medium text-ink-900 dark:text-ink-50">إيقاف المستأجر</h3>
                    <p class="mt-2 text-sm text-mist-500 dark:text-mist-400">سيفقد جميع مستخدمي <span class="font-semibold text-ink-700 dark:text-mist-200">{{ $tenant['name'] }}</span> الوصول إلى وحدات النظام فوراً. النص <span class="font-semibold">يُرسل إلى مالك الحساب</span> ويُحفظ في سجل التدقيق.</p>
                    <p class="mt-2 rounded-lg bg-mist-100 px-3 py-2 text-xs text-mist-600 dark:bg-ink-700 dark:text-mist-300">البيانات تبقى محفوظة بالكامل، والإجراء قابل للتراجع عبر إعادة التفعيل.</p>
                    <textarea name="suspension_reason" rows="3" required minlength="10" maxlength="2000" placeholder="سبب الإيقاف..." class="mt-3 w-full rounded-xl border border-mist-200 bg-white p-3 text-sm text-ink-700 placeholder:text-mist-400 focus:border-danger-solid focus:outline-none focus:ring-2 focus:ring-danger-solid/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50"></textarea>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <button type="button" @click="modal = null" class="rounded-xl px-4 py-2 text-sm font-semibold text-mist-600 transition duration-200 hover:bg-mist-100 dark:text-mist-300 dark:hover:bg-ink-700">إلغاء</button>
                        <button type="submit" class="rounded-xl bg-danger-solid px-4 py-2 text-sm font-semibold text-white transition duration-200 hover:opacity-90 active:scale-95">تأكيد الإيقاف</button>
                    </div>
                </form>

                {{-- Reactivate — real POST --}}
                <form x-show="modal === 'reactivate'" method="POST" action="{{ route('admin.tenants.reactivate', $tenant['slug']) }}">
                    @csrf
                    <h3 class="font-display text-lg font-medium text-ink-900 dark:text-ink-50">إعادة تفعيل المستأجر</h3>
                    <p class="mt-2 text-sm text-mist-500 dark:text-mist-400">سيستعيد مستخدمو <span class="font-semibold text-ink-700 dark:text-mist-200">{{ $tenant['name'] }}</span> الوصول الكامل فوراً، ويُرسَل إشعار بالبريد إلى مالك الحساب. سبب الإيقاف الحالي يُرفع من السجل.</p>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <button type="button" @click="modal = null" class="rounded-xl px-4 py-2 text-sm font-semibold text-mist-600 transition duration-200 hover:bg-mist-100 dark:text-mist-300 dark:hover:bg-ink-700">إلغاء</button>
                        <button type="submit" class="rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-glow transition duration-200 hover:bg-brand-600 active:scale-95">تأكيد إعادة التفعيل</button>
                    </div>
                </form>

                {{--
                    Cancel has no backend. Unlike suspension it is a customer-
                    initiated exit (ADR-04), so the Super Admin path to it is a
                    separate decision that has not been specified yet — a close
                    button, not a confirm button that silently does nothing.
                --}}
                <div x-show="modal === 'cancel'">
                    <h3 class="font-display text-lg font-medium text-ink-900 dark:text-ink-50">إلغاء الحساب</h3>
                    <p class="mt-2 text-sm text-mist-500 dark:text-mist-400">إلغاء الحسابات غير متاح بعد من هذه الشاشة — الإيقاف المؤقت هو الإجراء المتاح حالياً، وهو قابل للتراجع.</p>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <button type="button" @click="modal = null" class="rounded-xl px-4 py-2 text-sm font-semibold text-mist-600 transition duration-200 hover:bg-mist-100 dark:text-mist-300 dark:hover:bg-ink-700">إغلاق</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
