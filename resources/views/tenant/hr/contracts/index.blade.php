@php
    use App\Domain\Tenancy\Enums\ContractStatus;

    $statusClasses = [
        ContractStatus::Active->value => 'bg-brand-500/15 text-brand-700 dark:text-brand-300',
        ContractStatus::Expired->value => 'bg-mist-200 text-mist-700 dark:bg-ink-700 dark:text-mist-300',
        ContractStatus::Terminated->value => 'bg-danger-solid/10 text-danger-solid',
    ];
@endphp

<x-layouts.app title="العقود">
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">العقود</h1>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">إدارة عقود الموظفين وتنبيهات الانتهاء.</p>
            </div>
            @can('hr.contracts.create')
                <a href="{{ route('hr.contracts.create') }}" class="inline-flex items-center justify-center rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-glow transition hover:bg-brand-600">
                    إضافة عقد
                </a>
            @endcan
        </div>

        @if ($expiringSoon->isNotEmpty())
            <div class="rounded-2xl border border-amber-300/60 bg-amber-50/80 p-4 dark:border-amber-500/30 dark:bg-amber-500/10">
                <p class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                    عقود تنتهي خلال 30 يوماً ({{ $expiringSoon->count() }})
                </p>
                <ul class="mt-2 space-y-1 text-sm text-amber-700 dark:text-amber-300">
                    @foreach ($expiringSoon->take(5) as $expiring)
                        <li>
                            {{ $expiring->employee?->full_name ?? '—' }} —
                            <span dir="ltr">{{ $expiring->end_date?->format('Y-m-d') }}</span>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('hr.contracts.index', ['expiring' => 1]) }}" class="mt-2 inline-block text-xs font-bold text-amber-800 underline dark:text-amber-200">عرض العقود المنتهية قريباً</a>
            </div>
        @endif

        <form method="GET" action="{{ route('hr.contracts.index') }}" class="flex flex-wrap items-end gap-3 rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <div>
                <label for="status" class="mb-1.5 block text-xs font-medium text-mist-500">الحالة</label>
                <select id="status" name="status" class="rounded-xl border border-mist-200 bg-white px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50">
                    <option value="all" @selected($filters['status'] === 'all')>الكل</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <label class="inline-flex items-center gap-2 pb-2 text-sm text-ink-700 dark:text-mist-200">
                <input type="checkbox" name="expiring" value="1" @checked($filters['expiring'])>
                تنتهي خلال 30 يوماً
            </label>
            <button type="submit" class="rounded-xl bg-ink-900 px-4 py-2 text-sm font-semibold text-white dark:bg-ink-50 dark:text-ink-900">تصفية</button>
        </form>

        <div class="w-full overflow-x-auto rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                <thead class="bg-mist-50 dark:bg-ink-900">
                    <tr>
                        <th class="w-12 px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">#</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">الموظف</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">نوع العقد</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">البداية</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">النهاية</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-center">الحالة</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                    @forelse ($contracts as $contract)
                        <tr @class([
                            'transition hover:bg-mist-50/80 dark:hover:bg-ink-900/40',
                            'bg-amber-50/50 dark:bg-amber-500/5' => $contract->isExpiringSoon(),
                        ])>
                            <td class="w-12 px-3 py-2 text-center text-sm tabular-nums text-mist-500">{{ $loop->iteration }}</td>
                            <td class="px-3 py-2 font-medium text-ink-900 dark:text-ink-50 text-start">{{ $contract->employee?->full_name ?? '—' }}</td>
                            <td class="px-3 py-2 text-mist-500 text-start">{{ $contract->contract_type->label() }}</td>
                            <td class="px-3 py-2 tabular-nums text-mist-500 text-start"><x-ui.ltr>{{ $contract->start_date?->format('Y-m-d') }}</x-ui.ltr></td>
                            <td class="px-3 py-2 tabular-nums text-mist-500 text-start"><x-ui.ltr>{{ $contract->end_date?->format('Y-m-d') ?? '—' }}</x-ui.ltr></td>
                            <td class="px-3 py-2 text-center">
                                <span @class(['inline-flex rounded-md px-2.5 py-0.5 text-xs font-semibold', $statusClasses[$contract->status->value] ?? ''])>
                                    {{ $contract->status->label() }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <div class="flex items-center justify-end gap-2">
                                    @can('hr.contracts.update')
                                        <a href="{{ route('hr.contracts.edit', $contract) }}" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold transition hover:border-brand-500 hover:text-brand-600 dark:border-ink-600">تعديل</a>
                                    @endcan
                                    @can('hr.contracts.delete')
                                        <form method="POST" action="{{ route('hr.contracts.destroy', $contract) }}" data-swal-confirm data-swal-title="حذف هذا العقد؟">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold text-danger-solid dark:border-ink-600">حذف</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-ui.table-empty :colspan="7" icon="document" message="لا توجد عقود بعد." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $contracts->links() }}</div>
    </div>
</x-layouts.app>
