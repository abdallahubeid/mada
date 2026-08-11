@php
    use App\Domain\Tenancy\Enums\SubscriptionStatus;
    use App\Domain\Tenancy\Enums\TenantInvoiceStatus;

    $statusClasses = [
        SubscriptionStatus::Active->value => 'bg-emerald-400/15 text-emerald-700 dark:text-emerald-300',
        SubscriptionStatus::Trial->value => 'bg-sky-400/15 text-sky-800 dark:text-sky-300',
        SubscriptionStatus::Expired->value => 'bg-danger-solid/10 text-danger-solid',
    ];

    $invoiceStatusClasses = [
        TenantInvoiceStatus::Paid->value => 'bg-emerald-400/15 text-emerald-700 dark:text-emerald-300',
        TenantInvoiceStatus::Pending->value => 'bg-amber-400/15 text-amber-800 dark:text-amber-300',
        TenantInvoiceStatus::Failed->value => 'bg-danger-solid/10 text-danger-solid',
    ];
@endphp

<x-layouts.app title="إدارة الاشتراك والخطط">
    <div class="mx-auto max-w-5xl space-y-6">
        <div>
            <h1 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">إدارة الاشتراك والخطط</h1>
            <p class="mt-1 text-sm text-mist-500">راجع خطتك الحالية، استخدام الموارد، وسجل الفواتير.</p>
        </div>

        {{-- Header card --}}
        <section class="rounded-2xl border border-mist-200 bg-gradient-to-br from-emerald-400/15 via-white to-sky-400/10 p-5 shadow-sm dark:border-ink-600 dark:from-emerald-400/10 dark:via-ink-800 dark:to-ink-800 sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-display text-xl font-bold text-ink-900 dark:text-ink-50">{{ $planName }}</h2>
                        <span @class(['inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold', $statusClasses[$status->value] ?? ''])>
                            {{ $status->label() }}
                        </span>
                        @if ($renewalWarning)
                            <span class="inline-flex rounded-full bg-amber-400/15 px-2.5 py-0.5 text-xs font-semibold text-amber-800 dark:text-amber-300">
                                يتجدد خلال {{ $daysUntilRenewal }} يوم
                            </span>
                        @endif
                    </div>
                    <p class="mt-2 text-sm text-mist-500">
                        دورة الفوترة: <span class="font-semibold text-ink-700 dark:text-mist-200">{{ $billingCycle->label() }}</span>
                        @if ($price !== null)
                            · السعر:
                            <span class="font-semibold text-ink-700 dark:text-mist-200" dir="ltr">
                                {{ rtrim(rtrim(number_format((float) $price, 2, '.', ''), '0'), '.') }} {{ $currency }}
                            </span>
                        @else
                            · السعر: <span class="font-semibold text-ink-700 dark:text-mist-200">مخصص / تواصل معنا</span>
                        @endif
                    </p>
                    <p class="mt-1 text-sm text-mist-500" dir="ltr">
                        @if ($renewsAt)
                            التجديد / الانتهاء: {{ $renewsAt->format('Y-m-d') }}
                        @elseif ($trialEndsAt)
                            نهاية التجربة: {{ $trialEndsAt->format('Y-m-d') }}
                        @else
                            لا يوجد موعد تجديد محدد
                        @endif
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a
                        href="{{ route('marketing.pricing') }}"
                        class="rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-glow transition hover:bg-emerald-300"
                    >
                        ترقية الخطة
                    </a>
                    <a
                        href="{{ route('marketing.contact') }}"
                        class="rounded-xl border border-mist-200 bg-white/70 px-4 py-2 text-sm font-semibold text-ink-700 transition hover:border-emerald-400 hover:text-emerald-700 dark:border-ink-600 dark:bg-ink-900/50 dark:text-mist-200"
                    >
                        تجديد الاشتراك
                    </a>
                </div>
            </div>
        </section>

        {{-- Usage meters --}}
        <section class="grid gap-3 sm:grid-cols-3">
            @foreach ($usage as $meter)
                <div class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-sm font-semibold text-ink-900 dark:text-ink-50">{{ $meter['label'] }}</p>
                        <p class="text-xs text-mist-500" dir="ltr">
                            {{ $meter['used'] }}
                            /
                            {{ $meter['limit'] === null ? '∞' : $meter['limit'] }}
                            {{ $meter['unit'] }}
                        </p>
                    </div>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-mist-100 dark:bg-ink-700">
                        <div
                            class="h-full rounded-full transition-all {{ $meter['percent'] >= 90 ? 'bg-amber-400' : 'bg-emerald-400' }}"
                            style="width: {{ $meter['limit'] === null ? 8 : $meter['percent'] }}%"
                        ></div>
                    </div>
                    <p class="mt-2 text-xs text-mist-500">
                        @if ($meter['limit'] === null)
                            غير محدود في خطتك الحالية
                        @else
                            {{ $meter['percent'] }}% من الحد الأقصى
                        @endif
                    </p>
                </div>
            @endforeach
        </section>

        {{-- Invoices --}}
        <section class="space-y-3">
            <h2 class="text-sm font-semibold text-ink-900 dark:text-ink-50">سجل الفواتير</h2>
            <div class="w-full overflow-x-auto rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                    <thead class="bg-mist-50 dark:bg-ink-900">
                        <tr>
                            <th class="w-12 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">#</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">التاريخ</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">رقم الفاتورة</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">المبلغ</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-center">الحالة</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-end">PDF</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                        @forelse ($invoices as $invoice)
                            <tr>
                                <td class="w-12 px-4 py-3 text-center text-sm tabular-nums text-mist-500">{{ $loop->iteration }}</td>
                                <td class="px-4 py-3 tabular-nums text-start"><x-ui.ltr>{{ $invoice->issued_at?->format('Y-m-d') }}</x-ui.ltr></td>
                                <td class="px-4 py-3 font-medium text-start"><x-ui.ltr>{{ $invoice->number }}</x-ui.ltr></td>
                                <td class="px-4 py-3 tabular-nums text-start"><x-ui.ltr>{{ rtrim(rtrim(number_format((float) $invoice->amount, 2, '.', ''), '0'), '.') }}
                                    {{ $invoice->currency }}</x-ui.ltr></td>
                                <td class="px-4 py-3 text-center">
                                    <span @class(['inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold', $invoiceStatusClasses[$invoice->status->value] ?? ''])>
                                        {{ $invoice->status->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-end">
                                    @if ($invoice->pdf_path)
                                        <a
                                            href="{{ route('tenant.subscription.invoices.download', $invoice) }}"
                                            class="text-xs font-semibold text-emerald-600 hover:underline dark:text-emerald-400"
                                        >
                                            تحميل PDF
                                        </a>
                                    @else
                                        <span class="text-xs text-mist-400">غير متاح</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-ui.table-empty :colspan="6" icon="💳" message="لا توجد فواتير بعد." />
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $invoices->links() }}</div>
        </section>
    </div>
</x-layouts.app>
