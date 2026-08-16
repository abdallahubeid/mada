@extends('layouts.admin')

@section('title', 'تعديل ميزة ذكاء اصطناعي')

@section('breadcrumbs')
    <a href="{{ route('admin.ai-features.index') }}" class="text-mist-500 hover:text-brand-600">ميزات الذكاء الاصطناعي</a>
    <span class="mx-1.5 text-mist-300">/</span>
    <span class="text-ink-700 dark:text-mist-200">تعديل</span>
@endsection

@section('content')
    <h2 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">تعديل ميزة ذكاء اصطناعي</h2>
    <div class="mt-6 max-w-2xl">
        @include('admin.landing.ai-features._form', [
            'aiFeature' => $aiFeature,
            'action' => route('admin.ai-features.update', $aiFeature),
            'method' => 'PUT',
        ])
    </div>
@endsection