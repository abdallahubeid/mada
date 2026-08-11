@php
    $inputClass = 'w-full rounded-xl border border-mist-200 bg-white px-3 py-2.5 text-sm text-ink-700 shadow-sm transition placeholder:text-mist-400 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50';
    $labelClass = 'mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200';
    $errorClass = 'mt-1.5 text-xs text-danger-solid';

    // Reopen the modal when the POST bounced back with errors, so the operator
    // does not lose a composed email behind a closed panel.
    $interviewFields = ['interviewer_id', 'scheduled_at', 'location_or_link', 'notes', 'email_subject', 'email_body', 'cc'];
    $hasInterviewErrors = collect($interviewFields)->contains(fn (string $field): bool => $errors->has($field))
        || $errors->hasAny(['cc.0', 'cc.1', 'cc.2']);
@endphp

<div
    x-data="{
        open: {{ $hasInterviewErrors ? 'true' : 'false' }},
        previewing: false,
        preview: null,
        previewError: null,
        async runPreview() {
            this.previewing = true;
            this.previewError = null;

            try {
                const form = this.$refs.interviewForm;
                const response = await fetch(@js(route('hr.applications.interviews.preview', $application)), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=&quot;csrf-token&quot;]')?.getAttribute('content') ?? '',
                    },
                    body: new FormData(form),
                });

                if (! response.ok) {
                    this.previewError = 'تعذّرت المعاينة. تحقق من الحقول ثم أعد المحاولة.';
                    return;
                }

                this.preview = await response.json();
            } catch (error) {
                this.previewError = 'تعذّر الاتصال بالخادم لإجراء المعاينة.';
            } finally {
                this.previewing = false;
            }
        },
    }"
    @keydown.escape.window="open = false"
