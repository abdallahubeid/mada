@extends('layouts.admin')

@section('title', 'الأسئلة الشائعة')

@section('breadcrumbs')
    <span class="text-mist-500 dark:text-mist-400">المستأجرون</span>
    <span class="mx-1.5 text-mist-300 dark:text-mist-600">/</span>
    <span class="text-ink-700 dark:text-mist-200">الأسئلة الشائعة</span>
@endsection

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">الأسئلة الشائعة</h2>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">إدارة محتوى صفحة الأسئلة الشائعة وترتيبها ونشرها.</p>
        </div>
        @can('faqs.create')
            <a href="{{ route('admin.faqs.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-glow transition hover:bg-emerald-300">إضافة سؤال</a>
        @endcan
    </div>

    <div class="mt-6 overflow-x-auto w-full rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
        <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                    <thead class="bg-mist-50 text-mist-500 dark:bg-ink-900 dark:text-mist-400">
                <tr>
                    <th class="px-4 py-3 text-start font-semibold">الترتيب</th>
                    <th class="px-4 py-3 text-start font-semibold">التصنيف</th>
                    <th class="px-4 py-3 text-start font-semibold">السؤال</th>
                    <th class="px-4 py-3 text-start font-semibold">النشر</th>
                    <th class="px-4 py-3 text-end font-semibold">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                @forelse ($faqs as $faq)
                    <tr>
                        <td class="px-4 py-3 text-mist-500">{{ $faq->sort_order }}</td>
                        <td class="px-4 py-3">{{ $faq->category }}</td>
                        <td class="px-4 py-3 font-medium text-ink-900 dark:text-ink-50">{{ $faq->question }}</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'rounded-full px-2 py-0.5 text-[10px] font-semibold',
                                'bg-emerald-500/10 text-emerald-600' => $faq->is_published,
                                'bg-mist-100 text-mist-500' => ! $faq->is_published,
                            ])>{{ $faq->is_published ? 'منشور' : 'مسودة' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                @can('faqs.update')
                                    <a href="{{ route('admin.faqs.edit', $faq) }}" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold dark:border-ink-600">تعديل</a>
                                @endcan
                                @can('faqs.delete')
                                    <form method="POST" action="{{ route('admin.faqs.destroy', $faq) }}" data-swal-confirm data-swal-title="حذف هذا السؤال؟">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold text-danger-solid dark:border-ink-600">حذف</button>
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
