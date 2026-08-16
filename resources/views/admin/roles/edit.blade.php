@extends('layouts.admin')

@section('title', 'تعديل صلاحيات الدور')

@section('breadcrumbs')
    <span class="text-mist-500 dark:text-mist-400">الحساب والوصول</span>
    <span class="mx-1.5 text-mist-300 dark:text-mist-600">/</span>
    <a href="{{ route('admin.roles.index') }}" class="text-mist-500 hover:text-ink-700 dark:text-mist-400">الأدوار والصلاحيات</a>
    <span class="mx-1.5 text-mist-300 dark:text-mist-600">/</span>
    <span class="text-ink-700 dark:text-mist-200">{{ $roleLabel }}</span>
@endsection

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">{{ $roleLabel }}</h2>
            <p class="mt-1 font-mono text-xs text-mist-500">{{ $role->name }}</p>
        </div>
        <a href="{{ route('admin.roles.index') }}" class="rounded-xl border border-mist-200 px-4 py-2 text-sm font-semibold dark:border-ink-600">رجوع</a>
    </div>

    <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <x-admin.permission-domain-cards :groups="$groups" :assigned="$assigned" />

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.roles.index') }}" class="rounded-xl border border-mist-200 px-4 py-2 text-sm font-semibold dark:border-ink-600">إلغاء</a>
            <button type="submit" class="rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-glow transition hover:bg-brand-600">حفظ الصلاحيات</button>
        </div>
    </form>
@endsection
