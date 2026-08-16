@extends('layouts.admin')

@section('title', 'الحملات البريدية')

@section('breadcrumbs')
    <span class="text-mist-500 dark:text-mist-400">التواصل</span>
    <span class="mx-1.5 text-mist-300 dark:text-mist-600">/</span>
    <span class="text-mist-500 dark:text-mist-400">النشرة البريدية</span>
    <span class="mx-1.5 text-mist-300 dark:text-mist-600">/</span>
    <span class="text-ink-700 dark:text-mist-200">الحملات</span>
@endsection

@section('content')
    <div
        x-data="{
            open: false,
            loading: false,
            campaign: null,
            async viewCampaign(url) {
                this.loading = true;
                this.open = true;
                this.campaign = null;
                try {
                    const response = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    if (! response.ok) {
                        throw new Error('Failed');
                    }
                    this.campaign = await response.json();
                } catch (error) {
                    this.open = false;
                    Swal.fire({
                        icon: 'error',
                        title: 'تعذّر تحميل محتوى الحملة',
                        confirmButtonColor: '#714b67',
                    });
                } finally {
                    this.loading = false;
                }
            },
        }"
        class="space-y-6"
    >
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">الحملات البريدية</h2>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">سجل الحملات المرسلة وعدد المستلمين مع إمكانية مراجعة المحتوى.</p>
            </div>
            <a
                href="{{ route('admin.newsletter.index') }}"
                class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-glow transition hover:bg-brand-600"
            >
                إرسال حملة جديدة
            </a>
        </div>

        <div class="overflow-x-auto w-full rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                <thead class="bg-mist-50 text-mist-500 dark:bg-ink-900 dark:text-mist-400">
                    <tr>
                        <th class="w-14 border-e border-mist-100 px-3 py-2 text-center font-medium dark:border-ink-700">#</th>
                        <th class="border-e border-mist-100 px-3 py-2 text-start font-medium dark:border-ink-700">الموضوع</th>
                        <th class="border-e border-mist-100 px-3 py-2 text-center font-medium dark:border-ink-700">المستلمون</th>
                        <th class="border-e border-mist-100 px-3 py-2 text-center font-medium dark:border-ink-700">تاريخ الإرسال</th>
                        <th class="px-3 py-2 text-end font-medium">عرض</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                    @forelse ($campaigns as $campaign)
                        <tr
                            id="mada-search-campaign-{{ $campaign->id }}"
                            data-campaign-id="{{ $campaign->id }}"
                            data-mada-search="campaign-{{ $campaign->id }}"
                        >
                            <td class="border-e border-mist-100 px-3 py-2 font-medium text-ink-900 dark:border-ink-700 dark:text-ink-50">
                                {{ $campaign->subject }}
                            </td>
                            <td class="border-e border-mist-100 px-3 py-2 text-center text-mist-500 dark:border-ink-700">
                                {{ $campaign->recipients_count }}
                            </td>
                            <td class="border-e border-mist-100 px-3 py-2 text-center text-mist-500 dark:border-ink-700" dir="rtl" lang="ar">
                                {{ $campaign->sent_at?->locale('ar')->diffForHumans() ?? '—' }}
                            </td>
                            <td class="px-3 py-2 text-end">
                                <button
                                    type="button"
                                    @click="viewCampaign(@js(route('admin.newsletter.campaigns.show', $campaign)))"
                                    class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold dark:border-ink-600"
                                >
                                    قراءة المحتوى
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-mist-500">لا توجد حملات مرسلة بعد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($campaigns->hasPages())
            <div class="mt-4">
                {{ $campaigns->links() }}
            </div>
        @endif

        <div
            x-show="open"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <div class="absolute inset-0 bg-ink-950/60" @click="open = false"></div>
            <div class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl border border-mist-200 bg-white p-6 shadow-xl dark:border-ink-600 dark:bg-ink-800">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-display text-xl font-medium text-ink-900 dark:text-ink-50" x-text="campaign?.subject || 'محتوى الحملة'"></h3>
                        <p class="mt-1 text-sm text-mist-500">
                            <span x-show="campaign">المستلمون: <span x-text="campaign?.recipients_count"></span> · </span>
                            <span x-text="campaign?.sent_at_formatted || ''"></span>
                        </p>
                    </div>
                    <button type="button" @click="open = false" class="rounded-lg p-2 text-mist-400 hover:bg-mist-100 dark:hover:bg-ink-700" aria-label="إغلاق">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="mt-5 rounded-xl border border-mist-200 bg-mist-50 p-4 text-sm leading-relaxed text-ink-700 dark:border-ink-600 dark:bg-ink-900 dark:text-mist-200">
                    <template x-if="loading">
                        <p class="text-mist-500">جاري التحميل...</p>
                    </template>
                    <template x-if="! loading && campaign">
                        <div class="prose prose-sm max-w-none dark:prose-invert" x-html="campaign.content"></div>
                    </template>
                </div>
            </div>
        </div>
    </div>
@endsection
