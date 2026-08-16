@php
    $initialStep = 1;

    if ($errors->hasAny(['company_name', 'company_slug', 'industry', 'team_size'])) {
        $initialStep = 2;
    } elseif ($errors->hasAny(['plan', 'terms'])) {
        $initialStep = 3;
    }

    $steps = ['بيانات الحساب', 'بيانات المؤسسة', 'الخطة والمراجعة'];

    // Shared with login.blade.php — see the note there on the 40px control height.
    $inputClasses = 'block h-10 w-full rounded-lg border border-mist-300 bg-white px-3 text-sm text-ink-900 transition duration-150 placeholder:text-mist-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/25';
    $labelClasses = 'mb-1.5 block text-sm font-medium text-ink-700';
    $errorClasses = 'mt-1.5 text-xs font-medium text-critical-500';
    $primaryBtn = 'inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-brand-500 px-6 text-sm font-semibold text-white transition duration-150 ease-in-out hover:bg-brand-600 active:translate-y-px';
    $secondaryBtn = 'inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-mist-100 px-6 text-sm font-semibold text-ink-700 transition duration-150 ease-in-out hover:bg-mist-200 active:translate-y-px';
@endphp

<x-layouts.auth-centered wide title="إنشاء حساب — مدى">
    <div class="mb-6 text-center">
        <h1 class="font-display text-3xl font-extrabold tracking-tight text-ink-900">ابدأ مجاناً</h1>
        <p class="mt-2 text-sm text-mist-500">ثلاث خطوات قصيرة، ولا حاجة لبطاقة بنكية.</p>
    </div>

    <div
        x-data="{
            step: {{ $initialStep }},
            slugTouched: {{ old('company_slug') ? 'true' : 'false' }},
            slugify(value) {
                return value.toString().trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
            },
        }"
        class="rounded-xl border border-mist-200 bg-white p-6 shadow-sm sm:p-8"
    >
        {{-- Stepper --}}
        <div class="mb-8 flex items-center" role="list" aria-label="خطوات إنشاء الحساب">
            @foreach ($steps as $index => $label)
                @php $n = $index + 1; @endphp
                <div class="flex items-center {{ $index < count($steps) - 1 ? 'flex-1' : '' }}">
                    <div class="flex flex-col items-center gap-2">
                        <span
                            :class="step > {{ $n }}
                                ? 'bg-brand-500 text-white'
                                : (step === {{ $n }}
                                    ? 'border-2 border-brand-500 bg-brand-500/10 text-brand-600'
                                    : 'border border-mist-300 text-mist-400')"
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
                            :class="step === {{ $n }} ? 'text-brand-600' : 'text-mist-500'"
                            class="hidden text-xs font-medium sm:block"
                        >
                            {{ $label }}
                        </span>
                    </div>

                    @if ($index < count($steps) - 1)
                        <div
                            :class="step > {{ $n }} ? 'bg-brand-500' : 'bg-mist-200'"
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
                        class="{{ $inputClasses }} {{ $errors->has('name') ? '!border-critical-500' : '' }}"
                    />
                    @error('name') <p class="{{ $errorClasses }}">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="{{ $labelClasses }}">البريد الإلكتروني للعمل</label>
                    <input
                        id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email"
                        class="{{ $inputClasses }} {{ $errors->has('email') ? '!border-critical-500' : '' }}"
                    />
                    @error('email') <p class="{{ $errorClasses }}">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="password" class="{{ $labelClasses }}">كلمة المرور</label>
                        <input
                            id="password" name="password" type="password" autocomplete="new-password"
                            class="{{ $inputClasses }} {{ $errors->has('password') ? '!border-critical-500' : '' }}"
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
                        class="{{ $inputClasses }} {{ $errors->has('company_name') ? '!border-critical-500' : '' }}"
                    />
                    @error('company_name') <p class="{{ $errorClasses }}">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="company_slug" class="{{ $labelClasses }}">المعرّف الفريد للمؤسسة</label>
                    <div class="flex items-stretch overflow-hidden rounded-xl border border-mist-300 focus-within:border-brand-500 focus-within:ring-2 focus-within:ring-brand-500/30 {{ $errors->has('company_slug') ? '!border-critical-500' : '' }}">
                        <input
                            id="company_slug" x-ref="companySlug" name="company_slug" type="text" value="{{ old('company_slug') }}"
                            dir="ltr" @input="slugTouched = true"
                            class="w-full flex-1 border-0 bg-white px-3 py-2 text-sm text-ink-900 placeholder:text-mist-400 focus:outline-none focus:ring-0"
                        />
                        <span class="flex items-center whitespace-nowrap bg-mist-50 px-4 text-sm text-mist-400">.mada.app</span>
                    </div>
                    @error('company_slug')
                        <p class="{{ $errorClasses }}">{{ $message }}</p>
                    @else
                        <p class="mt-1.5 text-xs text-mist-400">أحرف إنجليزية صغيرة وأرقام وشرطات فقط.</p>
                    @enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="industry" class="{{ $labelClasses }}">قطاع النشاط</label>
                        <select id="industry" name="industry" class="{{ $inputClasses }} {{ $errors->has('industry') ? '!border-critical-500' : '' }}">
                            <option value="" disabled {{ old('industry') ? '' : 'selected' }}>اختر القطاع</option>
                            @foreach ($industries as $value => $label)
                                <option value="{{ $value }}" @selected(old('industry') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('industry') <p class="{{ $errorClasses }}">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="team_size" class="{{ $labelClasses }}">حجم فريق العمل</label>
                        <select id="team_size" name="team_size" class="{{ $inputClasses }} {{ $errors->has('team_size') ? '!border-critical-500' : '' }}">
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
                            <label class="relative flex cursor-pointer flex-col rounded-2xl border border-mist-200 p-4 transition duration-200 ease-out has-checked:border-brand-500 has-checked:bg-brand-500/5 has-checked:shadow-glow">
                                <input type="radio" name="plan" value="{{ $value }}" class="sr-only" {{ old('plan', 'growth') === $value ? 'checked' : '' }} />
                                <span class="font-display text-base font-semibold text-ink-900">{{ $plan['label'] }}</span>
                                <span class="mt-1 text-xs text-mist-500">{{ $plan['tagline'] }}</span>
                                <span class="mt-3 text-sm font-semibold text-brand-600">{{ $plan['price'] }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('plan') <p class="{{ $errorClasses }}">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="flex items-start gap-3 text-sm text-mist-600">
                        <input type="checkbox" name="terms" value="1" {{ old('terms') ? 'checked' : '' }} class="mt-0.5 h-4 w-4 shrink-0 rounded border-mist-300 text-brand-500 focus:ring-brand-500/40" />
                        <span>أوافق على <a href="#" class="font-medium text-brand-600 underline">الشروط والأحكام</a> و<a href="#" class="font-medium text-brand-600 underline">سياسة الخصوصية</a>.</span>
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

    <p class="mt-6 text-center text-sm text-mist-500">
        لديك حساب بالفعل؟
        <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:underline">سجّل الدخول</a>
    </p>
</x-layouts.auth-centered>
