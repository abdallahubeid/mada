@extends('layouts.admin')

@section('title', 'إنشاء دور جديد')

@section('breadcrumbs')
    <span class="text-mist-500 dark:text-mist-400">الحساب والوصول</span>
    <span class="mx-1.5 text-mist-400 dark:text-mist-600" aria-hidden="true">/</span>
    <a href="{{ route('admin.roles.index') }}" class="text-mist-500 hover:text-ink-700 dark:text-mist-400">الأدوار والصلاحيات</a>
    <span class="mx-1.5 text-mist-400 dark:text-mist-600" aria-hidden="true">/</span>
    <span class="text-ink-700 dark:text-mist-200">إنشاء</span>
@endsection

@section('content')
    @php
        $inputClass = 'ui-control w-full';
        $labelClass = 'ui-label';
    @endphp

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">إنشاء دور جديد</h1>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">عرّف معرّف الدور ثم اختر صلاحياته حسب النطاق.</p>
        </div>
        <a href="{{ route('admin.roles.index') }}" class="ui-btn ui-btn-secondary">رجوع</a>
    </div>

    <form method="POST" action="{{ route('admin.roles.store') }}" class="space-y-6">
        @csrf

        <div class="ui-card max-w-xl p-4">
            <label class="{{ $labelClass }}" for="name">معرّف الدور</label>
            <input
                id="name"
                name="name"
                type="text"
                value="{{ old('name') }}"
                required
                placeholder="مثال: marketing_editor"
                class="{{ $inputClass }} font-mono"
            >
            <p class="mt-1.5 text-xs text-mist-500">أحرف لاتينية صغيرة وأرقام وشرطة سفلية فقط (مثل content_reviewer).</p>
            @error('name') <p class="mt-1 text-xs text-danger-solid">{{ $message }}</p> @enderror
        </div>

        <x-admin.permission-domain-cards :groups="$groups" :assigned="old('permissions', [])" />

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.roles.index') }}" class="ui-btn ui-btn-secondary">إلغاء</a>
            <button type="submit" class="ui-btn ui-btn-primary">إنشاء الدور</button>
        </div>
    </form>
@endsection
