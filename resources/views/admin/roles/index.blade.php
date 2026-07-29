@extends('layouts.admin')

@section('title', 'الأدوار والصلاحيات')

@section('breadcrumbs')
    <span class="text-mist-500 dark:text-mist-400">الحساب والوصول</span>
    <span class="mx-1.5 text-mist-300 dark:text-mist-600">/</span>
    <span class="text-ink-700 dark:text-mist-200">الأدوار والصلاحيات</span>
@endsection

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">الأدوار والصلاحيات</h2>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">إدارة صلاحيات أدوار منصّة Veyra حسب النطاق.</p>
        </div>
        @can('roles.create')
            <a href="{{ route('admin.roles.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-glow transition hover:bg-emerald-300">إنشاء دور جديد</a>
        @endcan
    </div>

    <div class="mt-6 overflow-x-auto rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
        <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
            <thead class="bg-mist-50 text-mist-500 dark:bg-ink-900 dark:text-mist-400">
                <tr>
                    <th class="px-4 py-3 text-start font-semibold">#</th>
                    <th class="px-4 py-3 text-start font-semibold">الدور</th>
                    <th class="px-4 py-3 text-start font-semibold">المعرّف</th>
                    <th class="px-4 py-3 text-start font-semibold">عدد الصلاحيات</th>
                    <th class="px-4 py-3 text-start font-semibold">عدد المستخدمين</th>
                    <th class="px-4 py-3 text-end font-semibold">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                @forelse ($roles as $role)
                    <tr>
                        <td class="px-4 py-3 text-mist-500">{{ $loop->iteration }}</td>
                        <td class="px-4 py-3 font-medium text-ink-900 dark:text-ink-50">{{ $roleLabels[$role->name] ?? $role->name }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-mist-500">{{ $role->name }}</td>
                        <td class="px-4 py-3 text-mist-500">{{ $role->permissions_count }}</td>
                        <td class="px-4 py-3 text-mist-500">{{ $role->users_count }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                @can('roles.update')
                                    <a href="{{ route('admin.roles.edit', $role) }}" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold dark:border-ink-600">تعديل الصلاحيات</a>
                                @endcan
                                @can('roles.delete')
                                    @unless (in_array($role->name, $protectedRoles, true))
                                        <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" data-swal-confirm data-swal-title="حذف هذا الدور نهائيًا؟" data-swal-text="الحذف نهائي ولا يظهر في سلة المحذوفات.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold text-danger-solid dark:border-ink-600">حذف دور</button>
                                        </form>
                                    @endunless
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-mist-500">لا توجد أدوار بعد.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
