@extends('layouts.admin')

@section('title', 'إضافة موديول')

@section('breadcrumbs')
    <a href="{{ route('admin.modules.index') }}" class="text-mist-500 hover:text-emerald-600">الموديولات</a>
    <span class="mx-1.5 text-mist-300">/</span>
    <span class="text-ink-700 dark:text-mist-200">إضافة</span>
@endsection

@section('content')
    <h2 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">إضافة موديول</h2>
    <div class="mt-6 max-w-2xl">
        @include('admin.landing.modules._form', [
            'module' => $module,
            'action' => route('admin.modules.store'),
            'method' => 'POST',
        ])
    </div>
@endsection