@extends('layouts.admin')

@section('title', 'تعديل مشرف')

@section('breadcrumbs')
    <span class="text-mist-500 dark:text-mist-400">الحساب والوصول</span>
    <span class="mx-1.5 text-mist-400 dark:text-mist-600" aria-hidden="true">/</span>
    <a href="{{ route('admin.admins') }}" class="text-mist-500 hover:text-ink-700 dark:text-mist-400">مديرو المنصّة</a>
    <span class="mx-1.5 text-mist-400 dark:text-mist-600" aria-hidden="true">/</span>
    <span class="text-ink-700 dark:text-mist-200">{{ $admin->name }}</span>
@endsection

@section('content')
    <div class="mb-6">
        <h1 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">تعديل مشرف</h1>
        <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">{{ $admin->email }}</p>
    </div>

    <form method="POST" action="{{ route('admin.admins.update', $admin) }}" class="ui-card space-y-6 p-6">
        @csrf
        @method('PUT')
        @include('admin.admins._form')
        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.admins') }}" class="ui-btn ui-btn-secondary">إلغاء</a>
            <button type="submit" class="ui-btn ui-btn-primary">حفظ</button>
        </div>
    </form>
@endsection
