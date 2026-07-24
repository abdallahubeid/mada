@extends('layouts.admin')

@section('title', 'تعديل حل')

@section('breadcrumbs')
    <a href="{{ route('admin.solutions.index') }}" class="text-mist-500 hover:text-emerald-600">الحلول</a>
    <span class="mx-1.5 text-mist-300">/</span>
    <span class="text-ink-700 dark:text-mist-200">تعديل</span>
@endsection

@section('content')
    <h2 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">تعديل حل</h2>
    <div class="mt-6 max-w-2xl">
        @include('admin.landing.solutions._form', [
            'solution' => $solution,
            'action' => route('admin.solutions.update', $solution),
            'method' => 'PUT',
        ])
    </div>
@endsection