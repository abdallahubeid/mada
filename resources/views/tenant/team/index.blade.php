<x-layouts.app title="أعضاء الفريق">
    @php
        $inputClass = 'ui-control w-full';
    @endphp

    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">أعضاء الفريق</h1>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">إدارة حسابات المستخدمين والأدوار والأقسام داخل المؤسسة.</p>
            </div>
            @can('tenant.users.manage')
                <a
                    href="{{ route('team.create') }}"
                    class="ui-btn ui-btn-primary"
                >
                    إضافة عضو
                </a>
            @endcan
        </div>

        <form method="GET" action="{{ route('team.index') }}" class="ui-card grid gap-3 p-4 sm:grid-cols-[1fr_220px_auto]">
            <input
                type="search"
                name="q"
                value="{{ $filters['q'] }}"
                placeholder="بحث بالاسم أو البريد..."
                class="{{ $inputClass }}"
            >
            <select name="department_id" aria-label="تصفية حسب القسم" class="{{ $inputClass }}">
                <option value="all" @selected($filters['department_id'] === 'all')>كل الأقسام</option>
                @foreach ($departments as $id => $name)
                    <option value="{{ $id }}" @selected($filters['department_id'] === (string) $id)>{{ $name }}</option>
                @endforeach
            </select>
            <button type="submit" class="ui-btn ui-btn-secondary">
                تصفية
            </button>
        </form>

        <div class="ui-card w-full overflow-x-auto">
            <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                <thead class="bg-mist-50 dark:bg-ink-900">
                    <tr>
                        <th class="w-12 px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">#</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">العضو</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">البريد</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">القسم</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">الدور</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-center">الحالة</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                    @forelse ($members as $member)
                        @php
                            $roleName = $member->roles->first()?->name;
                            $roleLabel = $roleName ? ($roleLabels[$roleName] ?? $roleName) : '—';
                        @endphp
                        <tr class="transition hover:bg-mist-50/80 dark:hover:bg-ink-900/40">
                            <td class="w-12 px-3 py-2 text-center text-sm tabular-nums text-mist-500">{{ $loop->iteration }}</td>
                            <td class="px-3 py-2 text-start">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $member->avatar_url }}" alt="" class="h-9 w-9 rounded-full object-cover ring-1 ring-mist-200 dark:ring-ink-600">
                                    <span class="font-medium text-ink-900 dark:text-ink-50">{{ $member->name }}</span>
                                </div>
                            </td>
                            <td class="px-3 py-2 text-mist-500 text-start"><x-ui.ltr>{{ $member->email }}</x-ui.ltr></td>
                            <td class="px-3 py-2 text-start">
                                @if ($member->department)
                                    <span class="inline-flex rounded-md bg-mist-100 px-2.5 py-0.5 text-xs font-semibold text-ink-700 dark:bg-ink-700 dark:text-mist-200">
                                        {{ $member->department->name }}
                                    </span>
                                @else
                                    <span class="text-mist-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-start">
                                <span class="inline-flex rounded-md bg-brand-500/15 px-2.5 py-0.5 text-xs font-semibold text-brand-700 dark:text-brand-300">
                                    {{ $roleLabel }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-center">
                                @if ($member->is_active)
                                    <span class="inline-flex rounded-md bg-brand-500/15 px-2.5 py-0.5 text-xs font-semibold text-brand-700 dark:text-brand-300">نشط</span>
                                @else
                                    <span class="inline-flex rounded-md bg-mist-200 px-2.5 py-0.5 text-xs font-semibold text-mist-600 dark:bg-ink-700 dark:text-mist-300">معطّل</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-center">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    @can('tenant.users.manage')
                                        <a
                                            href="{{ route('team.edit', $member) }}"
                                            class="ui-btn ui-btn-secondary ui-btn-sm"
                                        >
                                            تعديل
                                        </a>
                                        <form
                                            method="POST"
                                            action="{{ route('team.toggle-status', $member) }}"
                                            data-swal-confirm
                                            data-swal-variant="{{ $member->is_active ? 'warning' : 'info' }}"
                                            data-swal-title="{{ $member->is_active ? 'تعطيل هذا العضو؟' : 'تفعيل هذا العضو؟' }}"
                                            data-swal-text="{{ $member->is_active ? 'لن يتمكن من تسجيل الدخول حتى إعادة التفعيل. لن يُحذف حسابه ولا بياناته.' : 'سيتمكن من تسجيل الدخول مجدداً.' }}"
                                            data-swal-confirm-button="{{ $member->is_active ? 'نعم، عطّل العضو' : 'نعم، فعّل العضو' }}"
                                        >
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="ui-btn ui-btn-secondary ui-btn-sm">
                                                {{ $member->is_active ? 'تعطيل' : 'تفعيل' }}
                                            </button>
                                        </form>
                                        <form
                                            method="POST"
                                            action="{{ route('team.destroy', $member) }}"
                                            data-swal-confirm
                                            data-swal-title="حذف هذا العضو؟"
                                            data-swal-text="سيتم الحذف الناعم ويمكن استعادته لاحقاً من سلة المحذوفات إن توفرت."
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="ui-btn ui-btn-danger-outline ui-btn-sm">
                                                حذف
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-ui.table-empty :colspan="7" icon="users" message="لا يوجد أعضاء مطابقون للبحث." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $members->links() }}
        </div>
    </div>
</x-layouts.app>
