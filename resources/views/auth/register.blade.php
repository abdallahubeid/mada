@php
    $initialStep = 1;

    if ($errors->hasAny(['company_name', 'company_slug', 'industry', 'team_size'])) {
        $initialStep = 2;
    } elseif ($errors->hasAny(['plan', 'terms'])) {
        $initialStep = 3;
    }

    $steps = ['بيانات الحساب', 'بيانات المؤسسة', 'الخطة والمراجعة'];

    $inputClasses = 'block w-full rounded-xl border border-mist-300 bg-white px-4 py-2.5 text-sm text-ink-900 shadow-sm transition duration-150 placeholder:text-mist-400 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-600 dark:bg-ink-800 dark:text-ink-50 dark:placeholder:text-mist-500';
    $labelClasses = 'mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200';
    $errorClasses = 'mt-1.5 text-xs text-danger-solid';
    $primaryBtn = 'inline-flex w-full items-center justify-center gap-2 rounded-full bg-emerald-500 px-6 py-3 text-sm font-semibold text-ink-950 shadow-glow transition duration-200 ease-in-out hover:bg-emerald-400 active:scale-[0.98]';
    $secondaryBtn = 'inline-flex items-center justify-center gap-2 rounded-full border border-mist-300 px-6 py-3 text-sm font-semibold text-ink-700 transition duration-200 ease-in-out hover:border-emerald-400 hover:text-emerald-600 active:scale-[0.98] dark:border-ink-600 dark:text-mist-200 dark:hover:border-emerald-400 dark:hover:text-emerald-400';
@endphp

