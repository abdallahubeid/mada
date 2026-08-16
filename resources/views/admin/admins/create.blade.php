@extends('layouts.admin')

@section('title', 'إضافة مشرف')

@section('breadcrumbs')
    <span class="text-mist-500 dark:text-mist-400">الحساب والوصول</span>
    <span class="mx-1.5 text-mist-300 dark:text-mist-600">/</span>
    <a href="{{ route('admin.admins') }}" class="text-mist-500 hover:text-ink-700 dark:text-mist-400">مديرو المنصّة</a>
    <span class="mx-1.5 text-mist-300 dark:text-mist-600">/</span>
    <span class="text-ink-700 dark:text-mist-200">إضافة</span>
@endsection

@section('content')
    <div class="mb-6">
        <h2 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">إضافة مشرف</h2>
        <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">إنشاء حساب منصّة مع دور وصلاحيات اختيارية مباشرة.</p>
    </div>

    <form method="POST" action="{{ route('admin.admins.store') }}" class="space-y-6 rounded-2xl border border-mist-200 bg-white p-6 shadow-sm dark:border-ink-600 dark:bg-ink-800">
        @csrf
        @include('admin.admins._form')
        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.admins') }}" class="rounded-xl border border-mist-200 px-4 py-2 text-sm font-semibold dark:border-ink-600">إلغاء</a>
            <button type="submit" class="rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-glow transition hover:bg-brand-600">إنشاء</button>
        </div>
    </form>
@endsection
