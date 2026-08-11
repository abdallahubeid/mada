<x-layouts.app title="الموقع العام">
    @php
        $inputClass = 'w-full rounded-xl border border-mist-200 bg-white px-3 py-2.5 text-sm text-ink-700 shadow-sm transition placeholder:text-mist-400 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50 disabled:cursor-not-allowed disabled:opacity-60';
        $labelClass = 'mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200';
        $cardClass = 'rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800 sm:p-6';
        $errorClass = 'mt-1.5 text-xs text-danger-solid';
        $val = fn (string $key, mixed $default = '') => old($key, $portalSettings->{$key} ?? $default);
        $tabs = [
            'general' => 'عام',
            'hero' => 'البطل',
            'about' => 'من نحن',
            'services' => 'الخدمات',
            'culture' => 'بيئة العمل',
            'stats' => 'الإحصائيات',
            'careers' => 'الوظائف',
            'faq' => 'الأسئلة',
            'cta' => 'CTA',
            'contact' => 'التواصل',
        ];
        $valuesRows = old('values_json', $portalSettings->values_json ?: [['title' => '', 'desc' => '']]);
        $servicesRows = old('services_json', $portalSettings->services_json ?: [['title' => '', 'description' => '', 'icon' => 'ops']]);
        $cultureRows = old('culture_perks_json', $portalSettings->culture_perks_json ?: [['title' => '', 'description' => '']]);
        $statsRows = old('stats_json', $portalSettings->stats_json ?: [['label' => '', 'value' => '', 'suffix' => '']]);
        $faqRows = old('faqs_json', $portalSettings->faqs_json ?: [['question' => '', 'answer' => '']]);
    @endphp

    <div class="mx-auto max-w-4xl space-y-6" x-data="{
        tab: 'general',
        values: @js($valuesRows),
        services: @js($servicesRows),
        culture: @js($cultureRows),
        stats: @js($statsRows),
        faqs: @js($faqRows),
    }">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">الموقع العام</h2>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">
                    تحكم بمحتوى بوابة التوظيف العامة لـ
                    <span class="font-medium text-ink-700 dark:text-mist-200">{{ $tenant?->name }}</span>
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($previewUrl)
                    <a
                        href="{{ $previewUrl }}"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center justify-center rounded-xl border border-mist-200 px-4 py-2 text-sm font-semibold text-ink-700 transition hover:border-emerald-400 hover:text-emerald-600 dark:border-ink-600 dark:text-mist-200 dark:hover:border-emerald-400 dark:hover:text-emerald-400"
                    >
                        معاينة الموقع
                    </a>
                @endif
            </div>
        </div>

        @unless ($canUpdate)
            <p class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">عرض فقط — ليس لديك صلاحية التعديل.</p>
        @endunless

        <div class="w-full overflow-x-auto">
            <div class="flex min-w-max items-center gap-1 border-b border-mist-200 dark:border-ink-700">
                @foreach ($tabs as $key => $label)
                    <button
                        type="button"
                        @click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}'
                            ? 'border-emerald-400 text-emerald-600 dark:text-emerald-400'
                            : 'border-transparent text-mist-500 hover:text-ink-700 dark:text-mist-400 dark:hover:text-mist-200'"
                        class="whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium transition"
                    >{{ $label }}</button>
                @endforeach
            </div>
        </div>

        <form method="POST" action="{{ route('settings.portal.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <fieldset @disabled(! $canUpdate) class="space-y-6">
                {{-- General --}}
                <div x-show="tab === 'general'" x-cloak class="{{ $cardClass }} space-y-4">
                    <h3 class="font-display text-lg font-semibold text-ink-900 dark:text-ink-50">الإعدادات العامة</h3>
                    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-mist-200 px-3 py-3 dark:border-ink-600">
                        <div>
                            <span class="block text-sm font-medium text-ink-700 dark:text-mist-200">تفعيل الموقع العام</span>
                            <span class="mt-0.5 block text-xs text-mist-500">عند الإيقاف تظهر صفحة صيانة للزوار (404).</span>
                        </div>
                        <input type="hidden" name="is_portal_enabled" value="0">
                        <input type="checkbox" name="is_portal_enabled" value="1" class="rounded border-mist-300 text-emerald-500 focus:ring-emerald-400" @checked((bool) $val('is_portal_enabled', true))>
                    </label>
                    <p class="text-xs text-mist-500">استخدم تبويبات الأقسام لتعديل النصوص والتفعيل لكل قسم على حدة.</p>
                </div>

                {{-- Hero --}}
                <div x-show="tab === 'hero'" x-cloak class="{{ $cardClass }} space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="font-display text-lg font-semibold text-ink-900 dark:text-ink-50">قسم البطل</h3>
                        <label class="flex items-center gap-2 text-sm text-ink-700 dark:text-mist-200">
                            <input type="hidden" name="is_hero_active" value="0">
                            <input type="checkbox" name="is_hero_active" value="1" class="rounded border-mist-300 text-emerald-500 focus:ring-emerald-400" @checked((bool) $val('is_hero_active', true))>
                            ظاهر
                        </label>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}" for="hero_badge_text">نص الشارة (badge_text)</label>
                        <input id="hero_badge_text" type="text" name="hero_badge_text" value="{{ $val('hero_badge_text') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}" for="hero_title">العنوان</label>
                        <input id="hero_title" type="text" name="hero_title" value="{{ $val('hero_title') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}" for="hero_subtitle">الوصف</label>
                        <textarea id="hero_subtitle" name="hero_subtitle" rows="3" class="{{ $inputClass }}">{{ $val('hero_subtitle') }}</textarea>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="{{ $labelClass }}" for="hero_primary_cta_text">نص الزر الأساسي</label>
                            <input id="hero_primary_cta_text" type="text" name="hero_primary_cta_text" value="{{ $val('hero_primary_cta_text') }}" class="{{ $inputClass }}">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="hero_primary_cta_url">رابط الزر الأساسي</label>
                            <input id="hero_primary_cta_url" type="text" dir="ltr" name="hero_primary_cta_url" value="{{ $val('hero_primary_cta_url') }}" placeholder="careers أو /path أو https://..." class="{{ $inputClass }}">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="hero_secondary_cta_text">نص الزر الثانوي</label>
                            <input id="hero_secondary_cta_text" type="text" name="hero_secondary_cta_text" value="{{ $val('hero_secondary_cta_text') }}" class="{{ $inputClass }}">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="hero_secondary_cta_url">رابط الزر الثانوي</label>
                            <input id="hero_secondary_cta_url" type="text" dir="ltr" name="hero_secondary_cta_url" value="{{ $val('hero_secondary_cta_url') }}" placeholder="contact" class="{{ $inputClass }}">
                        </div>
                    </div>
                </div>

                {{-- About --}}
                <div x-show="tab === 'about'" x-cloak class="{{ $cardClass }} space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="font-display text-lg font-semibold text-ink-900 dark:text-ink-50">من نحن والقيم</h3>
                        <label class="flex items-center gap-2 text-sm text-ink-700 dark:text-mist-200">
                            <input type="hidden" name="is_about_active" value="0">
                            <input type="checkbox" name="is_about_active" value="1" class="rounded border-mist-300 text-emerald-500 focus:ring-emerald-400" @checked((bool) $val('is_about_active', true))>
                            ظاهر
                        </label>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="{{ $labelClass }}" for="about_subtitle">عنوان فرعي</label>
                            <input id="about_subtitle" type="text" name="about_subtitle" value="{{ $val('about_subtitle') }}" class="{{ $inputClass }}">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="about_title">العنوان</label>
                            <input id="about_title" type="text" name="about_title" value="{{ $val('about_title') }}" class="{{ $inputClass }}">
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="{{ $labelClass }}" for="vision_text">الرؤية</label>
                            <textarea id="vision_text" name="vision_text" rows="3" class="{{ $inputClass }}">{{ $val('vision_text') }}</textarea>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="mission_text">الرسالة</label>
                            <textarea id="mission_text" name="mission_text" rows="3" class="{{ $inputClass }}">{{ $val('mission_text') }}</textarea>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <p class="{{ $labelClass }} mb-0">القيم</p>
                            @if ($canUpdate)
                                <button type="button" @click="values.push({ title: '', desc: '' })" class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">إضافة قيمة</button>
                            @endif
                        </div>
                        <template x-for="(row, index) in values" :key="'v'+index">
                            <div class="grid gap-2 rounded-xl border border-mist-100 p-3 sm:grid-cols-[1fr_2fr_auto] dark:border-ink-700">
                                <input type="text" :name="`values_json[${index}][title]`" x-model="row.title" placeholder="العنوان" class="{{ $inputClass }}">
                                <input type="text" :name="`values_json[${index}][desc]`" x-model="row.desc" placeholder="الوصف" class="{{ $inputClass }}">
                                @if ($canUpdate)
                                    <button type="button" @click="values.length === 1 ? values[0] = { title: '', desc: '' } : values.splice(index, 1)" class="rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600">حذف</button>
                                @endif
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Services --}}
                <div x-show="tab === 'services'" x-cloak class="{{ $cardClass }} space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="font-display text-lg font-semibold text-ink-900 dark:text-ink-50">الخدمات</h3>
                        <label class="flex items-center gap-2 text-sm text-ink-700 dark:text-mist-200">
                            <input type="hidden" name="is_services_active" value="0">
                            <input type="checkbox" name="is_services_active" value="1" class="rounded border-mist-300 text-emerald-500 focus:ring-emerald-400" @checked((bool) $val('is_services_active', true))>
                            ظاهر
                        </label>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="{{ $labelClass }}" for="services_subtitle">عنوان فرعي</label>
                            <input id="services_subtitle" type="text" name="services_subtitle" value="{{ $val('services_subtitle') }}" class="{{ $inputClass }}">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="services_title">العنوان</label>
                            <input id="services_title" type="text" name="services_title" value="{{ $val('services_title') }}" class="{{ $inputClass }}">
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <p class="{{ $labelClass }} mb-0">بطاقات الخدمات</p>
                            @if ($canUpdate)
                                <button type="button" @click="services.push({ title: '', description: '', icon: 'ops' })" class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">إضافة خدمة</button>
                            @endif
                        </div>
                        <template x-for="(row, index) in services" :key="'s'+index">
                            <div class="grid gap-2 rounded-xl border border-mist-100 p-3 sm:grid-cols-2 dark:border-ink-700">
                                <input type="text" :name="`services_json[${index}][title]`" x-model="row.title" placeholder="العنوان" class="{{ $inputClass }}">
                                <input type="text" :name="`services_json[${index}][icon]`" x-model="row.icon" placeholder="أيقونة (ops/tech/...)" dir="ltr" class="{{ $inputClass }}">
                                <input type="text" :name="`services_json[${index}][description]`" x-model="row.description" placeholder="الوصف" class="{{ $inputClass }} sm:col-span-2">
                                @if ($canUpdate)
                                    <button type="button" @click="services.length === 1 ? services[0] = { title: '', description: '', icon: 'ops' } : services.splice(index, 1)" class="rounded-xl border border-mist-200 px-3 py-2 text-sm sm:col-span-2 dark:border-ink-600">حذف</button>
                                @endif
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Culture --}}
                <div x-show="tab === 'culture'" x-cloak class="{{ $cardClass }} space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="font-display text-lg font-semibold text-ink-900 dark:text-ink-50">بيئة العمل والمزايا</h3>
                        <label class="flex items-center gap-2 text-sm text-ink-700 dark:text-mist-200">
                            <input type="hidden" name="is_culture_active" value="0">
                            <input type="checkbox" name="is_culture_active" value="1" class="rounded border-mist-300 text-emerald-500 focus:ring-emerald-400" @checked((bool) $val('is_culture_active', true))>
                            ظاهر
                        </label>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="{{ $labelClass }}" for="culture_subtitle">عنوان فرعي</label>
                            <input id="culture_subtitle" type="text" name="culture_subtitle" value="{{ $val('culture_subtitle') }}" class="{{ $inputClass }}">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="culture_title">العنوان</label>
                            <input id="culture_title" type="text" name="culture_title" value="{{ $val('culture_title') }}" class="{{ $inputClass }}">
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <p class="{{ $labelClass }} mb-0">المزايا</p>
                            @if ($canUpdate)
                                <button type="button" @click="culture.push({ title: '', description: '' })" class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">إضافة ميزة</button>
                            @endif
                        </div>
                        <template x-for="(row, index) in culture" :key="'c'+index">
                            <div class="grid gap-2 rounded-xl border border-mist-100 p-3 sm:grid-cols-[1fr_2fr_auto] dark:border-ink-700">
                                <input type="text" :name="`culture_perks_json[${index}][title]`" x-model="row.title" placeholder="العنوان" class="{{ $inputClass }}">
                                <input type="text" :name="`culture_perks_json[${index}][description]`" x-model="row.description" placeholder="الوصف" class="{{ $inputClass }}">
                                @if ($canUpdate)
                                    <button type="button" @click="culture.length === 1 ? culture[0] = { title: '', description: '' } : culture.splice(index, 1)" class="rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600">حذف</button>
                                @endif
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Stats --}}
                <div x-show="tab === 'stats'" x-cloak class="{{ $cardClass }} space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="font-display text-lg font-semibold text-ink-900 dark:text-ink-50">الإحصائيات</h3>
                        <label class="flex items-center gap-2 text-sm text-ink-700 dark:text-mist-200">
                            <input type="hidden" name="is_stats_active" value="0">
                            <input type="checkbox" name="is_stats_active" value="1" class="rounded border-mist-300 text-emerald-500 focus:ring-emerald-400" @checked((bool) $val('is_stats_active', true))>
                            ظاهر
                        </label>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}" for="stats_title">عنوان القسم</label>
                        <input id="stats_title" type="text" name="stats_title" value="{{ $val('stats_title') }}" class="{{ $inputClass }}">
                    </div>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <p class="{{ $labelClass }} mb-0">العدادات</p>
                            @if ($canUpdate)
                                <button type="button" @click="stats.push({ label: '', value: '', suffix: '' })" class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">إضافة رقم</button>
                            @endif
                        </div>
                        <template x-for="(row, index) in stats" :key="'st'+index">
                            <div class="grid gap-2 rounded-xl border border-mist-100 p-3 sm:grid-cols-[2fr_1fr_1fr_auto] dark:border-ink-700">
                                <input type="text" :name="`stats_json[${index}][label]`" x-model="row.label" placeholder="التسمية" class="{{ $inputClass }}">
                                <input type="text" :name="`stats_json[${index}][value]`" x-model="row.value" placeholder="القيمة" dir="ltr" class="{{ $inputClass }}">
                                <input type="text" :name="`stats_json[${index}][suffix]`" x-model="row.suffix" placeholder="+" dir="ltr" class="{{ $inputClass }}">
                                @if ($canUpdate)
                                    <button type="button" @click="stats.length === 1 ? stats[0] = { label: '', value: '', suffix: '' } : stats.splice(index, 1)" class="rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600">حذف</button>
                                @endif
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Careers --}}
                <div x-show="tab === 'careers'" x-cloak class="{{ $cardClass }} space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="font-display text-lg font-semibold text-ink-900 dark:text-ink-50">قسم الوظائف</h3>
                        <label class="flex items-center gap-2 text-sm text-ink-700 dark:text-mist-200">
                            <input type="hidden" name="is_careers_active" value="0">
                            <input type="checkbox" name="is_careers_active" value="1" class="rounded border-mist-300 text-emerald-500 focus:ring-emerald-400" @checked((bool) $val('is_careers_active', true))>
                            ظاهر
                        </label>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}" for="careers_badge_text">نص الشارة</label>
                        <input id="careers_badge_text" type="text" name="careers_badge_text" value="{{ $val('careers_badge_text') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}" for="careers_title">العنوان</label>
                        <input id="careers_title" type="text" name="careers_title" value="{{ $val('careers_title') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}" for="careers_subtitle">الوصف</label>
                        <textarea id="careers_subtitle" name="careers_subtitle" rows="2" class="{{ $inputClass }}">{{ $val('careers_subtitle') }}</textarea>
                    </div>
                </div>

                {{-- FAQ --}}
                <div x-show="tab === 'faq'" x-cloak class="{{ $cardClass }} space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="font-display text-lg font-semibold text-ink-900 dark:text-ink-50">الأسئلة الشائعة</h3>
                        <label class="flex items-center gap-2 text-sm text-ink-700 dark:text-mist-200">
                            <input type="hidden" name="is_faq_active" value="0">
                            <input type="checkbox" name="is_faq_active" value="1" class="rounded border-mist-300 text-emerald-500 focus:ring-emerald-400" @checked((bool) $val('is_faq_active', true))>
                            ظاهر
                        </label>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="{{ $labelClass }}" for="faq_subtitle">عنوان فرعي</label>
                            <input id="faq_subtitle" type="text" name="faq_subtitle" value="{{ $val('faq_subtitle') }}" class="{{ $inputClass }}">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="faq_title">العنوان</label>
                            <input id="faq_title" type="text" name="faq_title" value="{{ $val('faq_title') }}" class="{{ $inputClass }}">
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <p class="{{ $labelClass }} mb-0">الأسئلة والأجوبة</p>
                            @if ($canUpdate)
                                <button type="button" @click="faqs.push({ question: '', answer: '' })" class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">إضافة سؤال</button>
                            @endif
                        </div>
                        <template x-for="(row, index) in faqs" :key="'f'+index">
                            <div class="space-y-2 rounded-xl border border-mist-100 p-3 dark:border-ink-700">
                                <input type="text" :name="`faqs_json[${index}][question]`" x-model="row.question" placeholder="السؤال" class="{{ $inputClass }}">
                                <textarea :name="`faqs_json[${index}][answer]`" x-model="row.answer" rows="2" placeholder="الجواب" class="{{ $inputClass }}"></textarea>
                                @if ($canUpdate)
                                    <button type="button" @click="faqs.length === 1 ? faqs[0] = { question: '', answer: '' } : faqs.splice(index, 1)" class="rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600">حذف</button>
                                @endif
                            </div>
                        </template>
                    </div>
                </div>

                {{-- CTA --}}
                <div x-show="tab === 'cta'" x-cloak class="{{ $cardClass }} space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="font-display text-lg font-semibold text-ink-900 dark:text-ink-50">شريط الدعوة للإجراء</h3>
                        <label class="flex items-center gap-2 text-sm text-ink-700 dark:text-mist-200">
                            <input type="hidden" name="is_cta_active" value="0">
                            <input type="checkbox" name="is_cta_active" value="1" class="rounded border-mist-300 text-emerald-500 focus:ring-emerald-400" @checked((bool) $val('is_cta_active', true))>
                            ظاهر
                        </label>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}" for="cta_title">العنوان</label>
                        <input id="cta_title" type="text" name="cta_title" value="{{ $val('cta_title') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}" for="cta_subtitle">الوصف</label>
                        <textarea id="cta_subtitle" name="cta_subtitle" rows="2" class="{{ $inputClass }}">{{ $val('cta_subtitle') }}</textarea>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}" for="cta_button_text">نص الزر (cta_button_text)</label>
                        <input id="cta_button_text" type="text" name="cta_button_text" value="{{ $val('cta_button_text') }}" class="{{ $inputClass }}">
                    </div>
                </div>

                {{-- Contact --}}
                <div x-show="tab === 'contact'" x-cloak class="{{ $cardClass }} space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="font-display text-lg font-semibold text-ink-900 dark:text-ink-50">التواصل والخريطة</h3>
                        <label class="flex items-center gap-2 text-sm text-ink-700 dark:text-mist-200">
                            <input type="hidden" name="is_contact_active" value="0">
                            <input type="checkbox" name="is_contact_active" value="1" class="rounded border-mist-300 text-emerald-500 focus:ring-emerald-400" @checked((bool) $val('is_contact_active', true))>
                            ظاهر
                        </label>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="{{ $labelClass }}" for="contact_email">البريد</label>
                            <input id="contact_email" type="email" dir="ltr" name="contact_email" value="{{ $val('contact_email') }}" class="{{ $inputClass }}">
                            @error('contact_email')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="contact_phone">الهاتف</label>
                            <input id="contact_phone" type="text" dir="ltr" name="contact_phone" value="{{ $val('contact_phone') }}" class="{{ $inputClass }}">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="{{ $labelClass }}" for="contact_address">العنوان</label>
                            <input id="contact_address" type="text" name="contact_address" value="{{ $val('contact_address') }}" class="{{ $inputClass }}">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="office_hours">ساعات العمل</label>
                            <input id="office_hours" type="text" name="office_hours" value="{{ $val('office_hours') }}" class="{{ $inputClass }}">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="map_embed_url">رابط تضمين الخريطة</label>
                            <input id="map_embed_url" type="text" dir="ltr" name="map_embed_url" value="{{ $val('map_embed_url') }}" class="{{ $inputClass }}">
                        </div>
                    </div>
                </div>
            </fieldset>

            @can('tenant.settings.update')
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-emerald-400 px-5 py-2.5 text-sm font-semibold text-emerald-900 shadow-glow transition hover:bg-emerald-300 active:scale-[0.98]">
                        حفظ إعدادات الموقع
                    </button>
                </div>
            @endcan
        </form>
    </div>
</x-layouts.app>
