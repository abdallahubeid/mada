@php
    /*
     * Tenant sign-up as a three-step wizard, in the public site's Andromeda
     * identity (docs/DESIGN_SYSTEM.md §1.3).
     *
     * All three steps live in ONE form and post together; the steps are only
     * visual. When validation fails, the page reopens on the first step that
     * holds an error so the visitor lands on the field that needs fixing.
     */
    $initialStep = 1;

    if ($errors->hasAny(['company_name', 'company_slug', 'industry', 'team_size'])) {
        $initialStep = 2;
    } elseif ($errors->hasAny(['plan', 'terms'])) {
        $initialStep = 3;
    }

    $steps = ['بيانات الحساب', 'بيانات المؤسسة', 'الخطة والمراجعة'];

    // aria-invalid / aria-describedby for a field, so the error is read with it.
    $invalid = fn (string $field): string => $errors->has($field)
        ? 'aria-invalid="true" aria-describedby="'.$field.'-error"'
        : '';
@endphp

<x-layouts.auth-centered wide title="إنشاء حساب — مدى">
    <div
        x-data="{
            step: {{ $initialStep }},
            slugTouched: {{ old('company_slug') ? 'true' : 'false' }},
            slugify(value) {
                return value.toString().trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
            },
        }"
        class="site-card p-6 sm:p-8"
    >
        <div class="text-center">
            <h1 class="site-section-title site-h3">ابدأ مجاناً</h1>
            <p class="mt-8">ثلاث خطوات قصيرة، ولا حاجة لبطاقة بنكية.</p>
        </div>

        {{-- Stepper: a real sequence, so numbered. --}}
        <ol class="mt-8 mb-8 flex items-center" aria-label="خطوات إنشاء الحساب">
            @foreach ($steps as $index => $label)
                @php $n = $index + 1; @endphp
                <li class="flex items-center {{ $index < count($steps) - 1 ? 'flex-1' : '' }}" :aria-current="step === {{ $n }} ? 'step' : 'false'">
                    <div class="flex flex-col items-center gap-2">
                        <span
                            class="site-step"
                            :class="{ 'is-current': step === {{ $n }}, 'is-done': step > {{ $n }} }"
                        >
                            <svg x-show="step > {{ $n }}" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            <span x-show="step <= {{ $n }}">{{ $n }}</span>
                        </span>
                        <span
                            :class="step === {{ $n }} ? 'text-site-primary-ink' : ''"
                            class="hidden text-sm font-medium sm:block"
                        >{{ $label }}</span>
                    </div>

                    @if ($index < count($steps) - 1)
                        <div
                            :class="step > {{ $n }} ? 'bg-site-primary' : 'bg-site-border'"
                            class="mx-3 h-0.5 flex-1 rounded-full transition duration-300 sm:-mt-7"
                            aria-hidden="true"
                        ></div>
                    @endif
                </li>
            @endforeach
        </ol>

        <form method="POST" action="{{ route('register') }}" class="space-y-6" novalidate>
            @csrf

            {{-- Step 1: account --}}
            <fieldset x-show="step === 1" x-cloak class="space-y-5">
                <legend class="sr-only">{{ $steps[0] }}</legend>

                <div>
                    <label for="name" class="site-label">الاسم الكامل</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" autofocus class="site-input" {!! $invalid('name') !!} />
                    @error('name') <p id="name-error" class="site-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="site-label">البريد الإلكتروني للعمل</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" placeholder="name@company.com" dir="ltr" class="site-input text-start" {!! $invalid('email') !!} />
                    @error('email') <p id="email-error" class="site-error">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="password" class="site-label">كلمة المرور</label>
                        <input id="password" name="password" type="password" autocomplete="new-password" class="site-input" {!! $invalid('password') !!} />
                        @error('password') <p id="password-error" class="site-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="site-label">تأكيد كلمة المرور</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="site-input" />
                    </div>
                </div>

                <button type="button" @click="step = 2" class="site-btn site-btn-primary w-full">
                    التالي
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" />
                    </svg>
                </button>
            </fieldset>

            {{-- Step 2: organisation --}}
            <fieldset x-show="step === 2" x-cloak class="space-y-5">
                <legend class="sr-only">{{ $steps[1] }}</legend>

                <div>
                    <label for="company_name" class="site-label">اسم المؤسسة</label>
                    <input
                        id="company_name" name="company_name" type="text" value="{{ old('company_name') }}" autocomplete="organization"
                        @input="if (! slugTouched) { $refs.companySlug.value = slugify($event.target.value); }"
                        class="site-input" {!! $invalid('company_name') !!}
                    />
                    @error('company_name') <p id="company_name-error" class="site-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="company_slug" class="site-label">المعرّف الفريد للمؤسسة</label>
                    {{-- One field visually: the slug and its fixed domain suffix share the cream input surface. --}}
                    <div @class(['site-input flex items-stretch overflow-hidden p-0 focus-within:border-site-primary', 'border-[#b42318]' => $errors->has('company_slug')])>
                        <input
                            id="company_slug" x-ref="companySlug" name="company_slug" type="text" value="{{ old('company_slug') }}"
                            dir="ltr" @input="slugTouched = true"
                            class="w-full min-w-0 flex-1 border-0 bg-transparent px-3 py-4 text-start focus:outline-none"
                            aria-describedby="company_slug-{{ $errors->has('company_slug') ? 'error' : 'hint' }}"
                            @error('company_slug') aria-invalid="true" @enderror
                        />
                        <span class="flex items-center whitespace-nowrap bg-white/70 px-4 text-site-text" dir="ltr">.mada.app</span>
                    </div>
                    @error('company_slug')
                        <p id="company_slug-error" class="site-error">{{ $message }}</p>
                    @else
                        <p id="company_slug-hint" class="mt-1.5">أحرف إنجليزية صغيرة وأرقام وشرطات فقط.</p>
                    @enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="industry" class="site-label">قطاع النشاط</label>
                        <select id="industry" name="industry" class="site-input" {!! $invalid('industry') !!}>
                            <option value="" disabled {{ old('industry') ? '' : 'selected' }}>اختر القطاع</option>
                            @foreach ($industries as $value => $label)
                                <option value="{{ $value }}" @selected(old('industry') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('industry') <p id="industry-error" class="site-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="team_size" class="site-label">حجم فريق العمل</label>
                        <select id="team_size" name="team_size" class="site-input" {!! $invalid('team_size') !!}>
                            <option value="" disabled {{ old('team_size') ? '' : 'selected' }}>اختر الحجم</option>
                            @foreach ($teamSizes as $value => $label)
                                <option value="{{ $value }}" @selected(old('team_size') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('team_size') <p id="team_size-error" class="site-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex gap-3">
                    <button type="button" @click="step = 1" class="site-btn site-btn-outline">السابق</button>
                    <button type="button" @click="step = 3" class="site-btn site-btn-primary flex-1">التالي</button>
                </div>
            </fieldset>

            {{-- Step 3: plan and terms --}}
            <fieldset x-show="step === 3" x-cloak class="space-y-5">
                <legend class="sr-only">{{ $steps[2] }}</legend>

                <div role="radiogroup" aria-labelledby="plan-label" @error('plan') aria-describedby="plan-error" @enderror>
                    <p id="plan-label" class="site-label">اختر خطة الاشتراك</p>
                    <div class="grid gap-4 sm:grid-cols-3">
                        @foreach ($plans as $value => $plan)
                            <label class="relative flex cursor-pointer flex-col rounded-md border border-site-border bg-white p-4 transition has-checked:border-site-primary has-checked:bg-site-cream has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-site-primary">
                                <input type="radio" name="plan" value="{{ $value }}" class="sr-only" {{ old('plan', 'growth') === $value ? 'checked' : '' }} />
                                <span class="font-semibold text-site-dark">{{ $plan['label'] }}</span>
                                <span class="mt-1">{{ $plan['tagline'] }}</span>
                                <span class="mt-3 font-semibold text-site-primary-ink">{{ $plan['price'] }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('plan') <p id="plan-error" class="site-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="flex items-start gap-3">
                        <input type="checkbox" name="terms" value="1" {{ old('terms') ? 'checked' : '' }} class="site-check mt-1" {!! $invalid('terms') !!} />
                        <span>
                            أوافق على
                            <a href="{{ route('marketing.terms') }}" target="_blank" rel="noopener" class="site-link underline">الشروط والأحكام</a>
                            و<a href="{{ route('marketing.privacy') }}" target="_blank" rel="noopener" class="site-link underline">سياسة الخصوصية</a>.
                        </span>
                    </label>
                    @error('terms') <p id="terms-error" class="site-error">{{ $message }}</p> @enderror
                </div>

                <div class="flex gap-3">
                    <button type="button" @click="step = 2" class="site-btn site-btn-outline">السابق</button>
                    <button type="submit" class="site-btn site-btn-primary flex-1">إنشاء الحساب</button>
                </div>
            </fieldset>
        </form>
    </div>

    <p class="mt-6 text-center">
        لديك حساب بالفعل؟
        <a href="{{ route('login') }}" class="site-link">سجّل الدخول</a>
    </p>
</x-layouts.auth-centered>
