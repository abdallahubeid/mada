@extends('layouts.admin')

@section('title', 'سلة المحذوفات')

@section('breadcrumbs')
    <span class="text-mist-500 dark:text-mist-400">المنصّة</span>
    <span class="mx-1.5 text-mist-300 dark:text-mist-600">/</span>
    <span class="text-ink-700 dark:text-mist-200">سلة المحذوفات</span>
@endsection

@section('content')
    <div
        x-data="madaTrashManager()"
        class="space-y-6"
    >
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">سلة المحذوفات</h2>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">
                    العناصر المحذوفة ناعماً (Soft Delete). يمكن استعادتها أو حذفها نهائياً.
                    <span class="font-semibold text-ink-700 dark:text-mist-200">({{ $items->count() }} / {{ $totalCount }})</span>
                </p>
            </div>

            @can('trash.force_delete')
                <form
                    method="POST"
                    action="{{ route('admin.trash.empty') }}"
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

        <div class="flex flex-wrap gap-2">
            <a
                href="{{ route('admin.trash.index') }}"
                @class([
                    'rounded-md px-3 py-1.5 text-xs font-semibold transition',
                    'bg-brand-500 text-white' => blank($activeType),
                    'bg-mist-100 text-mist-600 hover:bg-mist-200 dark:bg-ink-700 dark:text-mist-300' => filled($activeType),
                ])
            >الكل</a>
            @foreach ($types as $key => $config)
                <a
                    href="{{ route('admin.trash.index', ['type' => $key]) }}"
                    @class([
                        'rounded-md px-3 py-1.5 text-xs font-semibold transition',
                        'bg-brand-500 text-white' => $activeType === $key,
                        'bg-mist-100 text-mist-600 hover:bg-mist-200 dark:bg-ink-700 dark:text-mist-300' => $activeType !== $key,
                    ])
                >{{ $config['label'] }}</a>
            @endforeach
        </div>

        <div class="flex flex-wrap items-center gap-2" x-show="selected.length > 0" x-cloak>
            @can('trash.restore')
                <form method="POST" action="{{ route('admin.trash.restore-selected') }}" @submit.prevent="submitBulk($el)">
                    @csrf
                    <template x-for="token in selected" :key="token">
                        <input type="hidden" name="items[]" :value="token">
                    </template>
                    @if ($activeType)
                        <input type="hidden" name="type" value="{{ $activeType }}">
                    @endif
                    <button type="submit" class="rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-glow transition hover:bg-brand-600">
                        استعادة المحدد (<span x-text="selected.length"></span>)
                    </button>
                </form>
            @endcan

            @can('trash.force_delete')
                <form
                    method="POST"
                    action="{{ route('admin.trash.force-selected') }}"
                    data-swal-confirm
                    data-swal-title="حذف نهائي للمحدد؟"
                    data-swal-text="لا يمكن التراجع عن الحذف النهائي."
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
                        <th class="px-3 py-2 text-start">
                            <input
                                type="checkbox"
                                class="rounded border-mist-300 text-brand-500 focus:ring-brand-500"
                                @change="toggleAll($event.target.checked)"
                                :checked="allSelected"
                                :indeterminate="partialSelected"
                            >
                        </th>
                        <th class="px-3 py-2 text-start font-medium">النوع</th>
                        <th class="px-3 py-2 text-start font-medium">العنصر</th>
                        <th class="px-3 py-2 text-start font-medium">تاريخ الحذف</th>
                        <th class="px-3 py-2 text-end font-medium">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                    @forelse ($items as $item)
                        @php
                            $token = $item['type'].':'.$item['id'];
                        @endphp
                        <tr>
                            <td class="px-3 py-2">
                                <input
                                    type="checkbox"
                                    class="rounded border-mist-300 text-brand-500 focus:ring-brand-500"
                                    value="{{ $token }}"
                                    @change="toggle('{{ $token }}', $event.target.checked)"
                                    :checked="selected.includes('{{ $token }}')"
                                >
                            </td>
                            <td class="px-3 py-2">
                                <span class="rounded-md bg-brand-500/10 px-2 py-0.5 text-xs font-semibold text-brand-600 dark:text-brand-300">
                                    {{ $item['type_label'] }}
                                </span>
                            </td>
                            <td class="px-3 py-2">
                                <p class="font-medium text-ink-900 dark:text-ink-50">{{ $item['title'] }}</p>
                                @if ($item['subtitle'])
                                    <p class="text-xs text-mist-500">{{ $item['subtitle'] }}</p>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-mist-500">
                                {{ $item['deleted_at']?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex items-center justify-end gap-2">
                                    @can('trash.restore')
                                        <form method="POST" action="{{ $item['restore_url'] }}">
                                            @csrf
                                            <button type="submit" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold text-brand-600 dark:border-ink-600 dark:text-brand-300">استعادة</button>
                                        </form>
                                    @endcan
                                    @can('trash.force_delete')
                                        <form
                                            method="POST"
                                            action="{{ $item['force_url'] }}"
                                            data-swal-confirm
                                            data-swal-title="حذف نهائي؟"
                                            data-swal-text="لا يمكن التراجع عن هذا الإجراء."
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
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-mist-500">لا توجد عناصر في سلة المحذوفات.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('madaTrashManager', () => ({
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
