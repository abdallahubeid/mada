{{-- Contact / Book-a-Demo (docs/MARKETING.md §2). --}}
<x-layouts.marketing
    title="تواصل معنا — Veyra ERP"
    description="تواصل مع فريق Veyra لحجز عرض توضيحي، استفسارات المبيعات، أو الدعم. نرد عادة خلال يوم عمل واحد."
>
    <x-marketing.nav />

    <main>
        <x-marketing.page-hero
            eyebrow="تواصل معنا"
            title="لنبدأ محادثة"
            subtitle="احجز عرضاً توضيحياً، اسأل عن الخطط، أو اترك رسالتك — فريقنا جاهز لمساعدتك."
        />

        <section class="bg-white py-16 sm:py-20 dark:bg-ink-900">
            <div class="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-5 lg:gap-16 lg:px-8">
                {{-- Side info --}}
                <aside class="space-y-6 lg:col-span-2">
                    <div class="rounded-2xl border border-mist-200 bg-ink-50/40 p-6 dark:border-ink-800 dark:bg-ink-800/60">
                        <h2 class="font-display text-lg font-semibold text-ink-900 dark:text-ink-50">معلومات التواصل</h2>
                        <ul class="mt-4 space-y-4 text-sm text-mist-600 dark:text-mist-300">
                            <li class="flex items-start gap-3">
                                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-400/10 text-emerald-600 dark:text-emerald-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                                </span>
                                <div>
                                    <p class="font-medium text-ink-900 dark:text-ink-50">البريد</p>
                                    <a href="mailto:hello@veyra.app" class="text-emerald-600 hover:underline dark:text-emerald-400">hello@veyra.app</a>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-400/10 text-emerald-600 dark:text-emerald-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>
                                </span>
                                <div>
                                    <p class="font-medium text-ink-900 dark:text-ink-50">المكتب</p>
                                    <p>الرياض، المملكة العربية السعودية</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-400/10 text-emerald-600 dark:text-emerald-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                </span>
                                <div>
                                    <p class="font-medium text-ink-900 dark:text-ink-50">وقت الاستجابة</p>
                                    <p>عادة خلال يوم عمل واحد</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </aside>

                {{-- Form --}}
                <div class="lg:col-span-3">
                    @if (session('status'))
                        <div class="mb-6 rounded-2xl border border-emerald-400/30 bg-emerald-400/10 px-5 py-4 text-sm font-medium text-emerald-700 dark:text-emerald-300" role="status">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('marketing.contact.store') }}" class="rounded-3xl border border-mist-200 bg-ink-50/40 p-6 sm:p-8 dark:border-ink-800 dark:bg-ink-800/60">
                        @csrf

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="name" class="mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200">الاسم الكامل</label>
                                <input id="name" name="name" type="text" value="{{ old('name') }}" required autocomplete="name"
                                    class="w-full rounded-xl border border-mist-200 bg-white px-4 py-2.5 text-sm text-ink-900 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-700 dark:bg-ink-900 dark:text-ink-50 @error('name') border-danger-solid @enderror">
                                @error('name') <p class="mt-1.5 text-xs text-danger-solid">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="email" class="mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200">البريد الإلكتروني</label>
                                <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email"
                                    class="w-full rounded-xl border border-mist-200 bg-white px-4 py-2.5 text-sm text-ink-900 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-700 dark:bg-ink-900 dark:text-ink-50 @error('email') border-danger-solid @enderror">
                                @error('email') <p class="mt-1.5 text-xs text-danger-solid">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="company" class="mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200">اسم المؤسسة <span class="text-mist-400">(اختياري)</span></label>
                                <input id="company" name="company" type="text" value="{{ old('company') }}" autocomplete="organization"
                                    class="w-full rounded-xl border border-mist-200 bg-white px-4 py-2.5 text-sm text-ink-900 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-700 dark:bg-ink-900 dark:text-ink-50">
                            </div>

                            <div>
                                <label for="subject" class="mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200">الموضوع</label>
                                <select id="subject" name="subject" required
                                    class="w-full rounded-xl border border-mist-200 bg-white px-4 py-2.5 text-sm text-ink-900 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-700 dark:bg-ink-900 dark:text-ink-50 @error('subject') border-danger-solid @enderror">
                                    <option value="" disabled @selected(old('subject') === null)>اختر الموضوع</option>
                                    @foreach ($subjects as $value => $label)
                                        <option value="{{ $value }}" @selected(old('subject') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('subject') <p class="mt-1.5 text-xs text-danger-solid">{{ $message }}</p> @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label for="message" class="mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200">الرسالة</label>
                                <textarea id="message" name="message" rows="6" required
                                    class="w-full rounded-xl border border-mist-200 bg-white px-4 py-2.5 text-sm text-ink-900 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-700 dark:bg-ink-900 dark:text-ink-50 @error('message') border-danger-solid @enderror">{{ old('message') }}</textarea>
                                @error('message') <p class="mt-1.5 text-xs text-danger-solid">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-emerald-500 px-6 py-3 text-sm font-semibold text-ink-950 shadow-glow transition duration-200 hover:bg-emerald-400 active:scale-[0.98]">
                                إرسال الرسالة
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <x-marketing.footer />
</x-layouts.marketing>