<x-layouts.guest max-width="max-w-2xl" title="إنشاء حساب — Veyra ERP">
    <div class="mb-8 text-center">
        <a href="/" class="inline-flex items-center gap-2">
            <span class="font-display text-2xl font-bold text-emerald-600 dark:text-emerald-400">Veyra</span>
            <span class="text-sm text-mist-500">ERP</span>
        </a>
        <h1 class="mt-6 font-display text-2xl font-bold text-ink-900 dark:text-ink-50 sm:text-3xl">ابدأ تجربتك المجانية</h1>
        <p class="mt-2 text-sm text-mist-500 dark:text-mist-400">أنشئ حساب مؤسستك في ثلاث خطوات بسيطة — بدون بطاقة ائتمانية.</p>
    </div>

    <div
        x-data="{
            step: {{ $initialStep }},
            slugTouched: {{ old('company_slug') ? 'true' : 'false' }},
            slugify(value) {
                return value.toString().trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
            },
        }"
        class="rounded-3xl border border-mist-200 bg-white p-6 shadow-sm dark:border-ink-700 dark:bg-ink-800/60 sm:p-8"
    >
        {{-- Stepper --}}
        <div class="mb-8 flex items-center" role="list" aria-label="خطوات إنشاء الحساب">
            @foreach ($steps as $index => $label)
                @php $n = $index + 1; @endphp
                <div class="flex items-center {{ $index < count($steps) - 1 ? 'flex-1' : '' }}">
                    <div class="flex flex-col items-center gap-2">
                        <span
                            :class="step > {{ $n }}
                                ? 'bg-emerald-500 text-ink-950'
                                : (step === {{ $n }}
                                    ? 'border-2 border-emerald-400 bg-emerald-400/10 text-emerald-600 dark:text-emerald-400'
                                    : 'border border-mist-300 text-mist-400 dark:border-ink-600 dark:text-mist-500')"
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-semibold transition duration-300 ease-out"
                        >
                            <template x-if="step > {{ $n }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            </template>
                            <template x-if="step <= {{ $n }}">
                                <span>{{ $n }}</span>
                            </template>
                        </span>
                        <span
                            :class="step === {{ $n }} ? 'text-emerald-600 dark:text-emerald-400' : 'text-mist-500 dark:text-mist-400'"
                            class="hidden text-xs font-medium sm:block"
                        >
                            {{ $label }}
                        </span>
                    </div>

                    @if ($index < count($steps) - 1)
                        <div
                            :class="step > {{ $n }} ? 'bg-emerald-400' : 'bg-mist-200 dark:bg-ink-600'"
                            class="mx-3 h-0.5 flex-1 rounded-full transition duration-300 ease-out"
                        ></div>
                    @endif
                </div>
            @endforeach
        </div>

        <form method="POST" action="{{ route('register') }}" class="space-y-6" novalidate>
            @csrf

            {{-- Step 1: User Credentials --}}
            <div x-show="step === 1" x-cloak class="space-y-5">
                <div>
                    <label for="name" class="{{ $labelClasses }}">الاسم الكامل</label>
                    <input
                        id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" autofocus
                        class="{{ $inputClasses }} {{ $errors->has('name') ? '!border-danger-solid' : '' }}"
                    />
                    @error('name') <p class="{{ $errorClasses }}">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="{{ $labelClasses }}">البريد الإلكتروني للعمل</label>
                    <input
                        id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email"
                        class="{{ $inputClasses }} {{ $errors->has('email') ? '!border-danger-solid' : '' }}"
                    />
                    @error('email') <p class="{{ $errorClasses }}">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="password" class="{{ $labelClasses }}">كلمة المرور</label>
                        <input
                            id="password" name="password" type="password" autocomplete="new-password"
                            class="{{ $inputClasses }} {{ $errors->has('password') ? '!border-danger-solid' : '' }}"
                        />
                        @error('password') <p class="{{ $errorClasses }}">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="{{ $labelClasses }}">تأكيد كلمة المرور</label>
                        <input
                            id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                            class="{{ $inputClasses }}"
                        />
                    </div>
                </div>

                <button type="button" @click="step = 2" class="{{ $primaryBtn }}">
                    التالي
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" />
                    </svg>
                </button>
            </div>

            {{-- Step 2: Organization Details --}}
            <div x-show="step === 2" x-cloak class="space-y-5">
                <div>
                    <label for="company_name" class="{{ $labelClasses }}">اسم المؤسسة</label>
                    <input
                        id="company_name" name="company_name" type="text" value="{{ old('company_name') }}" autocomplete="organization"
                        @input="if (! slugTouched) { $refs.companySlug.value = slugify($event.target.value); }"
                        class="{{ $inputClasses }} {{ $errors->has('company_name') ? '!border-danger-solid' : '' }}"
                    />
                    @error('company_name') <p class="{{ $errorClasses }}">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="company_slug" class="{{ $labelClasses }}">المعرّف الفريد للمؤسسة</label>
                    <div class="flex items-stretch overflow-hidden rounded-xl border border-mist-300 focus-within:border-emerald-400 focus-within:ring-2 focus-within:ring-emerald-400/30 dark:border-ink-600 {{ $errors->has('company_slug') ? '!border-danger-solid' : '' }}">
                        <input
                            id="company_slug" x-ref="companySlug" name="company_slug" type="text" value="{{ old('company_slug') }}"
                            dir="ltr" @input="slugTouched = true"
                            class="w-full flex-1 border-0 bg-white px-4 py-2.5 text-sm text-ink-900 placeholder:text-mist-400 focus:outline-none focus:ring-0 dark:bg-ink-800 dark:text-ink-50"
                        />
                        <span class="flex items-center whitespace-nowrap bg-mist-50 px-4 text-sm text-mist-400 dark:bg-ink-700 dark:text-mist-500">.veyra.app</span>
                    </div>
                    @error('company_slug')
                        <p class="{{ $errorClasses }}">{{ $message }}</p>
                    @else
                        <p class="mt-1.5 text-xs text-mist-400 dark:text-mist-500">أحرف إنجليزية صغيرة وأرقام وشرطات فقط.</p>
                    @enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="industry" class="{{ $labelClasses }}">قطاع النشاط</label>
                        <select id="industry" name="industry" class="{{ $inputClasses }} {{ $errors->has('industry') ? '!border-danger-solid' : '' }}">
                            <option value="" disabled {{ old('industry') ? '' : 'selected' }}>اختر القطاع</option>
                            @foreach ($industries as $value => $label)
                                <option value="{{ $value }}" @selected(old('industry') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('industry') <p class="{{ $errorClasses }}">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="team_size" class="{{ $labelClasses }}">حجم فريق العمل</label>
                        <select id="team_size" name="team_size" class="{{ $inputClasses }} {{ $errors->has('team_size') ? '!border-danger-solid' : '' }}">
                            <option value="" disabled {{ old('team_size') ? '' : 'selected' }}>اختر الحجم</option>
                            @foreach ($teamSizes as $value => $label)
                                <option value="{{ $value }}" @selected(old('team_size') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('team_size') <p class="{{ $errorClasses }}">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex gap-3">
                    <button type="button" @click="step = 1" class="{{ $secondaryBtn }}">السابق</button>
                    <button type="button" @click="step = 3" class="{{ $primaryBtn }} flex-1">التالي</button>
                </div>
            </div>

            {{-- Step 3: Plan Selection & Review --}}
            <div x-show="step === 3" x-cloak class="space-y-5">
                <div>
                    <p class="{{ $labelClasses }}">اختر خطة الاشتراك</p>
                    <div class="grid gap-4 sm:grid-cols-3">
                        @foreach ($plans as $value => $plan)
                            <label class="relative flex cursor-pointer flex-col rounded-2xl border border-mist-200 p-4 transition duration-200 ease-out has-checked:border-emerald-400 has-checked:bg-emerald-400/5 has-checked:shadow-glow dark:border-ink-600">
                                <input type="radio" name="plan" value="{{ $value }}" class="sr-only" {{ old('plan', 'growth') === $value ? 'checked' : '' }} />
                                <span class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">{{ $plan['label'] }}</span>
                                <span class="mt-1 text-xs text-mist-500 dark:text-mist-400">{{ $plan['tagline'] }}</span>
                                <span class="mt-3 text-sm font-semibold text-emerald-600 dark:text-emerald-400">{{ $plan['price'] }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('plan') <p class="{{ $errorClasses }}">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="flex items-start gap-3 text-sm text-mist-600 dark:text-mist-300">
                        <input type="checkbox" name="terms" value="1" {{ old('terms') ? 'checked' : '' }} class="mt-0.5 h-4 w-4 shrink-0 rounded border-mist-300 text-emerald-500 focus:ring-emerald-400/40 dark:border-ink-600 dark:bg-ink-800" />
                        <span>أوافق على <a href="#" class="font-medium text-emerald-600 underline dark:text-emerald-400">الشروط والأحكام</a> و<a href="#" class="font-medium text-emerald-600 underline dark:text-emerald-400">سياسة الخصوصية</a>.</span>
                    </label>
                    @error('terms') <p class="{{ $errorClasses }}">{{ $message }}</p> @enderror
                </div>

                <div class="flex gap-3">
                    <button type="button" @click="step = 2" class="{{ $secondaryBtn }}">السابق</button>
                    <button type="submit" class="{{ $primaryBtn }} flex-1">إنشاء الحساب</button>
                </div>
            </div>
        </form>
    </div>

    <p class="mt-6 text-center text-sm text-mist-500 dark:text-mist-400">
        لديك حساب بالفعل؟
        <a href="{{ route('login') }}" class="font-semibold text-emerald-600 hover:underline dark:text-emerald-400">سجّل الدخول</a>
    </p>
</x-layouts.guest>
