@extends('layouts.admin')

@section('title', 'الخطط وحدود الميزات')

@section('breadcrumbs')
    <span class="text-mist-500 dark:text-mist-400">المستأجرون</span>
    <span class="mx-1.5 text-mist-300 dark:text-mist-600">/</span>
    <span class="text-ink-700 dark:text-mist-200">الخطط والحدود</span>
@endsection

@section('content')
    @php
        $inputClass = 'w-full rounded-xl border border-mist-200 bg-white px-3 py-2.5 text-sm text-ink-700 placeholder:text-mist-400 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50';
        $labelClass = 'mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200';
        $blankPlan = [
            'id' => null,
            'key' => '',
            'name' => '',
            'tagline' => '',
            'price_monthly' => 0,
            'price_yearly' => 0,
            'currency' => 'USD',
            'cta_label' => 'ابدأ الآن',
            'cta_url' => '/register',
            'is_highlighted' => false,
            'is_active' => true,
            'sort_order' => 0,
            'tenants' => 0,
            'features_text' => '',
        ];
    @endphp

    <div x-data="{
        open: false,
        isNew: false,
        form: {},
        edit(plan) { this.form = JSON.parse(JSON.stringify(plan)); this.isNew = false; this.open = true; },
        create() { this.form = JSON.parse(JSON.stringify(@js($blankPlan))); this.isNew = true; this.open = true; },
    }">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">الخطط وحدود الميزات</h2>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">تُحفظ مباشرة في جداول الخطط وميزاتها.</p>
            </div>

            <button type="button" @click="create()" class="inline-flex items-center gap-2 rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-glow transition duration-200 hover:bg-emerald-300 active:scale-95">
                إضافة خطة
            </button>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($plans as $plan)
                <div @class([
                    'relative flex flex-col rounded-2xl border bg-white p-5 shadow-sm transition duration-200 dark:bg-ink-800',
                    'border-emerald-400/50 ring-1 ring-emerald-400/30' => $plan['popular'],
                    'border-mist-200 dark:border-ink-600' => ! $plan['popular'],
                ])>
                    @if ($plan['popular'])
                        <span class="absolute -top-2.5 end-5 rounded-full bg-emerald-400 px-2.5 py-1 text-[10px] font-bold text-emerald-900">مميزة</span>
                    @endif

                    <div class="flex items-center justify-between gap-2">
                        <h3 class="font-display text-lg font-bold text-ink-900 dark:text-ink-50">{{ $plan['name'] }}</h3>
                        <span @class([
                            'rounded-full px-2 py-0.5 text-[10px] font-semibold',
                            'bg-emerald-500/10 text-emerald-600' => $plan['is_active'],
                            'bg-mist-100 text-mist-500' => ! $plan['is_active'],
                        ])>{{ $plan['is_active'] ? 'نشطة' : 'غير نشطة' }}</span>
                    </div>

                    <p class="mt-1 text-sm text-mist-500">{{ $plan['tagline'] }}</p>

                    <div class="mt-3 flex items-baseline gap-1">
                        <span class="font-display text-3xl font-bold text-ink-900 dark:text-ink-50">${{ number_format((float) $plan['price_monthly']) }}</span>
                        <span class="text-sm text-mist-500">/ شهريًا</span>
                    </div>

                    <ul class="mt-4 space-y-1 border-t border-mist-100 pt-4 text-sm dark:border-ink-700">
                        @foreach (array_slice(explode("\n", $plan['features_text']), 0, 4) as $feature)
                            @if (trim($feature) !== '')
                                <li class="text-mist-600 dark:text-mist-300">{{ trim($feature) }}</li>
                            @endif
                        @endforeach
                    </ul>

                    <div class="mt-5 flex items-center gap-2 border-t border-mist-100 pt-4 dark:border-ink-700">
                        <button type="button" @click="edit(@js($plan))" class="flex-1 rounded-xl border border-mist-200 px-4 py-2 text-sm font-semibold text-ink-700 transition hover:border-emerald-400 hover:text-emerald-600 dark:border-ink-600 dark:text-mist-200">تعديل</button>
                        <form method="POST" action="{{ route('admin.plans.destroy', $plan['id']) }}" data-swal-confirm data-swal-title="أرشفة هذه الخطة؟">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-xl border border-mist-200 px-3 py-2 text-sm font-semibold text-mist-500 transition hover:border-danger-solid hover:text-danger-solid dark:border-ink-600">أرشفة</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <div x-show="open" x-cloak class="fixed inset-0 z-50 bg-ink-950/60 backdrop-blur-sm" @click="open = false"></div>

        <aside
            x-show="open"
            x-cloak
            class="fixed inset-y-0 end-0 z-50 flex w-full max-w-md flex-col border-s border-mist-200 bg-white shadow-xl dark:border-ink-600 dark:bg-ink-800"
        >
            <form
                method="POST"
                :action="isNew ? @js(route('admin.plans.store')) : (`{{ url('/admin/plans') }}/` + form.id)"
                class="flex h-full flex-col"
            >
                @csrf
                <template x-if="!isNew">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="flex items-center justify-between border-b border-mist-100 px-5 py-4 dark:border-ink-700">
                    <h3 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">
                        <span x-show="isNew">إضافة خطة جديدة</span>
                        <span x-show="!isNew">تعديل خطة</span>
                    </h3>
                    <button type="button" @click="open = false" class="rounded-lg p-1 text-mist-400" aria-label="إغلاق">×</button>
                </div>

                <div class="flex-1 space-y-4 overflow-y-auto p-5">
                    <div>
                        <label class="{{ $labelClass }}">اسم الخطة</label>
                        <input type="text" name="name" x-model="form.name" class="{{ $inputClass }}" required>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">المعرّف (slug)</label>
                        <input type="text" dir="ltr" name="slug" x-model="form.key" class="{{ $inputClass }}" required>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">الوصف القصير</label>
                        <input type="text" name="tagline" x-model="form.tagline" class="{{ $inputClass }}">
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="{{ $labelClass }}">السعر الشهري</label>
                            <input type="number" step="0.01" min="0" name="price_monthly" x-model="form.price_monthly" class="{{ $inputClass }}">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">السعر السنوي</label>
                            <input type="number" step="0.01" min="0" name="price_yearly" x-model="form.price_yearly" class="{{ $inputClass }}">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="{{ $labelClass }}">العملة</label>
                            <input type="text" dir="ltr" name="currency" x-model="form.currency" maxlength="3" class="{{ $inputClass }}">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">ترتيب العرض</label>
                            <input type="number" name="sort_order" x-model="form.sort_order" class="{{ $inputClass }}">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="{{ $labelClass }}">نص الزر</label>
                            <input type="text" name="cta_label" x-model="form.cta_label" class="{{ $inputClass }}">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">رابط الزر</label>
                            <input type="text" dir="ltr" name="cta_url" x-model="form.cta_url" class="{{ $inputClass }}">
                        </div>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">المزايا (سطر لكل ميزة)</label>
                        <textarea name="features_text" rows="6" x-model="form.features_text" class="{{ $inputClass }}"></textarea>
                    </div>
                    <label class="flex items-center justify-between rounded-xl border border-mist-200 px-3 py-2.5 dark:border-ink-600">
                        <span class="text-sm">خطة مميزة</span>
                        <input type="checkbox" name="is_highlighted" value="1" x-model="form.is_highlighted" class="rounded border-mist-300 text-emerald-500 focus:ring-emerald-400">
                    </label>
                    <label class="flex items-center justify-between rounded-xl border border-mist-200 px-3 py-2.5 dark:border-ink-600">
                        <span class="text-sm">نشطة للعرض العام</span>
                        <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="rounded border-mist-300 text-emerald-500 focus:ring-emerald-400">
                    </label>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-mist-100 px-5 py-4 dark:border-ink-700">
                    <button type="button" @click="open = false" class="rounded-xl px-4 py-2 text-sm font-semibold text-mist-600">إلغاء</button>
                    <button type="submit" class="rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-glow">حفظ الخطة</button>
                </div>
            </form>
        </aside>
    </div>
@endsection
