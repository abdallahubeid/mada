@extends('layouts.admin')

@section('title', 'الحلول')

@section('breadcrumbs')
    <span class="text-mist-500 dark:text-mist-400">محتوى الصفحة الرئيسية</span>
    <span class="mx-1.5 text-mist-300 dark:text-mist-600">/</span>
    <span class="text-ink-700 dark:text-mist-200">الحلول</span>
@endsection

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">الحلول</h2>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">إدارة عناصر قسم الحلول في صفحة الهبوط.</p>
        </div>
        <a href="{{ route('admin.solutions.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-glow transition hover:bg-emerald-300">إضافة حل</a>
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
        <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
            <thead class="bg-mist-50 dark:bg-ink-900">
                <tr>
                    <th class="px-4 py-3 text-start font-semibold text-mist-500">الصورة</th>
                    <th class="px-4 py-3 text-start font-semibold text-mist-500">الترتيب</th>
                    <th class="px-4 py-3 text-start font-semibold text-mist-500">العنوان</th>
                    <th class="px-4 py-3 text-start font-semibold text-mist-500">المفتاح</th>
                    <th class="px-4 py-3 text-start font-semibold text-mist-500">النشر</th>
                    <th class="px-4 py-3 text-end font-semibold text-mist-500">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                @forelse ($solutions as $solution)
                    @php
                        $image = $solution->images->firstWhere('collection', 'icon');
                    @endphp
                    <tr>
                        <td class="px-4 py-3">
                            @if ($image)
                                <img src="{{ $image->url() }}" alt="{{ $image->alt_text }}" class="h-10 w-10 rounded-lg object-cover ring-1 ring-mist-200 dark:ring-ink-600">
                            @else
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-mist-100 text-xs text-mist-400 dark:bg-ink-900">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-mist-500">{{ $solution->sort_order }}</td>
                        <td class="px-4 py-3 font-medium text-ink-900 dark:text-ink-50">{{ $solution->title }}</td>
                        <td class="px-4 py-3 text-mist-500">{{ $solution->icon_key }}</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'rounded-full px-2 py-0.5 text-[10px] font-semibold',
                                'bg-emerald-500/10 text-emerald-600' => $solution->is_published,
                                'bg-mist-100 text-mist-500' => ! $solution->is_published,
                            ])>{{ $solution->is_published ? 'منشور' : 'مسودة' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.solutions.edit', $solution) }}" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold dark:border-ink-600">تعديل</a>
                                <form method="POST" action="{{ route('admin.solutions.destroy', $solution) }}" onsubmit="return confirm('حذف هذا العنصر؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold text-danger-solid dark:border-ink-600">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-mist-500">لا توجد عناصر بعد.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $solutions->links() }}
    </div>
@endsection