<x-layouts.app title="سلة المحذوفات">
    <div
        x-data="veyraTrashManager()"
        class="space-y-6"
    >
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">سلة المحذوفات</h2>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">
                    العناصر المحذوفة ناعماً (Soft Delete). يمكنك استعادتها أو حذفها نهائياً.
                    <span class="font-semibold text-ink-700 dark:text-mist-200">({{ $items->count() }} / {{ $totalCount }})</span>
                </p>
            </div>

            @can('tenant.trash.force_delete')
                <form
                    method="POST"
                    action="{{ route('tenant.trash.empty') }}"
                    data-swal-confirm
                    data-swal-title="تفريغ سلة المحذوفات؟"
                    data-swal-text="سيتم الحذف النهائي لجميع العناصر المعروضة في الفلتر الحالي. لا يمكن التراجع."
                    data-swal-confirm-button="نعم، فرّغ السلة"
                >
                    @csrf
                    @method('DELETE')
                    @if ($activeType)
                        <input type="hidden" name="type" value="{{ $activeType }}">
                    @endif
                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 rounded-xl border border-danger-solid/40 px-4 py-2 text-sm font-semibold text-danger-solid transition hover:bg-danger-solid/10 disabled:opacity-40"
                        @disabled($items->isEmpty())
                    >
                        تفريغ السلة
                    </button>
                </form>
            @endcan
        </div>

        <div class="flex gap-2 overflow-x-auto pb-1">
            <a
                href="{{ route('tenant.trash.index') }}"
                @class([
                    'shrink-0 rounded-full px-3 py-1.5 text-xs font-semibold transition',
                    'bg-emerald-400 text-emerald-900' => blank($activeType),
                    'bg-mist-100 text-mist-600 hover:bg-mist-200 dark:bg-ink-700 dark:text-mist-300' => filled($activeType),
                ])
            >الكل</a>
            @foreach ($types as $key => $config)
                <a
                    href="{{ route('tenant.trash.index', ['type' => $key]) }}"
                    @class([
                        'shrink-0 rounded-full px-3 py-1.5 text-xs font-semibold transition',
                        'bg-emerald-400 text-emerald-900' => $activeType === $key,
                        'bg-mist-100 text-mist-600 hover:bg-mist-200 dark:bg-ink-700 dark:text-mist-300' => $activeType !== $key,
                    ])
                >{{ $config['label'] }}</a>
            @endforeach
        </div>

        <div class="flex flex-wrap items-center gap-2" x-show="selected.length > 0" x-cloak>
            @can('tenant.trash.restore')
                <form method="POST" action="{{ route('tenant.trash.restore-selected') }}" @submit.prevent="submitBulk($el)">
                    @csrf
                    <template x-for="token in selected" :key="token">
                        <input type="hidden" name="items[]" :value="token">
                    </template>
                    @if ($activeType)
                        <input type="hidden" name="type" value="{{ $activeType }}">
                    @endif
                    <button type="submit" class="rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-glow transition hover:bg-emerald-300">
                        استعادة المحدد (<span x-text="selected.length"></span>)
                    </button>
                </form>
            @endcan

            @can('tenant.trash.force_delete')
                <form
                    method="POST"
                    action="{{ route('tenant.trash.force-selected') }}"
                    data-swal-confirm
                    data-swal-title="حذف نهائي للمحدد؟"
                    data-swal-text="حذف نهائي لا يمكن التراجع عنه"
                    data-swal-confirm-button="نعم، احذف نهائياً"
                >
                    @csrf
                    @method('DELETE')
                    <template x-for="token in selected" :key="'force-'+token">
                        <input type="hidden" name="items[]" :value="token">
                    </template>
                    @if ($activeType)
                        <input type="hidden" name="type" value="{{ $activeType }}">
                    @endif
                    <button type="submit" class="rounded-xl border border-danger-solid/40 px-4 py-2 text-sm font-semibold text-danger-solid transition hover:bg-danger-solid/10">
                        حذف نهائي للمحدد
                    </button>
                </form>
            @endcan
        </div>

        <div class="overflow-x-auto rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                <thead class="bg-mist-50 text-mist-500 dark:bg-ink-900 dark:text-mist-400">
                    <tr>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">
                            <input
                                type="checkbox"
                                class="rounded border-mist-300 text-emerald-500 focus:ring-emerald-400"
                                @change="toggleAll($event.target.checked)"
                                :checked="allSelected"
                                :indeterminate="partialSelected"
                            >
                        </th>
                        {{-- The selection checkbox is a control, not data, so it keeps column 1 and # follows it. --}}
                        <th class="w-12 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">#</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">النوع</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">العنصر</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">تاريخ الحذف</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                    @forelse ($items as $item)
                        @php
                            $token = $item['type'].':'.$item['id'];
                        @endphp
                        <tr>
                            <td class="px-4 py-3 text-start">
                                <input
                                    type="checkbox"
                                    class="rounded border-mist-300 text-emerald-500 focus:ring-emerald-400"
                                    value="{{ $token }}"
                                    @change="toggle('{{ $token }}', $event.target.checked)"
                                    :checked="selected.includes('{{ $token }}')"
                                >
                            </td>
                            <td class="w-12 px-4 py-3 text-center text-sm tabular-nums text-mist-500">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3 text-start">
                                <span class="rounded-full bg-emerald-500/10 px-2 py-0.5 text-[10px] font-semibold text-emerald-600 dark:text-emerald-400">
                                    {{ $item['type_label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-start">
                                <p class="font-medium text-ink-900 dark:text-ink-50">{{ $item['title'] }}</p>
                                @if ($item['subtitle'])
                                    <p class="text-xs text-mist-500">{{ $item['subtitle'] }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-mist-500 text-start">
                                {{ $item['deleted_at']?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-end gap-2">
                                    @can('tenant.trash.restore')
                                        <form method="POST" action="{{ $item['restore_url'] }}">
                                            @csrf
                                            <button type="submit" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold text-emerald-600 dark:border-ink-600 dark:text-emerald-400">استعادة</button>
                                        </form>
                                    @endcan
                                    @can('tenant.trash.force_delete')
                                        <form
                                            method="POST"
                                            action="{{ $item['force_url'] }}"
                                            data-swal-confirm
                                            data-swal-title="حذف نهائي؟"
                                            data-swal-text="حذف نهائي لا يمكن التراجع عنه"
                                            data-swal-confirm-button="نعم، احذف نهائياً"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            @if ($activeType)
                                                <input type="hidden" name="type" value="{{ $activeType }}">
                                            @endif
                                            <button type="submit" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold text-danger-solid dark:border-ink-600">حذف نهائي</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-ui.table-empty :colspan="6" icon="🗑️" message="لا توجد عناصر في سلة المحذوفات." />
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('veyraTrashManager', () => ({
                    selected: [],
                    allTokens: @js($items->map(fn ($item) => $item['type'].':'.$item['id'])->values()->all()),

                    get allSelected() {
                        return this.allTokens.length > 0 && this.selected.length === this.allTokens.length;
                    },

                    get partialSelected() {
                        return this.selected.length > 0 && this.selected.length < this.allTokens.length;
                    },

                    toggle(token, checked) {
                        if (checked) {
                            if (! this.selected.includes(token)) {
                                this.selected.push(token);
                            }
                            return;
                        }

                        this.selected = this.selected.filter((value) => value !== token);
                    },

                    toggleAll(checked) {
                        this.selected = checked ? [...this.allTokens] : [];
                    },

                    prepareBulk(form) {
                        form.querySelectorAll('input[name="items[]"]').forEach((input) => input.remove());
                        this.selected.forEach((token) => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'items[]';
                            input.value = token;
                            form.appendChild(input);
                        });
                    },

                    submitBulk(form) {
                        this.prepareBulk(form);
                        form.submit();
                    },
                }));
            });
        </script>
    @endpush
</x-layouts.app>
