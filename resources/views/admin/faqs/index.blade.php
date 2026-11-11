@extends('layouts.admin')

@section('title', 'الأسئلة الشائعة')

@section('breadcrumbs')
    <span class="text-mist-500 dark:text-mist-400">المستأجرون</span>
    <span class="mx-1.5 text-mist-400 dark:text-mist-600" aria-hidden="true">/</span>
    <span class="text-ink-700 dark:text-mist-200">الأسئلة الشائعة</span>
@endsection

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">الأسئلة الشائعة</h1>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">إدارة محتوى صفحة الأسئلة الشائعة وترتيبها ونشرها.</p>
        </div>
        @can('faqs.create')
            <a href="{{ route('admin.faqs.create') }}" class="ui-btn ui-btn-primary">إضافة سؤال</a>
        @endcan
    </div>

    <div class="ui-card mt-6 overflow-x-auto w-full">
        <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                    <thead class="bg-mist-50 text-mist-500 dark:bg-ink-900 dark:text-mist-400">
                <tr>
                    <th class="px-3 py-2 text-start font-medium">الترتيب</th>
                    <th class="px-3 py-2 text-start font-medium">التصنيف</th>
                    <th class="px-3 py-2 text-start font-medium">السؤال</th>
                    <th class="px-3 py-2 text-start font-medium">النشر</th>
                    <th class="px-3 py-2 text-end font-medium">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                @forelse ($faqs as $faq)
                    <tr>
                        <td class="px-3 py-2 text-mist-500">{{ $faq->sort_order }}</td>
                        <td class="px-3 py-2">{{ $faq->category }}</td>
                        <td class="px-3 py-2 font-medium text-ink-900 dark:text-ink-50">{{ $faq->question }}</td>
                        <td class="px-3 py-2">
                            <span @class([
                                'rounded-md px-2 py-0.5 text-xs font-semibold',
                                'bg-brand-500/10 text-brand-600' => $faq->is_published,
                                'bg-mist-100 text-mist-500' => ! $faq->is_published,
                            ])>{{ $faq->is_published ? 'منشور' : 'مسودة' }}</span>
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex items-center justify-end gap-2">
                                @can('faqs.update')
                                    <a href="{{ route('admin.faqs.edit', $faq) }}" class="ui-btn ui-btn-secondary ui-btn-sm">تعديل</a>
                                @endcan
                                @can('faqs.delete')
                                    <form method="POST" action="{{ route('admin.faqs.destroy', $faq) }}" data-swal-confirm data-swal-title="حذف هذا السؤال؟">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ui-btn ui-btn-danger-outline ui-btn-sm">حذف</button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-mist-500">لا توجد أسئلة بعد.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
