@php
    use App\Domain\Tenancy\Enums\AttendanceStatus;

    $card = 'rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800';

    $statusClasses = [
        AttendanceStatus::Present->value => 'bg-brand-500/15 text-brand-700 dark:text-brand-300',
        AttendanceStatus::Late->value => 'bg-amber-400/15 text-amber-800 dark:text-amber-300',
        AttendanceStatus::Absent->value => 'bg-danger-solid/10 text-danger-solid',
        AttendanceStatus::HalfDay->value => 'bg-sky-400/15 text-sky-800 dark:text-sky-300',
    ];
@endphp

<x-layouts.app title="تسجيل الحضور والانصراف">
    @if ($employee === null)
        <div class="mx-auto max-w-2xl">
            <div class="{{ $card }} text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-400/15 text-mist-400 dark:text-mist-500"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg></div>
                <h1 class="mt-4 font-display text-2xl font-medium text-ink-900 dark:text-ink-50">تسجيل الحضور والانصراف</h1>
                <p class="mt-2 text-sm text-mist-500">
                    حسابك غير مرتبط بملف موظف، لذا لا يتوفر تسجيل حضور ذاتي. تواصل مع إدارة الموارد البشرية لربط حسابك.
                </p>
            </div>
        </div>
    @else
        <div class="mx-auto max-w-5xl space-y-6">
            <div>
                <h1 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">تسجيل الحضور والانصراف</h1>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">
                    سجّل حضورك وانصرافك، وتابع سجلّك الكامل — {{ now()->translatedFormat('l، j F Y') }}
                </p>
            </div>

            {{-- Today + action --}}
            <section class="relative overflow-hidden rounded-2xl border border-mist-200 bg-gradient-to-br from-brand-500/20 via-white to-sky-400/10 p-4 shadow-sm dark:border-ink-600 dark:from-brand-500/10 dark:via-ink-800 dark:to-ink-800 sm:p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-medium text-brand-700 dark:text-brand-300">حالة اليوم</p>
                        @if ($todayAttendance?->check_in === null)
                            <p class="mt-1 font-display text-xl font-medium text-ink-900 dark:text-ink-50">لم تسجّل حضورك بعد</p>
                        @elseif ($todayAttendance->check_out === null)
                            <p class="mt-1 font-display text-xl font-medium text-ink-900 dark:text-ink-50">
                                حاضر منذ <span dir="ltr">{{ $todayAttendance->check_in?->format('H:i') }}</span>
                            </p>
                            <span @class(['mt-2 inline-flex rounded-md px-2.5 py-0.5 text-xs font-semibold', $statusClasses[$todayAttendance->status->value] ?? ''])>
                                {{ $todayAttendance->status->label() }}
                            </span>
                        @else
                            <p class="mt-1 font-display text-xl font-medium text-ink-900 dark:text-ink-50">اكتمل يومك</p>
                            <p class="mt-1 text-sm text-mist-500" dir="ltr">
                                {{ $todayAttendance->check_in?->format('H:i') }} → {{ $todayAttendance->check_out?->format('H:i') }}
                            </p>
                        @endif
                    </div>

                    <div data-testid="attendance-action">
                        @can('hr.attendance.check_in_out')
                            @if ($todayAttendance?->check_in === null)
                                <form method="POST" action="{{ route('tenant.hr.my-attendance.check-in') }}">
                                    @csrf
                                    <button type="submit" class="rounded-xl bg-brand-500 px-6 py-3 text-sm font-semibold text-white shadow-glow transition hover:bg-brand-600">
                                        تسجيل حضور
                                    </button>
                                </form>
                            @elseif ($todayAttendance->check_out === null)
                                <form method="POST" action="{{ route('tenant.hr.my-attendance.check-out') }}">
                                    @csrf
                                    <button type="submit" class="rounded-xl border border-amber-400/50 bg-amber-400/15 px-6 py-3 text-sm font-semibold text-amber-900 transition hover:bg-amber-400/25 dark:text-amber-200">
                                        تسجيل انصراف
                                    </button>
                                </form>
                            @else
                                <span class="inline-flex rounded-xl border border-mist-200 bg-white/70 px-5 py-2.5 text-sm font-semibold text-mist-600 dark:border-ink-600 dark:bg-ink-900/50 dark:text-mist-300">
                                    اكتمل حضور اليوم
                                </span>
                            @endif
                        @endcan
                    </div>
                </div>
            </section>

            {{-- This month --}}
            <div class="grid gap-3 sm:grid-cols-3 xl:grid-cols-5">
                @foreach ([
                    ['أيام مسجّلة', $monthSummary['recorded'], 'text-ink-900 dark:text-ink-50', 'recorded'],
                    ['حاضر', $monthSummary['present'], 'text-brand-600 dark:text-brand-300', 'present'],
                    ['متأخر', $monthSummary['late'], 'text-amber-600 dark:text-amber-400', 'late'],
                    ['نصف يوم', $monthSummary['half_day'], 'text-sky-600 dark:text-sky-400', 'half-day'],
                    ['غائب', $monthSummary['absent'], 'text-danger-solid', 'absent'],
                ] as [$label, $value, $tone, $slug])
                    <div class="{{ $card }} text-center">
                        <p class="text-xs font-medium text-mist-500">{{ $label }}</p>
                        <p class="mt-1 font-display text-2xl font-medium {{ $tone }}" data-testid="month-{{ $slug }}">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            {{-- History --}}
            <div class="space-y-4">
                <h2 class="font-display text-lg font-medium text-ink-900 dark:text-ink-50">سجلّي الكامل</h2>
                <div class="w-full overflow-x-auto rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
                    <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                        <thead class="bg-mist-50 dark:bg-ink-900">
                            <tr>
                                <th class="w-12 px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">#</th>
                                <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">التاريخ</th>
                                <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">حضور</th>
                                <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">انصراف</th>
                                <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-center">الحالة</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                            @forelse ($attendances as $attendance)
                                <tr>
                                    <td class="w-12 px-3 py-2 text-center text-sm tabular-nums text-mist-500">{{ $loop->iteration }}</td>
                                    <td class="px-3 py-2 tabular-nums text-start"><x-ui.ltr>{{ $attendance->date?->format('Y-m-d') }}</x-ui.ltr></td>
                                    <td class="px-3 py-2 tabular-nums text-start"><x-ui.ltr>{{ $attendance->check_in?->format('H:i') ?? '—' }}</x-ui.ltr></td>
                                    <td class="px-3 py-2 tabular-nums text-start"><x-ui.ltr>{{ $attendance->check_out?->format('H:i') ?? '—' }}</x-ui.ltr></td>
                                    <td class="px-3 py-2 text-center">
                                        <span @class(['inline-flex rounded-md px-2.5 py-0.5 text-xs font-semibold', $statusClasses[$attendance->status->value] ?? ''])>
                                            {{ $attendance->status->label() }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <x-ui.table-empty :colspan="5" icon="clock" message="لا توجد سجلات حضور بعد." />
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div>{{ $attendances->withQueryString()->links() }}</div>
            </div>
        </div>
    @endif
</x-layouts.app>
