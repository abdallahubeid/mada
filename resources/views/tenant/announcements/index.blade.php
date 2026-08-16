@php
    use App\Domain\Tenancy\Enums\AnnouncementType;

    $typeBadges = [
        AnnouncementType::Info->value => 'bg-sky-500/10 text-sky-700 dark:text-sky-300',
        AnnouncementType::Warning->value => 'bg-amber-500/10 text-amber-800 dark:text-amber-300',
        AnnouncementType::Event->value => 'bg-brand-500/10 text-brand-700 dark:text-brand-300',
        AnnouncementType::Urgent->value => 'bg-danger-solid/10 text-danger-solid',
    ];
@endphp

<x-layouts.app title="التعميمات والإعلانات">
    <div
        class="space-y-6"
        x-data="{
            open: false,
            editing: null,
            openCreate() {
                this.editing = null;
                this.open = true;
            },
            openEdit(row) {
                this.editing = row;
                this.open = true;
            },
            close() {
                this.open = false;
                this.editing = null;
            },
        }"
    >
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">التعميمات والإعلانات</h1>
                <p class="mt-1 text-sm text-mist-500">انشر تعميمات تظهر لجميع أعضاء المؤسسة في لوحة التحكم.</p>
            </div>
            @if ($canManage)
                <button
                    type="button"
                    @click="openCreate()"
                    class="inline-flex items-center justify-center rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600"
                >
                    تعميم جديد
                </button>
            @endif
        </div>

        <div class="overflow-hidden rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <div class="w-full overflow-x-auto">
                <table class="w-full min-w-max text-sm">
                    <thead>
                        <tr class="border-b border-mist-100 text-xs text-mist-500 dark:border-ink-700">
                            <th class="w-12 px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">#</th>
                            <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">العنوان</th>
                            <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">النوع</th>
                            <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-center">الحالة</th>
                            <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">النشر / الانتهاء</th>
                            <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-center">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                        @forelse ($announcements as $announcement)
                            @php
                                $editPayload = [
                                    'id' => $announcement->id,
                                    'title' => $announcement->title,
                                    'content' => $announcement->content,
                                    'type' => $announcement->type->value,
                                    'published_at' => $announcement->published_at?->format('Y-m-d\TH:i'),
                                    'expires_at' => $announcement->expires_at?->format('Y-m-d\TH:i'),
                                    'is_pinned' => $announcement->is_pinned,
                                    'action' => route('tenant.announcements.update', $announcement),
                                ];
                            @endphp
                            <tr class="hover:bg-mist-50 dark:hover:bg-ink-700/40">
                                <td class="w-12 px-3 py-2 text-center text-sm tabular-nums text-mist-500">{{ $loop->iteration + ($announcements->currentPage() - 1) * $announcements->perPage() }}</td>
                                <td class="px-3 py-2 text-start">
                                    <p class="font-medium text-ink-900 dark:text-ink-50">
                                        @if ($announcement->is_pinned)
                                            <span class="me-1 text-amber-500"><svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z" /></svg></span>
                                        @endif
                                        {{ $announcement->title }}
                                    </p>
                                    <p class="mt-0.5 line-clamp-1 text-xs text-mist-500">{{ $announcement->content }}</p>
                                </td>
                                <td class="px-3 py-2 text-start">
                                    <span @class(['rounded-md px-2 py-0.5 text-xs font-semibold', $typeBadges[$announcement->type->value] ?? ''])>
                                        {{ $announcement->type->label() }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    @if ($announcement->isActive())
                                        <span class="rounded-md bg-brand-500/10 px-2 py-0.5 text-xs font-semibold text-brand-700 dark:text-brand-300">نشط</span>
                                    @else
                                        <span class="rounded-md bg-mist-100 px-2 py-0.5 text-xs font-semibold text-mist-500 dark:bg-ink-700">منتهٍ</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-start text-xs text-mist-500">
                                    <x-ui.ltr>{{ $announcement->published_at?->format('Y-m-d H:i') ?? '—' }}</x-ui.ltr>
                                    <br>
                                    <x-ui.ltr>{{ $announcement->expires_at?->format('Y-m-d H:i') ?? 'بدون انتهاء' }}</x-ui.ltr>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    @if ($canManage)
                                        <div class="flex justify-center gap-2">
                                            <button type="button" @click="openEdit(@js($editPayload))" class="rounded-lg border border-mist-200 px-2.5 py-1 text-xs font-semibold dark:border-ink-600">تعديل</button>
                                            <form method="POST" action="{{ route('tenant.announcements.destroy', $announcement) }}" data-swal-confirm data-swal-title="حذف هذا التعميم؟">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg border border-mist-200 px-2.5 py-1 text-xs font-semibold text-danger-solid dark:border-ink-600">حذف</button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-ui.table-empty :colspan="6" icon="megaphone" message="لا توجد تعميمات بعد." />
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($announcements->hasPages())
                <div class="border-t border-mist-100 px-4 py-3 dark:border-ink-700">{{ $announcements->links() }}</div>
            @endif
        </div>

        @if ($canManage)
            <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-ink-950/50 p-4">
                <div class="w-full max-w-lg rounded-2xl bg-white p-4 shadow-xl dark:bg-ink-800" @click.outside="close()">
                    <h3 class="font-semibold text-ink-900 dark:text-ink-50" x-text="editing ? 'تعديل تعميم' : 'تعميم جديد'"></h3>
                    <form
                        method="POST"
                        class="mt-4 space-y-3"
                        :action="editing ? editing.action : @js(route('tenant.announcements.store'))"
                    >
                        @csrf
                        <input type="hidden" name="_method" value="PUT" x-bind:disabled="!editing">
                        <input type="text" name="title" required placeholder="العنوان" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900" :value="editing?.title || ''">
                        <textarea name="content" required rows="4" placeholder="نص التعميم" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900" :value="editing?.content || ''"></textarea>
                        <select name="type" required class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900">
                            @foreach ($types as $type)
                                <option value="{{ $type->value }}" :selected="(editing?.type || 'info') === '{{ $type->value }}'">{{ $type->label() }}</option>
                            @endforeach
                        </select>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs text-mist-500">تاريخ النشر</label>
                                <input type="datetime-local" name="published_at" dir="ltr" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900" :value="editing?.published_at || ''">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs text-mist-500">تاريخ الانتهاء (اختياري)</label>
                                <input type="datetime-local" name="expires_at" dir="ltr" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900" :value="editing?.expires_at || ''">
                            </div>
                        </div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_pinned" value="1" class="rounded border-mist-300 text-brand-500" :checked="!!editing?.is_pinned">
                            تثبيت في أعلى الشريط
                        </label>
                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 rounded-xl bg-brand-500 py-2 text-sm font-semibold text-white">حفظ</button>
                            <button type="button" @click="close()" class="rounded-xl border border-mist-200 px-4 py-2 text-sm dark:border-ink-600">إلغاء</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>
