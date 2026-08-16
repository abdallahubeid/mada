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
        'completed' => 'border-t-brand-500',
    ];
@endphp

<x-layouts.app title="مهامي">
    @if ($employee === null)
        <div class="mx-auto max-w-2xl">
            <div class="rounded-2xl border border-mist-200 bg-white p-8 text-center shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-400/15 text-mist-400 dark:text-mist-500"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" /></svg></div>
                <h1 class="mt-4 font-display text-2xl font-medium text-ink-900 dark:text-ink-50">مهامي</h1>
                <p class="mt-2 text-sm text-mist-500">
                    حسابك الإداري غير مرتبط بملف موظف، لذا لا تتوفر لوحة مهام لعرضها.
                </p>
            </div>
        </div>
    @else
        <div class="mx-auto max-w-7xl space-y-6" x-data="{ draggedId: null }">
            <div>
                <h1 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">مهامي</h1>
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
                            <span class="rounded-md bg-white px-2 py-0.5 text-xs font-semibold text-mist-500 shadow-sm dark:bg-ink-800 dark:text-mist-400">
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
                                        <span @class(['shrink-0 rounded-md px-2 py-0.5 text-xs font-bold', $priorityClasses[$task->priority->value] ?? ''])>
                                            {{ $task->priority->label() }}
                                        </span>
                                    </div>

                                    @if ($task->description)
                                        <p class="line-clamp-2 text-xs text-mist-500">{{ $task->description }}</p>
                                    @endif

                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-mist-500">
                                        @if ($task->due_date)
                                            <span dir="ltr"><span class="inline-flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg> </span>{{ $task->due_date->format('Y-m-d') }}</span>
                                        @endif
                                        @if ($task->manager)
                                            <span><span class="inline-flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg> </span>{{ $task->manager->full_name }}</span>
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
                                            class="rounded-lg px-2 py-1 text-xs font-semibold text-brand-600 transition hover:bg-brand-500/10 disabled:cursor-not-allowed disabled:opacity-30 dark:text-brand-300"
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
