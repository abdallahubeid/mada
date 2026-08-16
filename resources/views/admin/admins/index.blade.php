@extends('layouts.admin')

@section('title', 'إدارة المشرفين')

@section('breadcrumbs')
    <span class="text-mist-500 dark:text-mist-400">الحساب والوصول</span>
    <span class="mx-1.5 text-mist-300 dark:text-mist-600">/</span>
    <span class="text-ink-700 dark:text-mist-200">مديرو المنصّة</span>
@endsection

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">مديرو المنصّة</h2>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">حسابات المشرفين بدون مستأجر (tenant_id = null).</p>
        </div>
        @can('admins.create')
            <a href="{{ route('admin.admins.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-glow transition hover:bg-brand-600">إضافة مشرف</a>
        @endcan
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        @foreach ($metrics as $metric)
            <div class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <p class="text-xs font-medium text-mist-500">{{ $metric['label'] }}</p>
                <p class="mt-2 font-display text-2xl font-medium text-ink-900 dark:text-ink-50">{{ $metric['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 overflow-x-auto rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
        <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
            <thead class="bg-mist-50 text-mist-500 dark:bg-ink-900 dark:text-mist-400">
                <tr>
                    <th class="px-3 py-2 text-start font-medium">#</th>
                    <th class="px-3 py-2 text-start font-medium">الصورة</th>
                    <th class="px-3 py-2 text-start font-medium">الاسم</th>
                    <th class="px-3 py-2 text-start font-medium">البريد</th>
                    <th class="px-3 py-2 text-start font-medium">الدور</th>
                    <th class="px-3 py-2 text-end font-medium">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                @forelse ($admins as $admin)
                    @php
                        $roleName = $admin->roles->first()?->name;
                        $hasUploadedAvatar = $admin->avatar !== null && filled($admin->avatar->path);
                        $initial = mb_strtoupper(mb_substr(trim((string) $admin->name), 0, 1) ?: '?');
                    @endphp
                    <tr>
                        <td class="px-3 py-2 text-mist-500">{{ $loop->iteration }}</td>
                        <td class="px-3 py-2">
                            @if ($hasUploadedAvatar)
                                <img
                                    src="{{ $admin->avatar_url }}"
                                    alt="{{ $admin->name }}"
                                    class="h-10 w-10 rounded-full object-cover ring-1 ring-mist-200 dark:ring-ink-600"
                                >
                            @else
                                <span class="flex h-10 w-10 items-center justify-center rounded-md border border-slate-700 bg-brand-500/15 font-display text-sm font-medium text-brand-600 dark:text-brand-300" aria-hidden="true">{{ $initial }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 font-medium text-ink-900 dark:text-ink-50">
                            {{ $admin->name }}
                            @if ($admin->is(auth()->user()))
                                <span class="ms-1 text-xs text-mist-400">(أنت)</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-mist-500">{{ $admin->email }}</td>
                        <td class="px-3 py-2">
                            <span class="rounded-md bg-brand-500/10 px-2 py-0.5 text-xs font-semibold text-brand-600 dark:text-brand-300">
                                {{ $roleLabels[$roleName] ?? ($roleName ?: '—') }}
                            </span>
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex items-center justify-end gap-2">
                                @can('admins.update')
                                    <a href="{{ route('admin.admins.edit', $admin) }}" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold dark:border-ink-600">تعديل</a>
                                @endcan
                                @can('admins.delete')
                                    @unless ($admin->is(auth()->user()))
                                        <form method="POST" action="{{ route('admin.admins.destroy', $admin) }}" data-swal-confirm data-swal-title="حذف هذا المشرف؟">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold text-danger-solid dark:border-ink-600">حذف مشرف</button>
                                        </form>
                                    @endunless
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-mist-500">لا يوجد مشرفون بعد.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