>
    <button
        type="button"
        @click="open = true"
        class="rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-glow transition hover:bg-emerald-300"
    >
        جدولة مقابلة
    </button>

    <div x-show="open" x-cloak class="fixed inset-0 z-40 bg-ink-950/60" @click="open = false" aria-hidden="true"></div>

    <div
        x-show="open"
        x-cloak
        x-transition
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 sm:p-6"
        role="dialog"
        aria-modal="true"
        aria-label="جدولة مقابلة"
    >
        <div class="w-full max-w-2xl rounded-2xl border border-mist-200 bg-white shadow-2xl dark:border-ink-600 dark:bg-ink-800" @click.stop>
            <div class="flex items-center justify-between border-b border-mist-100 px-5 py-4 dark:border-ink-700">
                <div>
                    <h2 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">جدولة مقابلة</h2>
                    <p class="mt-0.5 text-xs text-mist-500 dark:text-mist-400">{{ $application->applicant_name }} — {{ $application->jobPosting?->title ?? '—' }}</p>
                </div>
                <button type="button" @click="open = false" class="rounded-lg p-1 text-mist-400 transition hover:bg-mist-100 dark:hover:bg-ink-700" aria-label="إغلاق">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form
                x-ref="interviewForm"
                method="POST"
                action="{{ route('hr.applications.interviews.store', $application) }}"
                data-swal-confirm
                data-swal-variant="info"
                data-swal-title="إرسال دعوة المقابلة؟"
                data-swal-text="سيتم حفظ موعد المقابلة وإرسال البريد إلى المرشّح فوراً."
                data-swal-confirm-button="نعم، أرسل الدعوة"
                class="max-h-[70vh] space-y-4 overflow-y-auto px-5 py-5"
            >
                @csrf

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="{{ $labelClass }}">إلى (المرشّح)</label>
                        {{--
                            Read-only by design. The action always sends to the
                            application's own email and never reads a recipient
                            from the request — an editable "To" would turn this
                            screen into a way to send mail from the tenant's
                            domain to anyone.
                        --}}
                        <input type="email" dir="ltr" value="{{ $application->email }}" readonly
                               class="{{ $inputClass }} cursor-not-allowed bg-mist-50 dark:bg-ink-900/60">
                    </div>

                    <div>
                        <label for="cc" class="{{ $labelClass }}">نسخة إلى (CC) — اختياري</label>
                        <input id="cc" type="text" dir="ltr" name="cc" value="{{ old('cc') }}"
                               placeholder="hr@example.com, manager@example.com"
                               class="{{ $inputClass }}">
                        @error('cc')
                            <p class="{{ $errorClass }}">{{ $message }}</p>
                        @enderror
                        @foreach ($errors->get('cc.*') as $ccMessages)
                            @foreach ($ccMessages as $ccMessage)
                                <p class="{{ $errorClass }}">{{ $ccMessage }}</p>
                            @endforeach
                        @endforeach
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="scheduled_at" class="{{ $labelClass }}">موعد المقابلة</label>
                        <input id="scheduled_at" type="datetime-local" dir="ltr" required
                               name="scheduled_at" value="{{ old('scheduled_at') }}"
                               class="{{ $inputClass }}">
                        @error('scheduled_at')
                            <p class="{{ $errorClass }}">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="interviewer_id" class="{{ $labelClass }}">المحاور</label>
                        <select id="interviewer_id" name="interviewer_id" required class="{{ $inputClass }}">
                            <option value="">— اختر المحاور —</option>
                            @foreach ($interviewers as $id => $name)
                                <option value="{{ $id }}" @selected((string) old('interviewer_id') === (string) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('interviewer_id')
                            <p class="{{ $errorClass }}">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="location_or_link" class="{{ $labelClass }}">المكان أو رابط الاجتماع</label>
                    <input id="location_or_link" type="text" name="location_or_link" value="{{ old('location_or_link') }}"
                           placeholder="مثال: مقر الشركة — الدور الثالث، أو رابط اجتماع"
                           class="{{ $inputClass }}">
                    @error('location_or_link')
                        <p class="{{ $errorClass }}">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="notes" class="{{ $labelClass }}">ملاحظات داخلية — لا تُرسل للمرشّح</label>
                    <textarea id="notes" name="notes" rows="2" class="{{ $inputClass }}">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="{{ $errorClass }}">{{ $message }}</p>
                    @enderror
                </div>

                <div class="border-t border-mist-100 pt-4 dark:border-ink-700">
                    <div>
                        <label for="email_subject" class="{{ $labelClass }}">موضوع البريد</label>
                        <input id="email_subject" type="text" name="email_subject" required
                               value="{{ old('email_subject', $defaultSubject) }}" class="{{ $inputClass }}">
                        @error('email_subject')
                            <p class="{{ $errorClass }}">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-4">
                        <label for="email_body" class="{{ $labelClass }}">نص البريد</label>
                        <textarea id="email_body" name="email_body" rows="10" required class="{{ $inputClass }} leading-7">{{ old('email_body', $defaultBody) }}</textarea>
                        @error('email_body')
                            <p class="{{ $errorClass }}">{{ $message }}</p>
                        @enderror

                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <span class="text-xs text-mist-500 dark:text-mist-400">وسوم متاحة:</span>
                            @foreach ($interviewTags as $tag)
                                <code class="rounded-md bg-mist-100 px-2 py-0.5 text-xs text-ink-700 dark:bg-ink-900 dark:text-mist-200" dir="ltr">{{ $tag }}</code>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-dashed border-mist-200 p-3 dark:border-ink-600">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs text-mist-500 dark:text-mist-400">تحقّق من شكل الرسالة بعد استبدال الوسوم قبل الإرسال.</p>
                        <button type="button" @click="runPreview()" :disabled="previewing"
                                class="shrink-0 rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold text-ink-700 transition hover:border-emerald-400 hover:text-emerald-600 disabled:opacity-60 dark:border-ink-600 dark:text-mist-200">
                            <span x-show="! previewing">معاينة الرسالة</span>
                            <span x-show="previewing" x-cloak>جاري المعاينة…</span>
                        </button>
                    </div>

                    <p x-show="previewError" x-cloak x-text="previewError" class="mt-2 text-xs text-danger-solid"></p>

                    <template x-if="preview">
                        <div class="mt-3 space-y-2 rounded-lg bg-mist-50 p-3 dark:bg-ink-900">
                            <p class="text-xs text-mist-500 dark:text-mist-400">إلى: <span dir="ltr" x-text="preview.to"></span></p>
                            <p class="text-sm font-semibold text-ink-900 dark:text-ink-50" x-text="preview.subject"></p>
                            <p class="whitespace-pre-line text-sm leading-7 text-mist-600 dark:text-mist-300" x-text="preview.body"></p>
                        </div>
                    </template>
                </div>

                <div class="flex justify-end gap-3 border-t border-mist-100 pt-4 dark:border-ink-700">
                    <button type="button" @click="open = false" class="rounded-xl px-4 py-2 text-sm font-semibold text-mist-600 dark:text-mist-300">إلغاء</button>
                    <button type="submit" class="rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-glow transition hover:bg-emerald-300">
                        حفظ وإرسال الدعوة
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
