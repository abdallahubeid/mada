@extends('layouts.admin')

@section('title', 'إنشاء دور جديد')

@section('breadcrumbs')
    <span class="text-mist-500 dark:text-mist-400">الحساب والوصول</span>
    <span class="mx-1.5 text-mist-300 dark:text-mist-600">/</span>
    <a href="{{ route('admin.roles.index') }}" class="text-mist-500 hover:text-ink-700 dark:text-mist-400">الأدوار والصلاحيات</a>
    <span class="mx-1.5 text-mist-300 dark:text-mist-600">/</span>
    <span class="text-ink-700 dark:text-mist-200">إنشاء</span>
@endsection

@section('content')
    @php
        $inputClass = 'w-full rounded-xl border border-mist-200 bg-white px-3 py-2.5 text-sm text-ink-700 placeholder:text-mist-400 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50';
        $labelClass = 'mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200';
    @endphp

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">إنشاء دور جديد</h2>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">عرّف معرّف الدور ثم اختر صلاحياته حسب النطاق.</p>
        </div>
        <a href="{{ route('admin.roles.index') }}" class="rounded-xl border border-mist-200 px-4 py-2 text-sm font-semibold dark:border-ink-600">رجوع</a>
    </div>

    <form method="POST" action="{{ route('admin.roles.store') }}" class="space-y-6">
        @csrf

        <div class="max-w-xl rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
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
            <a href="{{ route('admin.roles.index') }}" class="rounded-xl border border-mist-200 px-4 py-2 text-sm font-semibold dark:border-ink-600">إلغاء</a>
            <button type="submit" class="rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-glow transition hover:bg-emerald-300">إنشاء الدور</button>
        </div>
    </form>
@endsection
