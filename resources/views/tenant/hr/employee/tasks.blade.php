@php
    use App\Domain\Tenancy\Enums\TaskPriority;

    $priorityClasses = [
        TaskPriority::Low->value => 'bg-sky-400/15 text-sky-800 dark:text-sky-300',
        TaskPriority::Medium->value => 'bg-amber-400/15 text-amber-800 dark:text-amber-300',
        TaskPriority::High->value => 'bg-danger-solid/10 text-danger-solid',
    ];

    $columnAccent = [
        'todo' => 'border-t-mist-300 dark:border-t-ink-500',
        'in_progress' => 'border-t-sky-400',
        'review' => 'border-t-amber-400',
        'completed' => 'border-t-emerald-400',
    ];
@endphp

<x-layouts.app title="مهامي">
    @if ($employee === null)
        <div class="mx-auto max-w-2xl">
            <div class="rounded-2xl border border-mist-200 bg-white p-8 text-center shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-400/15 text-2xl">🗂️</div>
                <h1 class="mt-4 font-display text-2xl font-bold text-ink-900 dark:text-ink-50">مهامي</h1>
                <p class="mt-2 text-sm text-mist-500">
                    حسابك الإداري غير مرتبط بملف موظف، لذا لا تتوفر لوحة مهام لعرضها.
                </p>
            </div>
        </div>
    @else
        <div class="mx-auto max-w-7xl space-y-6" x-data="{ draggedId: null }">
            <div>
                <h1 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">مهامي</h1>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">
                    اسحب البطاقة إلى العمود المناسب، أو استخدم زرّي التالي/السابق، لتحديث حالة المهمة.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($statuses as $status)
                    @php $columnTasks = $columns[$status->value]; @endphp
                    <div
                        class="flex flex-col gap-3 rounded-2xl border border-t-4 {{ $columnAccent[$status->value] ?? 'border-t-mist-300' }} border-mist-200 bg-mist-50/60 p-3 dark:border-ink-700 dark:bg-ink-900/40"
                        @dragover.prevent
                        @drop="
                            if (draggedId) {
                                const form = document.getElementById('task-status-form-' + draggedId);
                                form.querySelector('[name=status]').value = '{{ $status->value }}';
                                form.requestSubmit();
                                draggedId = null;
                            }
                        "
                    >
                        <div class="flex items-center justify-between px-1">
                            <h2 class="text-sm font-bold text-ink-900 dark:text-ink-50">{{ $status->label() }}</h2>
                            <span class="rounded-full bg-white px-2 py-0.5 text-xs font-semibold text-mist-500 shadow-sm dark:bg-ink-800 dark:text-mist-400">
                                {{ $columnTasks->count() }}
                            </span>
                        </div>

                        <div class="flex min-h-24 flex-col gap-3">
                            @forelse ($columnTasks as $task)
                                <div
                                    draggable="true"
                                    @dragstart="draggedId = {{ $task->id }}"
                                    @dragend="draggedId = null"
                                    class="cursor-grab space-y-2 rounded-xl border border-mist-200 bg-white p-3 shadow-sm transition active:cursor-grabbing dark:border-ink-600 dark:bg-ink-800"
                                >
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="text-sm font-semibold text-ink-900 dark:text-ink-50">{{ $task->title }}</p>
                                        <span @class(['shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold', $priorityClasses[$task->priority->value] ?? ''])>
                                            {{ $task->priority->label() }}
                                        </span>
                                    </div>

                                    @if ($task->description)
                                        <p class="line-clamp-2 text-xs text-mist-500">{{ $task->description }}</p>
                                    @endif

                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-mist-500">
                                        @if ($task->due_date)
                                            <span dir="ltr">📅 {{ $task->due_date->format('Y-m-d') }}</span>
                                        @endif
                                        @if ($task->manager)
                                            <span>👤 {{ $task->manager->full_name }}</span>
                                        @endif
                                    </div>

                                    <form id="task-status-form-{{ $task->id }}" method="POST" action="{{ route('tenant.hr.my-tasks.status', $task) }}" class="hidden">
                                        @csrf
                                        <input type="hidden" name="status" value="{{ $task->status->value }}">
                                    </form>

                                    <div class="flex items-center justify-between border-t border-mist-100 pt-2 dark:border-ink-700">
                                        <button
                                            type="submit"
                                            form="task-status-form-{{ $task->id }}"
                                            @click="document.getElementById('task-status-form-{{ $task->id }}').querySelector('[name=status]').value = '{{ $status->previous()?->value }}'"
                                            @disabled(! $status->previous())
                                            class="rounded-lg px-2 py-1 text-xs font-semibold text-mist-500 transition hover:bg-mist-100 disabled:cursor-not-allowed disabled:opacity-30 dark:hover:bg-ink-700"
                                        >◀ السابق</button>
                                        <button
                                            type="submit"
                                            form="task-status-form-{{ $task->id }}"
                                            @click="document.getElementById('task-status-form-{{ $task->id }}').querySelector('[name=status]').value = '{{ $status->next()?->value }}'"
                                            @disabled(! $status->next())
                                            class="rounded-lg px-2 py-1 text-xs font-semibold text-emerald-600 transition hover:bg-emerald-400/10 disabled:cursor-not-allowed disabled:opacity-30 dark:text-emerald-400"
                                        >التالي ▶</button>
                                    </div>
                                </div>
                            @empty
                                <p class="rounded-xl border border-dashed border-mist-200 p-4 text-center text-xs text-mist-400 dark:border-ink-700">
                                    لا توجد مهام هنا.
                                </p>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</x-layouts.app>
