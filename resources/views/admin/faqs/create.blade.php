@extends('layouts.admin')

@section('title', 'إضافة سؤال')

@section('breadcrumbs')
    <a href="{{ route('admin.faqs.index') }}" class="text-mist-500 hover:text-brand-600">الأسئلة الشائعة</a>
    <span class="mx-1.5 text-mist-300">/</span>
    <span class="text-ink-700 dark:text-mist-200">إضافة</span>
@endsection

@section('content')
    <h2 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">إضافة سؤال</h2>
    <div class="mt-6 max-w-2xl">
        @include('admin.faqs._form', ['faq' => $faq, 'action' => route('admin.faqs.store'), 'method' => 'POST'])
    </div>
@endsection
