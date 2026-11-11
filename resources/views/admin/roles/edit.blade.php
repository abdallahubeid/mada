@extends('layouts.admin')

@section('title', 'تعديل صلاحيات الدور')

@section('breadcrumbs')
    <span class="text-mist-500 dark:text-mist-400">الحساب والوصول</span>
    <span class="mx-1.5 text-mist-400 dark:text-mist-600" aria-hidden="true">/</span>
    <a href="{{ route('admin.roles.index') }}" class="text-mist-500 hover:text-ink-700 dark:text-mist-400">الأدوار والصلاحيات</a>
    <span class="mx-1.5 text-mist-400 dark:text-mist-600" aria-hidden="true">/</span>
    <span class="text-ink-700 dark:text-mist-200">{{ $roleLabel }}</span>
@endsection

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">{{ $roleLabel }}</h1>
            <p class="mt-1 font-mono text-xs text-mist-500">{{ $role->name }}</p>
        </div>
        <a href="{{ route('admin.roles.index') }}" class="ui-btn ui-btn-secondary">رجوع</a>
    </div>

    <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <x-admin.permission-domain-cards :groups="$groups" :assigned="$assigned" />

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.roles.index') }}" class="ui-btn ui-btn-secondary">إلغاء</a>
            <button type="submit" class="ui-btn ui-btn-primary">حفظ الصلاحيات</button>
        </div>
    </form>
@endsection
