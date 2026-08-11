@php
    use App\Domain\Tenancy\Enums\TaskPriority;
    use App\Domain\Tenancy\Enums\TaskStatus;

    $inputClass = 'w-full rounded-xl border border-mist-200 bg-white px-3 py-2.5 text-sm text-ink-700 shadow-sm transition placeholder:text-mist-400 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50';
    $labelClass = 'mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200';
    $errorClass = 'mt-1.5 text-xs text-danger-solid';

    $priorityClasses = [
        TaskPriority::Low->value => 'bg-sky-400/15 text-sky-800 dark:text-sky-300',
        TaskPriority::Medium->value => 'bg-amber-400/15 text-amber-800 dark:text-amber-300',
        TaskPriority::High->value => 'bg-danger-solid/10 text-danger-solid',
    ];

    $statusClasses = [
        TaskStatus::Todo->value => 'bg-mist-100 text-mist-700 dark:bg-ink-700 dark:text-mist-200',
        TaskStatus::InProgress->value => 'bg-sky-400/15 text-sky-800 dark:text-sky-300',
        TaskStatus::Review->value => 'bg-amber-400/15 text-amber-800 dark:text-amber-300',
        TaskStatus::Completed->value => 'bg-emerald-400/15 text-emerald-700 dark:text-emerald-400',
    ];
@endphp

<x-layouts.app title="إسناد المهام">
    <div class="mx-auto max-w-6xl space-y-6">
        <div>
            <h1 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">إسناد المهام</h1>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">أنشئ مهام جديدة وأسندها لأعضاء فريقك المباشرين، وتابع حالتها أولاً بأول.</p>
        </div>

        @if ($manager === null)
            <div class="rounded-2xl border border-mist-200 bg-white p-8 text-center shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <p class="text-sm text-mist-500">حسابك الإداري غير مرتبط بملف موظف، لذا لا يمكنك إسناد مهام.</p>
            </div>
        @elseif ($directReports->isEmpty())
            <div class="rounded-2xl border border-dashed border-mist-200 bg-white p-8 text-center dark:border-ink-600 dark:bg-ink-800">
                <p class="text-sm text-mist-500">لا يوجد لديك مرؤوسون مباشرون حالياً.</p>
            </div>
        @else
            <div class="grid gap-6 lg:grid-cols-[380px_1fr]">
                <section class="h-fit space-y-4 rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                    <div>
                        <h2 class="font-display text-lg font-semibold text-ink-900 dark:text-ink-50">مهمة جديدة</h2>
                        <p class="mt-1 text-xs text-mist-500">تُسند فقط لأعضاء فريقك المباشرين.</p>
                    </div>

                    <form method="POST" action="{{ route('hr.tasks.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label for="employee_id" class="{{ $labelClass }}">الموظف</label>
                            <select id="employee_id" name="employee_id" required class="{{ $inputClass }}">
                                <option value="">اختر موظفاً...</option>
                                @foreach ($directReports as $report)
                                    <option value="{{ $report->id }}" @selected((string) old('employee_id') === (string) $report->id)>{{ $report->full_name }}</option>
                                @endforeach
                            </select>
                            @error('employee_id')
                                <p class="{{ $errorClass }}">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="title" class="{{ $labelClass }}">عنوان المهمة</label>
                            <input id="title" type="text" name="title" required value="{{ old('title') }}" class="{{ $inputClass }}">
                            @error('title')
                                <p class="{{ $errorClass }}">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="description" class="{{ $labelClass }}">الوصف</label>
                            <textarea id="description" name="description" rows="3" class="{{ $inputClass }}">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="{{ $errorClass }}">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="due_date" class="{{ $labelClass }}">تاريخ الاستحقاق</label>
                                <input id="due_date" type="date" name="due_date" dir="ltr" value="{{ old('due_date') }}" class="{{ $inputClass }}">
                                @error('due_date')
                                    <p class="{{ $errorClass }}">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="priority" class="{{ $labelClass }}">الأولوية</label>
                                <select id="priority" name="priority" class="{{ $inputClass }}">
                                    @foreach ($priorities as $priority)
                                        <option value="{{ $priority->value }}" @selected(old('priority', 'medium') === $priority->value)>{{ $priority->label() }}</option>
                                    @endforeach
                                </select>
                                @error('priority')
                                    <p class="{{ $errorClass }}">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <button type="submit" class="w-full rounded-xl bg-emerald-400 px-4 py-2.5 text-sm font-semibold text-emerald-900 shadow-glow transition hover:bg-emerald-300">
                            إسناد المهمة
                        </button>
                    </form>
                </section>

                <section class="space-y-3">
                    <h2 class="font-display text-lg font-semibold text-ink-900 dark:text-ink-50">مهام الفريق</h2>

                    @if ($tasks->isEmpty())
                        <div class="rounded-2xl border border-dashed border-mist-200 bg-white p-8 text-center dark:border-ink-600 dark:bg-ink-800">
                            <p class="text-sm text-mist-500">لم يتم إسناد أي مهام بعد.</p>
                        </div>
                    @else
                        <div class="overflow-hidden rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                                    <thead class="bg-mist-50 text-mist-500 dark:bg-ink-900 dark:text-mist-400">
                                        <tr>
                                            <th class="w-12 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">#</th>
                                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">المهمة</th>
                                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">الموظف</th>
                                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">الاستحقاق</th>
                                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">الأولوية</th>
                                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-center">الحالة</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                                        @foreach ($tasks as $task)
                                            <tr>
                                                <td class="w-12 px-4 py-3 text-center text-sm tabular-nums text-mist-500">{{ $loop->iteration }}</td>
                                                <td class="px-4 py-3 font-medium text-ink-900 dark:text-ink-50 text-start">{{ $task->title }}</td>
                                                <td class="px-4 py-3 text-mist-600 dark:text-mist-300 text-start">{{ $task->employee?->full_name ?? '—' }}</td>
                                                <td class="px-4 py-3 tabular-nums text-start"><x-ui.ltr>{{ $task->due_date?->format('Y-m-d') ?? '—' }}</x-ui.ltr></td>
                                                <td class="px-4 py-3 text-start">
                                                    <span @class(['inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold', $priorityClasses[$task->priority->value] ?? ''])>
                                                        {{ $task->priority->label() }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <span @class(['inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold', $statusClasses[$task->status->value] ?? ''])>
                                                        {{ $task->status->label() }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </section>
            </div>
        @endif
    </div>
</x-layouts.app>
