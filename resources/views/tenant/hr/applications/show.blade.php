@php
    use App\Domain\Tenancy\Enums\ApplicationStatus;
@endphp

<x-layouts.app title="طلب تقديم">
    <div class="mx-auto max-w-3xl space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">{{ $application->applicant_name }}</h1>
                <p class="mt-1 text-sm text-mist-500">{{ $application->jobPosting?->title }}</p>
            </div>
            <a href="{{ route('hr.applications.index') }}" class="rounded-xl border border-mist-200 px-4 py-2 text-sm font-semibold text-mist-600 dark:border-ink-600">رجوع</a>
        </div>

        <section class="grid gap-4 rounded-2xl border border-mist-200 bg-white p-5 shadow-sm sm:grid-cols-2 dark:border-ink-600 dark:bg-ink-800">
            <div>
                <p class="text-xs text-mist-500">البريد</p>
                <p class="mt-1 text-sm text-ink-800 dark:text-ink-100" dir="ltr">{{ $application->email }}</p>
            </div>
            <div>
                <p class="text-xs text-mist-500">الجوال</p>
                <p class="mt-1 text-sm text-ink-800 dark:text-ink-100" dir="ltr">{{ $application->phone }}</p>
            </div>
            <div>
                <p class="text-xs text-mist-500">السيرة الذاتية</p>
                @if ($application->cvUrl())
                    <a href="{{ $application->cvUrl() }}" target="_blank" rel="noopener" class="mt-1 inline-block text-sm font-semibold text-emerald-600 dark:text-emerald-400">تحميل CV</a>
                @else
                    <p class="mt-1 text-sm text-mist-500">—</p>
                @endif
            </div>
            <div>
                <p class="text-xs text-mist-500">القسم</p>
                <p class="mt-1 text-sm text-ink-800 dark:text-ink-100">{{ $application->jobPosting?->department?->name ?? '—' }}</p>
            </div>
            @if ($application->cover_letter)
                <div class="sm:col-span-2">
                    <p class="text-xs text-mist-500">خطاب التغطية</p>
                    <p class="mt-1 whitespace-pre-line text-sm text-mist-600 dark:text-mist-300">{{ $application->cover_letter }}</p>
                </div>
            @endif
        </section>

        @can('hr.applications.update')
            <form method="POST" action="{{ route('hr.applications.update', $application) }}" class="space-y-4 rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                @csrf
                @method('PUT')
                <div>
                    <label for="status" class="mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200">مرحلة ATS</label>
                    <select id="status" name="status" class="w-full rounded-xl border border-mist-200 px-3 py-2.5 text-sm dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50">
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(old('status', $application->status->value) === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-glow hover:bg-emerald-300">تحديث المرحلة</button>
                </div>
            </form>
        @endcan

        <section class="space-y-4 rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">المقابلات</h2>
                    <p class="mt-0.5 text-sm text-mist-500 dark:text-mist-400">مراحل المقابلة المجدولة لهذا المرشّح.</p>
                </div>
                @can('hr.recruitment.manage')
                    @include('tenant.hr.applications._schedule-interview')
                @endcan
            </div>

            @forelse ($application->interviews as $interview)
                <div class="rounded-xl border border-mist-100 p-4 dark:border-ink-700">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-sm font-semibold text-ink-900 dark:text-ink-50">
                            <x-ui.ltr>{{ $interview->scheduled_at?->format('Y-m-d H:i') }}</x-ui.ltr>
                        </p>
                        <span class="text-xs text-mist-500 dark:text-mist-400">
                            المحاور: {{ $interview->interviewer?->name ?? '—' }}
                        </span>
                    </div>
                    @if ($interview->location_or_link)
                        <p class="mt-1 break-all text-sm text-mist-600 dark:text-mist-300" dir="auto">{{ $interview->location_or_link }}</p>
                    @endif
                    @if ($interview->notes)
                        <p class="mt-1 whitespace-pre-line text-xs text-mist-500 dark:text-mist-400">{{ $interview->notes }}</p>
                    @endif
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-mist-200 p-6 text-center dark:border-ink-600">
                    <p class="text-sm text-mist-500 dark:text-mist-400">لا توجد مقابلات مجدولة بعد.</p>
                </div>
            @endforelse
        </section>

        @if ($application->status === ApplicationStatus::Accepted)
            <section class="rounded-2xl border border-emerald-400/30 bg-emerald-50/50 p-5 dark:bg-emerald-500/10">
                @if ($application->isConverted())
                    <p class="text-sm text-emerald-800 dark:text-emerald-200">
                        تم التحويل إلى موظف:
                        <a href="{{ route('hr.employees.show', $application->converted_employee_id) }}" class="font-semibold underline">عرض الملف</a>
                    </p>
                @else
                    @can('hr.applications.convert')
                        <form method="POST" action="{{ route('hr.applications.convert', $application) }}" data-swal-confirm data-swal-variant="success" data-swal-title="تحويل المتقدم إلى موظف؟" data-swal-text="سيتم إنشاء ملف موظف مسبقاً بالبيانات والسيرة الذاتية." data-swal-confirm-button="نعم، حوّل إلى موظف">
                            @csrf
                            <button type="submit" class="rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-glow hover:bg-emerald-300">
                                تحويل إلى موظف
                            </button>
                        </form>
                    @endcan
                @endif
            </section>
        @endif
    </div>
</x-layouts.app>
