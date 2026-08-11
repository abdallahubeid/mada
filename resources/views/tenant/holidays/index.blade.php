<x-layouts.app title="العطلات الرسمية">
    <div
        class="space-y-6"
        x-data="{
            open: false,
            editing: null,
            openCreate() { this.editing = null; this.open = true; },
            openEdit(row) { this.editing = row; this.open = true; },
            close() { this.open = false; this.editing = null; },
        }"
    >
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">العطلات الرسمية</h1>
                <p class="mt-1 text-sm text-mist-500">تُستثنى هذه الأيام تلقائياً من احتساب مدة طلبات الإجازة.</p>
            </div>
            @if ($canManage)
                <button type="button" @click="openCreate()" class="inline-flex items-center justify-center rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-900 hover:bg-emerald-300">
                    إضافة عطلة
                </button>
            @endif
        </div>

        <div class="overflow-hidden rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <div class="w-full overflow-x-auto">
                <table class="w-full min-w-max text-sm">
                    <thead>
                        <tr class="border-b border-mist-100 text-xs text-mist-500 dark:border-ink-700">
                            <th class="w-12 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">#</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">الاسم</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">الفترة</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">متكررة</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">ملاحظات</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-center">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                        @forelse ($holidays as $holiday)
                            @php
                                $editPayload = [
                                    'id' => $holiday->id,
                                    'name' => $holiday->name,
                                    'start_date' => $holiday->start_date?->format('Y-m-d'),
                                    'end_date' => $holiday->end_date?->format('Y-m-d'),
                                    'is_recurring' => $holiday->is_recurring,
                                    'notes' => $holiday->notes,
                                    'action' => route('tenant.holidays.update', $holiday),
                                ];
                            @endphp
                            <tr class="hover:bg-mist-50 dark:hover:bg-ink-700/40">
                                <td class="w-12 px-4 py-3 text-center text-sm tabular-nums text-mist-500">{{ $loop->iteration + ($holidays->currentPage() - 1) * $holidays->perPage() }}</td>
                                <td class="px-4 py-3 font-medium text-ink-900 dark:text-ink-50 text-start">{{ $holiday->name }}</td>
                                <td class="px-4 py-3 text-mist-500 text-start"><x-ui.ltr>{{ $holiday->start_date?->format('Y-m-d') }}
                                    @if ($holiday->end_date && $holiday->start_date && ! $holiday->end_date->equalTo($holiday->start_date))
                                        → {{ $holiday->end_date->format('Y-m-d') }}
                                    @endif</x-ui.ltr></td>
                                <td class="px-4 py-3 text-start">
                                    @if ($holiday->is_recurring)
                                        <span class="rounded-full bg-sky-500/10 px-2 py-0.5 text-xs font-semibold text-sky-700 dark:text-sky-300">سنوياً</span>
                                    @else
                                        <span class="text-mist-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-mist-500 text-start">{{ $holiday->notes ?? '—' }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if ($canManage)
                                        <div class="flex justify-center gap-2">
                                            <button type="button" @click="openEdit(@js($editPayload))" class="rounded-lg border border-mist-200 px-2.5 py-1 text-xs font-semibold dark:border-ink-600">تعديل</button>
                                            <form method="POST" action="{{ route('tenant.holidays.destroy', $holiday) }}" data-swal-confirm data-swal-title="حذف هذه العطلة؟">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg border border-mist-200 px-2.5 py-1 text-xs font-semibold text-danger-solid dark:border-ink-600">حذف</button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-ui.table-empty :colspan="6" icon="📅" message="لا توجد عطلات مسجّلة بعد." />
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($holidays->hasPages())
                <div class="border-t border-mist-100 px-4 py-3 dark:border-ink-700">{{ $holidays->links() }}</div>
            @endif
        </div>

        @if ($canManage)
            <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-ink-950/50 p-4">
                <div class="w-full max-w-lg rounded-2xl bg-white p-5 shadow-xl dark:bg-ink-800" @click.outside="close()">
                    <h3 class="font-semibold" x-text="editing ? 'تعديل عطلة' : 'عطلة جديدة'"></h3>
                    <form method="POST" class="mt-4 space-y-3" :action="editing ? editing.action : @js(route('tenant.holidays.store'))">
                        @csrf
                        <input type="hidden" name="_method" value="PUT" x-bind:disabled="!editing">
                        <input type="text" name="name" required placeholder="اسم العطلة" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900" :value="editing?.name || ''">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <input type="date" name="start_date" required dir="ltr" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900" :value="editing?.start_date || ''">
                            <input type="date" name="end_date" required dir="ltr" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900" :value="editing?.end_date || ''">
                        </div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_recurring" value="1" class="rounded border-mist-300 text-emerald-500" :checked="!!editing?.is_recurring">
                            تتكرر سنوياً
                        </label>
                        <textarea name="notes" rows="2" placeholder="ملاحظات" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900" :value="editing?.notes || ''"></textarea>
                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 rounded-xl bg-emerald-400 py-2 text-sm font-semibold text-emerald-900">حفظ</button>
                            <button type="button" @click="close()" class="rounded-xl border border-mist-200 px-4 py-2 text-sm dark:border-ink-600">إلغاء</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>
