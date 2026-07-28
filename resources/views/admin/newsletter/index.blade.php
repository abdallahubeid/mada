@extends('layouts.admin')

@section('title', 'النشرة البريدية')

@section('breadcrumbs')
    <span class="text-mist-500 dark:text-mist-400">التواصل</span>
    <span class="mx-1.5 text-mist-300 dark:text-mist-600">/</span>
    <span class="text-ink-700 dark:text-mist-200">النشرة البريدية</span>
@endsection

@section('content')
    <div
        x-data="veyraNewsletterDashboard({
            pollUrl: @js(route('admin.newsletter.poll')),
            csrf: @js(csrf_token()),
            status: @js($status),
            search: @js($search),
            page: @js($subscribers->currentPage()),
            stats: @js($stats),
            subscribers: @js($subscriberRows),
            activeSubscribers: @js($activeSubscribers),
            signature: @js($pollSignature),
            pollIntervalMs: 7000,
        })"
        class="space-y-6"
    >
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">النشرة البريدية</h2>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">إدارة المشتركين وإرسال الحملات مع إمكانية استثناء مستلمين محددين.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('admin.newsletter.export') }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-mist-200 bg-white px-4 py-2 text-sm font-semibold text-ink-700 transition hover:bg-mist-50 dark:border-ink-600 dark:bg-ink-800 dark:text-mist-200"
                >
                    تصدير CSV
                </a>
                <button
                    type="button"
                    @click="campaignOpen = true"
                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-glow transition hover:bg-emerald-300"
                >
                    إرسال حملة
                </button>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <p class="text-xs font-semibold uppercase tracking-wide text-mist-500">إجمالي المشتركين</p>
                <p class="mt-2 font-display text-3xl font-bold text-ink-900 dark:text-ink-50" x-text="stats.total" data-stat="total">{{ $stats['total'] }}</p>
            </div>
            <div class="rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <p class="text-xs font-semibold uppercase tracking-wide text-mist-500">نشط</p>
                <p class="mt-2 font-display text-3xl font-bold text-emerald-600 dark:text-emerald-400" x-text="stats.active" data-stat="active">{{ $stats['active'] }}</p>
            </div>
            <div class="rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <p class="text-xs font-semibold uppercase tracking-wide text-mist-500">ملغى الاشتراك</p>
                <p class="mt-2 font-display text-3xl font-bold text-amber-600 dark:text-amber-400" x-text="stats.unsubscribed" data-stat="unsubscribed">{{ $stats['unsubscribed'] }}</p>
            </div>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap gap-2">
                @foreach ([
                    'all' => 'الكل',
                    'subscribed' => 'نشط',
                    'unsubscribed' => 'ملغى',
                ] as $key => $label)
                    <a
                        href="{{ route('admin.newsletter.index', ['status' => $key, 'q' => $search ?: null]) }}"
                        @class([
                            'rounded-full px-3 py-1.5 text-xs font-semibold transition',
                            'bg-emerald-400/15 text-emerald-700 dark:text-emerald-400' => $status === $key,
                            'bg-mist-100 text-mist-600 hover:bg-mist-200 dark:bg-ink-700 dark:text-mist-300' => $status !== $key,
                        ])
                    >{{ $label }}</a>
                @endforeach
            </div>
            <form method="GET" action="{{ route('admin.newsletter.index') }}" class="relative w-full sm:w-72">
                <input type="hidden" name="status" value="{{ $status }}">
                <input
                    type="search"
                    name="q"
                    value="{{ $search }}"
                    placeholder="بحث بالبريد..."
                    class="w-full rounded-xl border border-mist-200 bg-white px-3 py-2 text-sm text-ink-700 placeholder:text-mist-400 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50"
                >
            </form>
        </div>

        <div class="w-full overflow-x-auto overflow-hidden rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <table class="min-w-full table-fixed divide-y divide-mist-100 text-sm dark:divide-ink-700">
                <thead class="bg-mist-50 text-mist-500 dark:bg-ink-900 dark:text-mist-400">
                    <tr>
                        <th class="w-14 border-e border-mist-100 px-6 py-4 text-center font-semibold dark:border-ink-700">#</th>
                        <th class="w-[32%] border-e border-mist-100 px-6 py-4 text-start font-semibold dark:border-ink-700" dir="ltr">البريد</th>
                        <th class="w-[20%] border-e border-mist-100 px-6 py-4 text-center font-semibold dark:border-ink-700">تاريخ الاشتراك</th>
                        <th class="w-[16%] border-e border-mist-100 px-6 py-4 text-center font-semibold dark:border-ink-700">الحالة</th>
                        <th class="w-[22%] px-6 py-4 text-end font-semibold">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mist-100 dark:divide-ink-700" data-subscriber-tbody x-ref="subscriberTbody">
                    @forelse ($subscriberRows as $row)
                        <tr
                            id="veyra-search-subscriber-{{ $row['id'] }}"
                            data-subscriber-id="{{ $row['id'] }}"
                            data-veyra-search="subscriber-{{ $row['id'] }}"
                        >
                            <td class="border-e border-mist-100 px-6 py-4 text-center text-mist-500 dark:border-ink-700">{{ $row['index'] }}</td>
                            <td class="max-w-0 border-e border-mist-100 px-6 py-4 text-start dark:border-ink-700" dir="ltr">
                                <span class="block truncate font-mono text-sm font-medium text-ink-900 dark:text-ink-50" title="{{ $row['email'] }}">{{ $row['email'] }}</span>
                            </td>
                            <td class="border-e border-mist-100 px-6 py-4 text-center text-mist-500 dark:border-ink-700" dir="rtl" lang="ar">
                                <span
                                    class="veyra-relative-time inline-block"
                                    data-timestamp="{{ $row['subscribed_at'] }}"
                                >{{ $row['subscribed_at_human'] }}</span>
                            </td>
                            <td class="border-e border-mist-100 px-6 py-4 text-center dark:border-ink-700">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-semibold',
                                    'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' => $row['is_subscribed'],
                                    'bg-amber-500/10 text-amber-600 dark:text-amber-400' => ! $row['is_subscribed'],
                                ])>{{ $row['status_label'] }}</span>
                            </td>
                            <td class="px-6 py-4 text-end">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <form method="POST" action="{{ $row['toggle_url'] }}">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold dark:border-ink-600">
                                            {{ $row['is_subscribed'] ? 'إلغاء الاشتراك' : 'تفعيل' }}
                                        </button>
                                    </form>
                                    <form
                                        method="POST"
                                        action="{{ $row['destroy_url'] }}"
                                        @submit.prevent="confirmDelete($el)"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold text-danger-solid dark:border-ink-600">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr data-empty-row>
                            <td colspan="5" class="px-6 py-8 text-center text-mist-500">لا يوجد مشتركون بعد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($subscribers->hasPages())
            <div class="mt-4">
                {{ $subscribers->links() }}
            </div>
        @endif

        <div
            x-show="campaignOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <div class="absolute inset-0 bg-ink-950/60" @click="campaignOpen = false"></div>
            <div class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl border border-mist-200 bg-white p-6 shadow-xl dark:border-ink-600 dark:bg-ink-800">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-display text-xl font-bold text-ink-900 dark:text-ink-50">إرسال حملة بريدية</h3>
                        <p class="mt-1 text-sm text-mist-500">اختر مستلمين للاستثناء قبل الإرسال إلى المشتركين النشطين.</p>
                    </div>
                    <button type="button" @click="campaignOpen = false" class="rounded-lg p-2 text-mist-400 hover:bg-mist-100 dark:hover:bg-ink-700" aria-label="إغلاق">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form
                    method="POST"
                    action="{{ route('admin.newsletter.campaign') }}"
                    class="mt-5 space-y-4"
                    @submit.prevent="confirmCampaign($el)"
                >
                    @csrf
                    <div>
                        <label for="campaign-subject" class="mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200">الموضوع</label>
                        <input
                            id="campaign-subject"
                            type="text"
                            name="subject"
                            value="{{ old('subject') }}"
                            required
                            maxlength="255"
                            class="w-full rounded-xl border border-mist-200 bg-white px-3 py-2 text-sm text-ink-700 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50"
                        >
                        @error('subject')
                            <p class="mt-1 text-xs text-danger-solid">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="campaign-body" class="mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200">المحتوى (HTML مسموح)</label>
                        <textarea
                            id="campaign-body"
                            name="body"
                            rows="8"
                            required
                            class="w-full rounded-xl border border-mist-200 bg-white px-3 py-2 text-sm text-ink-700 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50"
                        >{{ old('body') }}</textarea>
                        @error('body')
                            <p class="mt-1 text-xs text-danger-solid">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <p class="mb-2 text-sm font-medium text-ink-700 dark:text-mist-200">استثناء مشتركين من الحملة</p>
                        <template x-if="activeSubscribers.length === 0">
                            <p class="text-sm text-mist-500">لا يوجد مشتركون نشطون حاليًا.</p>
                        </template>
                        <template x-if="activeSubscribers.length > 0">
                            <div>
                                <div class="max-h-48 space-y-2 overflow-y-auto rounded-xl border border-mist-200 p-3 dark:border-ink-600" x-ref="excludeList">
                                    <template x-for="active in activeSubscribers" :key="active.id">
                                        <label class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-mist-50 dark:hover:bg-ink-700">
                                            <input
                                                type="checkbox"
                                                name="exclude_ids[]"
                                                :value="active.id"
                                                class="rounded border-mist-300 text-emerald-500 focus:ring-emerald-400"
                                                :checked="excludeIds.includes(active.id)"
                                                @change="toggleExclude(active.id)"
                                            >
                                            <span class="font-mono text-sm" dir="ltr" x-text="active.email"></span>
                                        </label>
                                    </template>
                                </div>
                                <p class="mt-2 text-xs text-mist-500">المحددون هنا <strong>لن</strong> يستلموا الحملة.</p>
                            </div>
                        </template>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="campaignOpen = false" class="rounded-xl border border-mist-200 px-4 py-2 text-sm font-semibold dark:border-ink-600">إلغاء</button>
                        <button
                            type="submit"
                            class="rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-glow hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="activeSubscribers.length === 0"
                        >
                            إرسال الحملة
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('veyraNewsletterDashboard', (config) => ({
                pollUrl: config.pollUrl,
                csrf: config.csrf,
                status: config.status,
                search: config.search,
                page: config.page || 1,
                stats: config.stats || { total: 0, active: 0, unsubscribed: 0 },
                subscribers: config.subscribers || [],
                activeSubscribers: config.activeSubscribers || [],
                signature: config.signature || '',
                pollIntervalMs: config.pollIntervalMs || 7000,
                campaignOpen: false,
                excludeIds: [],
                pollTimer: null,
                relativeTimer: null,
                polling: false,

                init() {
                    this.refreshRelativeTimes();
                    this.relativeTimer = setInterval(() => this.refreshRelativeTimes(), 60000);
                    this.pollTimer = setInterval(() => this.poll(), this.pollIntervalMs);
                    // Immediate first poll so footer signups appear quickly.
                    setTimeout(() => this.poll(), 1500);
                    document.addEventListener('visibilitychange', () => {
                        if (! document.hidden) {
                            this.poll();
                        }
                    });
                },

                destroy() {
                    if (this.pollTimer) {
                        clearInterval(this.pollTimer);
                    }
                    if (this.relativeTimer) {
                        clearInterval(this.relativeTimer);
                    }
                },

                toggleExclude(id) {
                    if (this.excludeIds.includes(id)) {
                        this.excludeIds = this.excludeIds.filter((item) => item !== id);
                    } else {
                        this.excludeIds = [...this.excludeIds, id];
                    }
                },

                confirmDelete(form) {
                    Swal.fire({
                        title: 'هل أنت متأكد من حذف المشترك؟',
                        text: 'سيتم الحذف الناعم (Soft Delete).',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'نعم، احذف',
                        cancelButtonText: 'إلغاء',
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#64748b',
                        reverseButtons: true,
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                },

                confirmCampaign(form) {
                    const excluded = this.excludeIds.length;
                    Swal.fire({
                        title: 'إرسال الحملة الآن؟',
                        text: excluded > 0
                            ? `سيتم استثناء ${excluded} مشتركًا من الإرسال.`
                            : 'سيتم الإرسال إلى جميع المشتركين النشطين.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'إرسال',
                        cancelButtonText: 'إلغاء',
                        confirmButtonColor: '#4edea3',
                        cancelButtonColor: '#64748b',
                        reverseButtons: true,
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                },

                formatRelative(iso) {
                    if (! iso) {
                        return '—';
                    }

                    const then = new Date(iso).getTime();
                    if (Number.isNaN(then)) {
                        return '—';
                    }

                    const rtf = new Intl.RelativeTimeFormat('ar', { numeric: 'auto' });
                    const diffSec = Math.round((then - Date.now()) / 1000);
                    const abs = Math.abs(diffSec);
                    let value = diffSec;
                    let unit = 'second';

                    if (abs >= 60 && abs < 3600) {
                        value = Math.round(diffSec / 60);
                        unit = 'minute';
                    } else if (abs >= 3600 && abs < 86400) {
                        value = Math.round(diffSec / 3600);
                        unit = 'hour';
                    } else if (abs >= 86400 && abs < 604800) {
                        value = Math.round(diffSec / 86400);
                        unit = 'day';
                    } else if (abs >= 604800 && abs < 2629800) {
                        value = Math.round(diffSec / 604800);
                        unit = 'week';
                    } else if (abs >= 2629800 && abs < 31557600) {
                        value = Math.round(diffSec / 2629800);
                        unit = 'month';
                    } else if (abs >= 31557600) {
                        value = Math.round(diffSec / 31557600);
                        unit = 'year';
                    }

                    return rtf.format(value, unit);
                },

                refreshRelativeTimes() {
                    this.$el.querySelectorAll('.veyra-relative-time[data-timestamp]').forEach((node) => {
                        const iso = node.getAttribute('data-timestamp');
                        if (iso) {
                            node.textContent = this.formatRelative(iso);
                        }
                    });
                },

                escapeHtml(value) {
                    return String(value ?? '')
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                },

                renderRows(subscribers) {
                    if (! subscribers.length) {
                        return `<tr data-empty-row>
                            <td colspan="5" class="px-6 py-8 text-center text-mist-500">لا يوجد مشتركون بعد.</td>
                        </tr>`;
                    }

                    return subscribers.map((row) => {
                        const badge = row.is_subscribed
                            ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                            : 'bg-amber-500/10 text-amber-600 dark:text-amber-400';
                        const toggleLabel = row.is_subscribed ? 'إلغاء الاشتراك' : 'تفعيل';

                        return `<tr id="veyra-search-subscriber-${row.id}" data-subscriber-id="${row.id}" data-veyra-search="subscriber-${row.id}">
                            <td class="border-e border-mist-100 px-6 py-4 text-center text-mist-500 dark:border-ink-700">${row.index ?? ''}</td>
                            <td class="max-w-0 border-e border-mist-100 px-6 py-4 text-start dark:border-ink-700" dir="ltr">
                                <span class="block truncate font-mono text-sm font-medium text-ink-900 dark:text-ink-50" title="${this.escapeHtml(row.email)}">${this.escapeHtml(row.email)}</span>
                            </td>
                            <td class="border-e border-mist-100 px-6 py-4 text-center text-mist-500 dark:border-ink-700" dir="rtl" lang="ar">
                                <span class="veyra-relative-time inline-block" data-timestamp="${this.escapeHtml(row.subscribed_at || '')}">${this.escapeHtml(this.formatRelative(row.subscribed_at))}</span>
                            </td>
                            <td class="border-e border-mist-100 px-6 py-4 text-center dark:border-ink-700">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-semibold ${badge}">${this.escapeHtml(row.status_label)}</span>
                            </td>
                            <td class="px-6 py-4 text-end">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <form method="POST" action="${this.escapeHtml(row.toggle_url)}">
                                        <input type="hidden" name="_token" value="${this.escapeHtml(this.csrf)}">
                                        <input type="hidden" name="_method" value="PUT">
                                        <button type="submit" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold dark:border-ink-600">${toggleLabel}</button>
                                    </form>
                                    <form method="POST" action="${this.escapeHtml(row.destroy_url)}" data-delete-form>
                                        <input type="hidden" name="_token" value="${this.escapeHtml(this.csrf)}">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <button type="submit" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold text-danger-solid dark:border-ink-600">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>`;
                    }).join('');
                },

                bindDeleteForms() {
                    const tbody = this.$refs.subscriberTbody || this.$el.querySelector('[data-subscriber-tbody]');
                    if (! tbody) {
                        return;
                    }

                    tbody.querySelectorAll('form[data-delete-form]').forEach((form) => {
                        form.addEventListener('submit', (event) => {
                            event.preventDefault();
                            this.confirmDelete(form);
                        });
                    });
                },

                applySnapshot(data) {
                    this.stats = {
                        total: data.stats?.total ?? this.stats.total,
                        active: data.stats?.active ?? this.stats.active,
                        unsubscribed: data.stats?.unsubscribed ?? this.stats.unsubscribed,
                    };
                    this.subscribers = data.subscribers || [];
                    this.activeSubscribers = data.active_subscribers || [];
                    this.signature = data.signature || this.signature;

                    // Drop exclude ids that are no longer active.
                    const activeIds = new Set(this.activeSubscribers.map((item) => item.id));
                    this.excludeIds = this.excludeIds.filter((id) => activeIds.has(id));

                    const tbody = this.$refs.subscriberTbody || this.$el.querySelector('[data-subscriber-tbody]');
                    if (tbody) {
                        tbody.innerHTML = this.renderRows(this.subscribers);
                        this.bindDeleteForms();
                        this.refreshRelativeTimes();
                    }
                },

                async poll() {
                    if (this.polling || document.hidden) {
                        return;
                    }

                    this.polling = true;

                    try {
                        const params = new URLSearchParams({
                            status: this.status,
                            page: String(this.page || 1),
                        });

                        if (this.search) {
                            params.set('q', this.search);
                        }

                        const response = await fetch(`${this.pollUrl}?${params.toString()}`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            cache: 'no-store',
                        });

                        if (! response.ok) {
                            return;
                        }

                        const data = await response.json();

                        if (data.signature && data.signature !== this.signature) {
                            this.applySnapshot(data);
                        } else {
                            this.refreshRelativeTimes();
                        }
                    } catch (error) {
                        // Ignore transient poll errors.
                    } finally {
                        this.polling = false;
                    }
                },
            }));
        });
    </script>
@endpush
