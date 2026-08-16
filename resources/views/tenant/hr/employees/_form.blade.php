@php
    $inputClass = 'w-full rounded-xl border border-mist-200 bg-white px-3 py-2 text-sm text-ink-700 shadow-sm transition placeholder:text-mist-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50';
    $labelClass = 'mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200';
    $errorClass = 'mt-1.5 text-xs text-danger-solid';
    $sectionClass = 'space-y-4 rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800';
    $isEdit = ($method ?? 'POST') !== 'POST';
    $hasLinkedUser = $isEdit && filled($employee->user_id);
@endphp

<form
    method="POST"
    action="{{ $action }}"
    enctype="multipart/form-data"
    class="space-y-6"
    x-data="{
        createUser: {{ old('create_user_account', false) ? 'true' : 'false' }},
        autoGenerate: {{ old('auto_generate_password', true) ? 'true' : 'false' }},
    }"
>
    @csrf
    @if ($isEdit)
        @method($method)
    @endif

    <section class="{{ $sectionClass }}">
        <div>
            <h2 class="font-display text-lg font-medium text-ink-900 dark:text-ink-50">البيانات الشخصية</h2>
            <p class="mt-1 text-xs text-mist-500">الاسم وبيانات التواصل الأساسية.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="first_name" class="{{ $labelClass }}">الاسم الأول</label>
                <input id="first_name" type="text" name="first_name" value="{{ old('first_name', $employee->first_name) }}" required class="{{ $inputClass }}">
                @error('first_name')
                    <p class="{{ $errorClass }}">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="last_name" class="{{ $labelClass }}">اسم العائلة</label>
                <input id="last_name" type="text" name="last_name" value="{{ old('last_name', $employee->last_name) }}" required class="{{ $inputClass }}">
                @error('last_name')
                    <p class="{{ $errorClass }}">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="national_id" class="{{ $labelClass }}">رقم الهوية</label>
                <input id="national_id" type="text" name="national_id" dir="ltr" value="{{ old('national_id', $employee->national_id) }}" class="{{ $inputClass }}">
                @error('national_id')
                    <p class="{{ $errorClass }}">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="phone" class="{{ $labelClass }}">الجوال</label>
                <input id="phone" type="text" name="phone" dir="ltr" value="{{ old('phone', $employee->phone) }}" class="{{ $inputClass }}">
                @error('phone')
                    <p class="{{ $errorClass }}">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="address" class="{{ $labelClass }}">العنوان</label>
            <textarea id="address" name="address" rows="2" class="{{ $inputClass }}">{{ old('address', $employee->address) }}</textarea>
            @error('address')
                <p class="{{ $errorClass }}">{{ $message }}</p>
            @enderror
        </div>
    </section>

    <section class="{{ $sectionClass }}">
        <div>
            <h2 class="font-display text-lg font-medium text-ink-900 dark:text-ink-50">بيانات الوظيفة</h2>
            <p class="mt-1 text-xs text-mist-500">المسمى، القسم، المدير المباشر، وحالة التوظيف.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="job_title" class="{{ $labelClass }}">المسمى الوظيفي</label>
                <input id="job_title" type="text" name="job_title" value="{{ old('job_title', $employee->job_title) }}" required class="{{ $inputClass }}">
                @error('job_title')
                    <p class="{{ $errorClass }}">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="joining_date" class="{{ $labelClass }}">تاريخ الالتحاق</label>
                <input
                    id="joining_date"
                    type="date"
                    name="joining_date"
                    dir="ltr"
                    value="{{ old('joining_date', optional($employee->joining_date)->format('Y-m-d')) }}"
                    required
                    class="{{ $inputClass }}"
                >
                @error('joining_date')
                    <p class="{{ $errorClass }}">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="department_id" class="{{ $labelClass }}">القسم</label>
                <select id="department_id" name="department_id" class="{{ $inputClass }}">
                    <option value="">— بدون —</option>
                    @foreach ($departments as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('department_id', $employee->department_id) === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('department_id')
                    <p class="{{ $errorClass }}">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="manager_id" class="{{ $labelClass }}">المدير المباشر</label>
                <select id="manager_id" name="manager_id" class="{{ $inputClass }}">
                    <option value="">— بدون —</option>
                    @foreach ($managers as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('manager_id', $employee->manager_id) === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('manager_id')
                    <p class="{{ $errorClass }}">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="status" class="{{ $labelClass }}">حالة التوظيف</label>
            <select id="status" name="status" required class="{{ $inputClass }}">
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $employee->status?->value ?? $employee->status) === $status->value)>
                        {{ $status->label() }}
                    </option>
                @endforeach
            </select>
            @error('status')
                <p class="{{ $errorClass }}">{{ $message }}</p>
            @enderror
        </div>
    </section>

    <section class="{{ $sectionClass }}">
        <div>
            <h2 class="font-display text-lg font-medium text-ink-900 dark:text-ink-50">المستندات المرفقة</h2>
            <p class="mt-1 text-xs text-mist-500">الصورة الشخصية والسيرة الذاتية.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="avatar" class="{{ $labelClass }}">الصورة الشخصية</label>
                @if ($isEdit && $employee->avatar_path)
                    <div class="mb-3 flex items-center gap-3">
                        <img src="{{ $employee->avatarUrl() }}" alt="" class="h-12 w-12 rounded-full object-cover ring-1 ring-mist-200 dark:ring-ink-600">
                        <label class="inline-flex items-center gap-2 text-xs text-mist-500">
                            <input type="hidden" name="remove_avatar" value="0">
                            <input type="checkbox" name="remove_avatar" value="1" @checked((bool) old('remove_avatar'))>
                            إزالة الصورة الحالية
                        </label>
                    </div>
                @elseif ($isEdit)
                    <input type="hidden" name="remove_avatar" value="0">
                @endif
                <input id="avatar" type="file" name="avatar" accept="image/*" class="{{ $inputClass }}">
                @error('avatar')
                    <p class="{{ $errorClass }}">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="cv" class="{{ $labelClass }}">السيرة الذاتية</label>
                @if ($isEdit && $employee->cv_path)
                    <div class="mb-3 flex flex-wrap items-center gap-3 text-xs">
                        <a href="{{ $employee->cvUrl() }}" target="_blank" rel="noopener" class="font-semibold text-brand-600 hover:underline dark:text-brand-300">عرض الملف الحالي</a>
                        <label class="inline-flex items-center gap-2 text-mist-500">
                            <input type="hidden" name="remove_cv" value="0">
                            <input type="checkbox" name="remove_cv" value="1" @checked((bool) old('remove_cv'))>
                            إزالة الملف الحالي
                        </label>
                    </div>
                @elseif ($isEdit)
                    <input type="hidden" name="remove_cv" value="0">
                @endif
                <input id="cv" type="file" name="cv" accept=".pdf,.doc,.docx,application/pdf" class="{{ $inputClass }}">
                @error('cv')
                    <p class="{{ $errorClass }}">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </section>

    <section class="{{ $sectionClass }}">
        <div>
            <h2 class="font-display text-lg font-medium text-ink-900 dark:text-ink-50">حساب النظام</h2>
            <p class="mt-1 text-xs text-mist-500">اختياري — إنشاء مستخدم مرتبط بملف الموظف للدخول إلى المنصة.</p>
        </div>

        @if ($hasLinkedUser)
            <p class="rounded-xl bg-brand-500/10 px-3 py-2 text-sm text-brand-700 dark:text-brand-300">
                مرتبط بحساب: <span dir="ltr">{{ $employee->user?->email }}</span>
            </p>
            <input type="hidden" name="create_user_account" value="0">
            <input type="hidden" name="auto_generate_password" value="0">
        @else
            <label class="inline-flex items-center gap-2 text-sm text-ink-700 dark:text-mist-200">
                <input type="hidden" name="create_user_account" value="0">
                <input type="checkbox" name="create_user_account" value="1" x-model="createUser">
                إنشاء حساب مستخدم لهذا الموظف
            </label>

            <div x-show="createUser" x-cloak class="space-y-4 border-t border-mist-100 pt-4 dark:border-ink-700">
                <div>
                    <label for="email" class="{{ $labelClass }}">البريد الإلكتروني</label>
                    <input id="email" type="email" name="email" dir="ltr" value="{{ old('email') }}" class="{{ $inputClass }}">
                    @error('email')
                        <p class="{{ $errorClass }}">{{ $message }}</p>
                    @enderror
                </div>

                <label class="inline-flex items-center gap-2 text-sm text-ink-700 dark:text-mist-200">
                    <input type="hidden" name="auto_generate_password" value="0">
                    <input type="checkbox" name="auto_generate_password" value="1" x-model="autoGenerate">
                    توليد كلمة مرور تلقائياً وإرسالها بالبريد
                </label>

                <div x-show="! autoGenerate" class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="password" class="{{ $labelClass }}">كلمة المرور</label>
                        <input id="password" type="password" name="password" class="{{ $inputClass }}">
                        @error('password')
                            <p class="{{ $errorClass }}">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="{{ $labelClass }}">تأكيد كلمة المرور</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" class="{{ $inputClass }}">
                    </div>
                </div>
            </div>

            <template x-if="! createUser">
                <input type="hidden" name="auto_generate_password" value="0">
            </template>
        @endif
    </section>

    <div class="flex justify-end gap-3">
        <a href="{{ route('hr.employees.index') }}" class="rounded-xl px-4 py-2 text-sm font-semibold text-mist-600 transition hover:text-ink-700 dark:text-mist-400 dark:hover:text-mist-200">إلغاء</a>
        <button type="submit" class="rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-glow transition hover:bg-brand-600">حفظ</button>
    </div>
</form>
