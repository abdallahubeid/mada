@extends('layouts.admin')

@section('title', 'آراء العملاء')

@section('breadcrumbs')
    <span class="text-mist-500 dark:text-mist-400">محتوى الصفحة الرئيسية</span>
    <span class="mx-1.5 text-mist-300 dark:text-mist-600">/</span>
    <span class="text-ink-700 dark:text-mist-200">آراء العملاء</span>
@endsection

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">آراء العملاء</h2>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">إدارة شهادات العملاء الظاهرة في صفحة الهبوط.</p>
        </div>
        <a href="{{ route('admin.testimonials.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-glow transition hover:bg-brand-600">إضافة شهادة</a>
    </div>

    <div class="mt-6 overflow-x-auto w-full rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
        <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
            <thead class="bg-mist-50 dark:bg-ink-900">
                <tr>
                    <th class="px-3 py-2 text-start font-medium text-mist-500">الصورة</th>
                    <th class="px-3 py-2 text-start font-medium text-mist-500">الترتيب</th>
                    <th class="px-3 py-2 text-start font-medium text-mist-500">العميل</th>
                    <th class="px-3 py-2 text-start font-medium text-mist-500">المؤسسة</th>
                    <th class="px-3 py-2 text-start font-medium text-mist-500">التقييم</th>
                    <th class="px-3 py-2 text-start font-medium text-mist-500">النشر</th>
                    <th class="px-3 py-2 text-end font-medium text-mist-500">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                @forelse ($testimonials as $testimonial)
                    @php
                        $avatar = $testimonial->images->firstWhere('collection', 'avatar')
                            ?? $testimonial->images->firstWhere('collection', 'logo');
                    @endphp
                    <tr>
                        <td class="px-3 py-2">
                            @if ($avatar)
                                <img src="{{ $avatar->url() }}" alt="{{ $avatar->alt_text }}" class="h-10 w-10 rounded-full object-cover ring-1 ring-mist-200 dark:ring-ink-600">
                            @else
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-md bg-mist-100 text-xs text-mist-400 dark:bg-ink-900">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-mist-500">{{ $testimonial->sort_order }}</td>
                        <td class="px-3 py-2 font-medium text-ink-900 dark:text-ink-50">{{ $testimonial->client_name }}</td>
                        <td class="px-3 py-2">{{ $testimonial->organization_name }}</td>
                        <td class="px-3 py-2 text-mist-500">{{ $testimonial->rate ?? '—' }}</td>
                        <td class="px-3 py-2">
                            <span @class([
                                'rounded-md px-2 py-0.5 text-xs font-semibold',
                                'bg-brand-500/10 text-brand-600' => $testimonial->is_published,
                                'bg-mist-100 text-mist-500' => ! $testimonial->is_published,
                            ])>{{ $testimonial->is_published ? 'منشور' : 'مسودة' }}</span>
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.testimonials.edit', $testimonial) }}" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold dark:border-ink-600">تعديل</a>
                                <form method="POST" action="{{ route('admin.testimonials.destroy', $testimonial) }}" data-swal-confirm data-swal-title="حذف هذه الشهادة؟">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold text-danger-solid dark:border-ink-600">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-mist-500">لا توجد شهادات بعد.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $testimonials->links() }}
    </div>
@endsection
