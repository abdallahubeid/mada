<x-layouts.app title="تقييماتي">
    @if ($employee === null)
        <div class="mx-auto max-w-2xl">
            <div class="rounded-2xl border border-mist-200 bg-white p-8 text-center shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-400/15 text-2xl">⭐</div>
                <h1 class="mt-4 font-display text-2xl font-bold text-ink-900 dark:text-ink-50">تقييماتي</h1>
                <p class="mt-2 text-sm text-mist-500">
                    حسابك الإداري غير مرتبط بملف موظف، لذا لا تتوفر تقييمات لعرضها.
                </p>
            </div>
        </div>
    @else
        <div class="mx-auto max-w-4xl space-y-6" x-data>
            <div>
                <h1 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">تقييماتي</h1>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">
                    سجل تقييمات الأداء المُرسلة والمعتمدة لـ <span class="font-semibold text-ink-700 dark:text-mist-200">{{ $employee->full_name }}</span>
                </p>
            </div>

            @if ($evaluations->isEmpty())
                <div class="rounded-2xl border border-dashed border-mist-200 bg-white p-10 text-center dark:border-ink-600 dark:bg-ink-800">
                    <p class="text-sm font-medium text-ink-900 dark:text-ink-50">لا توجد تقييمات متاحة بعد.</p>
                    <p class="mt-1 text-sm text-mist-500">تظهر هنا تقييماتك فور إرسالها من مديرك المباشر أو اعتمادها من الإدارة.</p>
                </div>
            @else
                <div class="overflow-hidden rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                            <thead class="bg-mist-50 text-mist-500 dark:bg-ink-900 dark:text-mist-400">
                                <tr>
                                    <th class="w-12 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">#</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">الفترة</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">التقييم</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">ملاحظات المقيّم</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">المقيّم</th>
                                    <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-center">الحالة</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                                @foreach ($evaluations as $row)
                                    @php $evaluation = $row['evaluation']; @endphp
                                    <tr>
                                        <td class="w-12 px-4 py-3 text-center text-sm tabular-nums text-mist-500">{{ $loop->iteration }}</td>
                                        <td class="px-4 py-3 font-medium text-ink-900 dark:text-ink-50 text-start">
                                            {{ $row['periodLabel'] }}
                                        </td>
                                        <td class="px-4 py-3 text-start">
                                            @if ($evaluation->rating !== null)
                                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-400/15 px-2.5 py-0.5 text-xs font-bold text-emerald-700 dark:text-emerald-400">
                                                    {{ number_format((float) $evaluation->rating, 2) }} / 5
                                                </span>
                                            @else
                                                <span class="text-xs text-mist-500">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-mist-600 dark:text-mist-300 text-start">
                                            {{ $evaluation->notes ?: '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-mist-600 dark:text-mist-300 text-start">
                                            {{ $evaluation->evaluator?->full_name ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span @class([
                                                'rounded-full px-2.5 py-0.5 text-[10px] font-bold',
                                                'bg-emerald-400/15 text-emerald-700 dark:text-emerald-400' => $evaluation->status->value === 'approved',
                                                'bg-sky-400/15 text-sky-800 dark:text-sky-300' => $evaluation->status->value === 'submitted',
                                            ])>
                                                {{ $evaluation->status->label() }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    @endif
</x-layouts.app>
