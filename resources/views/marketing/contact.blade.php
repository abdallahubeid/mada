{{-- Contact / Book-a-Demo (docs/MARKETING.md §2), laid out like the Andromeda contact page. --}}
@php
    $channels = [
        [
            'label' => 'البريد',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />',
            'value' => '<a href="mailto:hello@mada.app" class="site-link inline-block py-0.5" dir="ltr">hello@mada.app</a>',
        ],
        [
            'label' => 'المكتب',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />',
            'value' => 'الرياض، المملكة العربية السعودية',
        ],
        [
            'label' => 'وقت الاستجابة',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />',
            'value' => 'عادة خلال يوم عمل واحد',
        ],
    ];
@endphp

<x-layouts.marketing
    title="تواصل معنا — مدى"
    description="تواصل مع فريق مدى لحجز عرض توضيحي، استفسارات المبيعات، أو الدعم. نرد عادة خلال يوم عمل واحد."
>
    <x-marketing.nav />

    <main>
        <x-marketing.page-hero
            eyebrow="تواصل معنا"
            title="لنبدأ محادثة"
            subtitle="احجز عرضاً توضيحياً، اسأل عن الخطط، أو اترك رسالتك — فريقنا جاهز لمساعدتك."
        />

        <section class="site-section">
            <div class="site-container">
                <div class="grid items-center justify-center gap-12 lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)] lg:gap-16 xl:px-16">
                    {{-- The template's illustration slot: the contact details on a card resting on circles. --}}
                    <aside class="relative isolate p-6 sm:p-[60px]">
                        <x-marketing.circles set="figure" />

                        <div class="site-card relative p-7">
                            <h2 class="site-h4">معلومات التواصل</h2>
                            <ul class="mt-6 space-y-5">
                                @foreach ($channels as $channel)
                                    <li class="flex items-start gap-4">
                                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-md border border-site-peach bg-site-cream text-site-primary">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">{!! $channel['icon'] !!}</svg>
                                        </span>
                                        <div>
                                            <p class="font-medium text-site-dark">{{ $channel['label'] }}</p>
                                            <p>{!! $channel['value'] !!}</p>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </aside>

                    <form method="POST" action="{{ route('marketing.contact.store') }}" class="rounded-xl bg-white p-6 shadow-[0_4px_25px_rgb(0_0_0/0.05)] sm:p-8">
                        @csrf
                        <h2 class="site-h4 mb-6">أرسل رسالة</h2>

                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <label for="name" class="site-label">الاسم الكامل</label>
                                <input id="name" name="name" type="text" value="{{ old('name') }}" required autocomplete="name" placeholder="الاسم الكامل"
                                    class="site-input" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                                @error('name') <p id="name-error" class="site-error">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="email" class="site-label">البريد الإلكتروني</label>
                                <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" placeholder="name@company.com" dir="ltr"
                                    class="site-input text-start" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                                @error('email') <p id="email-error" class="site-error">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="company" class="site-label">اسم المؤسسة <span class="font-normal text-site-text">(اختياري)</span></label>
                                <input id="company" name="company" type="text" value="{{ old('company') }}" autocomplete="organization"
                                    class="site-input">
                            </div>

                            <div>
                                <label for="subject" class="site-label">الموضوع</label>
                                <select id="subject" name="subject" required
                                    class="site-input" @error('subject') aria-invalid="true" aria-describedby="subject-error" @enderror>
                                    <option value="" disabled @selected(old('subject') === null)>اختر الموضوع</option>
                                    @foreach ($subjects as $value => $label)
                                        <option value="{{ $value }}" @selected(old('subject') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('subject') <p id="subject-error" class="site-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label for="message" class="site-label">الرسالة</label>
                                <textarea id="message" name="message" rows="6" required
                                    class="site-input" @error('message') aria-invalid="true" aria-describedby="message-error" @enderror>{{ old('message') }}</textarea>
                                @error('message') <p id="message-error" class="site-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <button type="submit" class="site-btn site-btn-primary mt-6 w-full">إرسال الرسالة</button>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <x-marketing.footer />
</x-layouts.marketing>
