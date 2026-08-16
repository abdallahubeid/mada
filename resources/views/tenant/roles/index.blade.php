<x-layouts.app title="الأدوار والصلاحيات">
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">الأدوار والصلاحيات</h2>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">إدارة أدوار مؤسستك وصلاحياتها (المالك فقط).</p>
            </div>
            @can('tenant.roles.manage')
                <a
                    href="{{ route('roles.create') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-glow transition hover:bg-brand-600"
                >
                    إنشاء دور
                </a>
            @endcan
        </div>

        <div class="w-full overflow-x-auto rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                <thead class="bg-mist-50 dark:bg-ink-900">
                    <tr>
                        <th class="w-12 px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">#</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">الدور</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">النوع</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">الصلاحيات</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">المستخدمون</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                    @forelse ($roles as $role)
                        @php $isProtected = in_array($role->name, $protectedRoles, true); @endphp
                        <tr class="transition hover:bg-mist-50/80 dark:hover:bg-ink-900/40">
                            <td class="w-12 px-3 py-2 text-center text-sm tabular-nums text-mist-500">{{ $loop->iteration }}</td>
                            <td class="px-3 py-2 font-medium text-ink-900 dark:text-ink-50 text-start">
                                {{ $roleLabels[$role->name] ?? $role->name }}
                                <span class="mt-0.5 block font-mono text-xs text-mist-500">{{ $role->name }}</span>
                            </td>
                            <td class="px-3 py-2 text-start">
                                @if ($isProtected)
                                    <span class="rounded-md bg-brand-500/10 px-2 py-0.5 text-xs font-semibold text-brand-600 dark:text-brand-300">نظامي</span>
                                @else
                                    <span class="rounded-md bg-mist-100 px-2 py-0.5 text-xs font-semibold text-mist-500 dark:bg-ink-700 dark:text-mist-400">مخصص</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-mist-500 text-start">{{ $role->permissions_count }}</td>
                            <td class="px-3 py-2 text-start">
                                <span class="inline-flex min-w-8 items-center justify-center rounded-md bg-mist-100 px-2 py-0.5 text-xs font-semibold text-ink-700 dark:bg-ink-700 dark:text-mist-200">
                                    {{ $role->users_count }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <div class="flex items-center justify-end gap-2">
                                    @can('tenant.roles.manage')
                                        <a
                                            href="{{ route('roles.edit', $role) }}"
                                            class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold transition hover:border-brand-500 hover:text-brand-600 dark:border-ink-600"
                                        >
                                            تعديل
                                        </a>
                                        @unless ($isProtected)
                                            <form
                                                method="POST"
                                                action="{{ route('roles.destroy', $role) }}"
                                                data-swal-confirm
                                                data-swal-title="حذف هذا الدور؟"
                                                data-swal-text="لا يمكن التراجع عن حذف الأدوار المخصصة."
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold text-danger-solid transition hover:bg-danger-solid/10 dark:border-ink-600">
                                                    حذف
                                                </button>
                                            </form>
                                        @endunless
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-ui.table-empty :colspan="6" icon="key" message="لا توجد أدوار بعد." />
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
