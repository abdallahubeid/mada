@php
    use App\Domain\Tenancy\Enums\AssetStatus;

    $statusBadges = [
        'available' => 'bg-brand-500/10 text-brand-700 dark:text-brand-300',
        'assigned' => 'bg-sky-500/10 text-sky-700 dark:text-sky-300',
        'under_maintenance' => 'bg-amber-500/10 text-amber-800 dark:text-amber-300',
        'retired' => 'bg-mist-100 text-mist-600 dark:bg-ink-700 dark:text-mist-300',
    ];
@endphp

<x-layouts.app title="إدارة العُهد والأصول">
    <div
        class="space-y-6"
        x-data="{
            createOpen: false,
            editOpen: false,
            assignOpen: false,
            returnOpen: false,
            editing: null,
            assigning: null,
            returning: null,
            openEdit(row) { this.editing = row; this.editOpen = true; },
            openAssign(row) { this.assigning = row; this.assignOpen = true; },
            openReturn(row) { this.returning = row; this.returnOpen = true; },
        }"
    >
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">إدارة العُهد والأصول</h1>
                <p class="mt-1 text-sm text-mist-500">تتبّع أصول الشركة وإسنادها للموظفين مع سجل العهدة.</p>
            </div>
            @if ($canManage)
                <button type="button" @click="createOpen = true" class="inline-flex items-center justify-center rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">
                    إضافة أصل
                </button>
            @endif
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <p class="text-sm text-mist-500">إجمالي الأصول</p>
                <p class="mt-2 font-display text-3xl font-medium text-ink-900 dark:text-ink-50" data-testid="kpi-assets-total">{{ $kpis['total'] }}</p>
            </div>
            <div class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <p class="text-sm text-mist-500">مُسندة</p>
                <p class="mt-2 font-display text-3xl font-medium text-ink-900 dark:text-ink-50" data-testid="kpi-assets-assigned">{{ $kpis['assigned'] }}</p>
            </div>
            <div class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <p class="text-sm text-mist-500">متاحة</p>
                <p class="mt-2 font-display text-3xl font-medium text-ink-900 dark:text-ink-50" data-testid="kpi-assets-available">{{ $kpis['available'] }}</p>
            </div>
            <div class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <p class="text-sm text-mist-500">تحت الصيانة</p>
                <p class="mt-2 font-display text-3xl font-medium text-ink-900 dark:text-ink-50" data-testid="kpi-assets-maintenance">{{ $kpis['maintenance'] }}</p>
            </div>
        </div>

        <form method="GET" action="{{ route('tenant.assets.index') }}" class="grid gap-3 rounded-2xl border border-mist-200 bg-white p-4 shadow-sm sm:grid-cols-4 dark:border-ink-600 dark:bg-ink-800">
            <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="بحث بالاسم أو الرمز..." class="rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900 sm:col-span-2">
            <select name="category" class="rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900">
                <option value="all" @selected($filters['category'] === 'all')>كل الفئات</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->value }}" @selected($filters['category'] === $category->value)>{{ $category->label() }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <select name="status" class="min-w-0 flex-1 rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900">
                    <option value="all" @selected($filters['status'] === 'all')>كل الحالات</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
                <button type="submit" class="rounded-xl bg-brand-500 px-3 py-2 text-sm font-semibold text-white">تصفية</button>
            </div>
        </form>

        <div class="overflow-hidden rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <div class="w-full overflow-x-auto">
                <table class="w-full min-w-max text-sm">
                    <thead>
                        <tr class="border-b border-mist-100 text-xs text-mist-500 dark:border-ink-700">
                            <th class="w-12 px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">#</th>
                            <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">رمز الأصل</th>
                            <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">الاسم</th>
                            <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">الفئة</th>
                            <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-center">الحالة</th>
                            <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">الموظف</th>
                            <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-center">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                        @forelse ($assets as $asset)
                            @php
                                $editPayload = [
                                    'id' => $asset->id,
                                    'name' => $asset->name,
                                    'asset_code' => $asset->asset_code,
                                    'category' => $asset->category->value,
                                    'serial_number' => $asset->serial_number,
                                    'purchase_date' => $asset->purchase_date?->format('Y-m-d'),
                                    'purchase_cost' => $asset->purchase_cost,
                                    'status' => $asset->status->value,
                                    'notes' => $asset->notes,
                                    'action' => route('tenant.assets.update', $asset),
                                ];
                                $assignPayload = [
                                    'id' => $asset->id,
                                    'label' => $asset->asset_code.' — '.$asset->name,
                                    'action' => route('tenant.assets.assign', $asset),
                                ];
                                $returnPayload = [
                                    'id' => $asset->id,
                                    'label' => $asset->asset_code.' — '.$asset->name,
                                    'employee' => $asset->currentAssignment?->employee?->full_name,
                                    'action' => route('tenant.assets.return', $asset),
                                ];
                            @endphp
                            <tr class="hover:bg-mist-50 dark:hover:bg-ink-700/40">
                                <td class="w-12 px-3 py-2 text-center text-sm tabular-nums text-mist-500">{{ $loop->iteration + ($assets->currentPage() - 1) * $assets->perPage() }}</td>
                                <td class="px-3 py-2 font-mono text-xs text-ink-800 dark:text-ink-100 text-start"><x-ui.ltr>{{ $asset->asset_code }}</x-ui.ltr></td>
                                <td class="px-3 py-2 font-medium text-ink-900 dark:text-ink-50 text-start">{{ $asset->name }}</td>
                                <td class="px-3 py-2 text-start">
                                    <span class="rounded-md bg-mist-100 px-2 py-0.5 text-xs font-semibold text-mist-600 dark:bg-ink-700 dark:text-mist-300">{{ $asset->category->label() }}</span>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <span @class(['rounded-md px-2 py-0.5 text-xs font-semibold', $statusBadges[$asset->status->value] ?? ''])>
                                        {{ $asset->status->label() }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-start">
                                    @if ($asset->currentAssignment?->employee)
                                        <a href="{{ route('hr.employees.show', [$asset->currentAssignment->employee, 'tab' => 'assets']) }}" class="font-medium text-brand-700 hover:underline dark:text-brand-300">
                                            {{ $asset->currentAssignment->employee->full_name }}
                                        </a>
                                    @else
                                        <span class="text-mist-400">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-center">
                                    @if ($canManage)
                                        <div class="flex flex-wrap justify-end gap-2">
                                            @if ($asset->status === AssetStatus::Available)
                                                <button type="button" @click="openAssign(@js($assignPayload))" class="rounded-lg border border-mist-200 px-2.5 py-1 text-xs font-semibold dark:border-ink-600">إسناد</button>
                                            @endif
                                            @if ($asset->status === AssetStatus::Assigned)
                                                <button type="button" @click="openReturn(@js($returnPayload))" class="rounded-lg border border-mist-200 px-2.5 py-1 text-xs font-semibold dark:border-ink-600">إعادة</button>
                                            @endif
                                            <button type="button" @click="openEdit(@js($editPayload))" class="rounded-lg border border-mist-200 px-2.5 py-1 text-xs font-semibold dark:border-ink-600">تعديل</button>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-ui.table-empty :colspan="7" icon="archive" message="لا توجد أصول بعد." />
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($assets->hasPages())
                <div class="border-t border-mist-100 px-4 py-3 dark:border-ink-700">{{ $assets->links() }}</div>
            @endif
        </div>

        @if ($canManage)
            {{-- Create --}}
            <div x-show="createOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-ink-950/50 p-4">
                <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-4 shadow-xl dark:bg-ink-800" @click.outside="createOpen = false">
                    <h3 class="font-semibold">إضافة أصل</h3>
                    <form method="POST" action="{{ route('tenant.assets.store') }}" class="mt-4 space-y-3">
                        @csrf
                        <input type="text" name="name" required placeholder="اسم الأصل" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900">
                        <input type="text" name="asset_code" dir="ltr" placeholder="رمز الأصل (اختياري) — {{ $nextCode }}" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900">
                        <select name="category" required class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900">
                            @foreach ($categories as $category)
                                <option value="{{ $category->value }}">{{ $category->label() }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="serial_number" dir="ltr" placeholder="الرقم التسلسلي" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <input type="date" name="purchase_date" dir="ltr" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900">
                            <input type="number" step="0.01" min="0" name="purchase_cost" placeholder="التكلفة" dir="ltr" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900">
                        </div>
                        <select name="status" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900">
                            <option value="available">متاح</option>
                            <option value="under_maintenance">تحت الصيانة</option>
                            <option value="retired">مستبعد</option>
                        </select>
                        <textarea name="notes" rows="2" placeholder="ملاحظات" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900"></textarea>
                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 rounded-xl bg-brand-500 py-2 text-sm font-semibold text-white">حفظ</button>
                            <button type="button" @click="createOpen = false" class="rounded-xl border border-mist-200 px-4 py-2 text-sm dark:border-ink-600">إلغاء</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Edit --}}
            <div x-show="editOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-ink-950/50 p-4">
                <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-4 shadow-xl dark:bg-ink-800" @click.outside="editOpen = false">
                    <h3 class="font-semibold">تعديل أصل</h3>
                    <form method="POST" class="mt-4 space-y-3" :action="editing?.action">
                        @csrf
                        @method('PUT')
                        <input type="text" name="name" required class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900" :value="editing?.name || ''">
                        <input type="text" name="asset_code" required dir="ltr" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900" :value="editing?.asset_code || ''">
                        <select name="category" required class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900">
                            @foreach ($categories as $category)
                                <option value="{{ $category->value }}" :selected="editing?.category === '{{ $category->value }}'">{{ $category->label() }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="serial_number" dir="ltr" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900" :value="editing?.serial_number || ''">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <input type="date" name="purchase_date" dir="ltr" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900" :value="editing?.purchase_date || ''">
                            <input type="number" step="0.01" min="0" name="purchase_cost" dir="ltr" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900" :value="editing?.purchase_cost || ''">
                        </div>
                        <select name="status" required class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900">
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" :selected="editing?.status === '{{ $status->value }}'">{{ $status->label() }}</option>
                            @endforeach
                        </select>
                        <textarea name="notes" rows="2" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900" :value="editing?.notes || ''"></textarea>
                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 rounded-xl bg-brand-500 py-2 text-sm font-semibold text-white">تحديث</button>
                            <button type="button" @click="editOpen = false" class="rounded-xl border border-mist-200 px-4 py-2 text-sm dark:border-ink-600">إلغاء</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Assign --}}
            <div x-show="assignOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-ink-950/50 p-4">
                <div class="w-full max-w-md rounded-2xl bg-white p-4 shadow-xl dark:bg-ink-800" @click.outside="assignOpen = false">
                    <h3 class="font-semibold">إسناد أصل</h3>
                    <p class="mt-1 text-sm text-mist-500" x-text="assigning?.label"></p>
                    <form method="POST" class="mt-4 space-y-3" :action="assigning?.action">
                        @csrf
                        <select name="employee_id" required class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900">
                            <option value="">اختر موظفاً نشطاً</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->full_name }}</option>
                            @endforeach
                        </select>
                        <input type="datetime-local" name="assigned_at" dir="ltr" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900">
                        <select name="condition_on_assign" required class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900">
                            @foreach ($conditions as $condition)
                                <option value="{{ $condition->value }}" @selected($condition->value === 'good')>{{ $condition->label() }}</option>
                            @endforeach
                        </select>
                        <textarea name="notes" rows="2" placeholder="ملاحظات الإسناد" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900"></textarea>
                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 rounded-xl bg-brand-500 py-2 text-sm font-semibold text-white">إسناد</button>
                            <button type="button" @click="assignOpen = false" class="rounded-xl border border-mist-200 px-4 py-2 text-sm dark:border-ink-600">إلغاء</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Return --}}
            <div x-show="returnOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-ink-950/50 p-4">
                <div class="w-full max-w-md rounded-2xl bg-white p-4 shadow-xl dark:bg-ink-800" @click.outside="returnOpen = false">
                    <h3 class="font-semibold">إعادة أصل</h3>
                    <p class="mt-1 text-sm text-mist-500">
                        <span x-text="returning?.label"></span>
                        <template x-if="returning?.employee">
                            <span> · من <span x-text="returning.employee"></span></span>
                        </template>
                    </p>
                    <form method="POST" class="mt-4 space-y-3" :action="returning?.action">
                        @csrf
                        <input type="datetime-local" name="returned_at" dir="ltr" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900">
                        <select name="condition_on_return" required class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900">
                            @foreach ($conditions as $condition)
                                <option value="{{ $condition->value }}">{{ $condition->label() }}</option>
                            @endforeach
                        </select>
                        <select name="status" required class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900">
                            <option value="available">متاح</option>
                            <option value="under_maintenance">تحت الصيانة</option>
                            <option value="retired">مستبعد</option>
                        </select>
                        <textarea name="notes" rows="2" placeholder="ملاحظات الإعادة" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900"></textarea>
                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 rounded-xl bg-brand-500 py-2 text-sm font-semibold text-white">تأكيد الإعادة</button>
                            <button type="button" @click="returnOpen = false" class="rounded-xl border border-mist-200 px-4 py-2 text-sm dark:border-ink-600">إلغاء</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>
