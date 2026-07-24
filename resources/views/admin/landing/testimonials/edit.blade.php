@extends('layouts.admin')

@section('title', 'تعديل شهادة')

@section('breadcrumbs')
    <a href="{{ route('admin.testimonials.index') }}" class="text-mist-500 hover:text-emerald-600">آراء العملاء</a>
    <span class="mx-1.5 text-mist-300">/</span>
    <span class="text-ink-700 dark:text-mist-200">تعديل</span>
@endsection

@section('content')
    <h2 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">تعديل شهادة</h2>
    <div class="mt-6 max-w-2xl">
        @include('admin.landing.testimonials._form', [
            'testimonial' => $testimonial,
            'action' => route('admin.testimonials.update', $testimonial),
            'method' => 'PUT',
        ])
    </div>
@endsection
