@extends('layouts.admin')

@section('title', 'إعدادات الصفحة')

@section('breadcrumbs')
    <span class="text-mist-500 dark:text-mist-400">محتوى الصفحة الرئيسية</span>
    <span class="mx-1.5 text-mist-300 dark:text-mist-600">/</span>
    <span class="text-ink-700 dark:text-mist-200">إعدادات الصفحة</span>
@endsection

@section('content')
    @php
        $inputClass = 'w-full rounded-xl border border-mist-200 bg-white px-3 py-2.5 text-sm text-ink-700 placeholder:text-mist-400 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50';
        $labelClass = 'mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200';
        $cardClass = 'rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800';
        $val = fn (string $key) => old($key, $settings[$key] ?? '');
        $tabs = [
            'site' => 'الموقع',
            'hero' => 'Hero',
            'problem' => 'Problem',
            'solution' => 'Solution',
            'offerings' => 'Offerings',
            'modules' => 'Modules',
            'previews' => 'Previews',
            'ai' => 'AI',
            'features' => 'Features',
            'testimonials' => 'Testimonials',
            'pricing' => 'Pricing',
            'faq' => 'FAQ',
            'cta' => 'CTA',
            'footer' => 'Footer',
            'privacy' => 'Privacy',
            'terms' => 'Terms',
        ];
    @endphp

    <div x-data="{ tab: 'hero' }">
        <div>
            <h2 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">إعدادات صفحة الهبوط</h2>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">إدارة محتوى الصفحة عبر مفاتيح فريدة لكل قسم.</p>
        </div>

        <div class="mt-6 overflow-x-auto">
            <div class="flex min-w-max items-center gap-1 border-b border-mist-200 dark:border-ink-700">
                @foreach ($tabs as $key => $label)
                    <button
                        type="button"
                        @click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}'
                            ? 'border-emerald-400 text-emerald-600 dark:text-emerald-400'
                            : 'border-transparent text-mist-500 hover:text-ink-700 dark:text-mist-400 dark:hover:text-mist-200'"
                        class="whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium transition-all duration-200"
                    >{{ $label }}</button>
                @endforeach
            </div>
        </div>

        <form method="POST" action="{{ route('admin.landing.settings.update') }}" enctype="multipart/form-data" class="mt-6">
            @csrf
            @method('PUT')

            <div x-show="tab === 'site'" x-cloak class="{{ $cardClass }} space-y-4">
                <h3 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">Site</h3>
                <div>
                    <label class="{{ $labelClass }}">site_logo</label>
                    @if ($val('site_logo'))
                        <p class="mb-2 text-xs text-mist-500" dir="ltr">{{ $val('site_logo') }}</p>
                    @endif
                    <input type="file" name="site_logo" accept="image/*" class="{{ $inputClass }}">
                </div>
            </div>

            <div x-show="tab === 'hero'" x-cloak class="{{ $cardClass }} space-y-4">
                <h3 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">Hero</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="{{ $labelClass }}">hero_badge_text</label>
                        <input type="text" name="hero_badge_text" value="{{ $val('hero_badge_text') }}" class="{{ $inputClass }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $labelClass }}">hero_title</label>
                        <input type="text" name="hero_title" value="{{ $val('hero_title') }}" class="{{ $inputClass }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $labelClass }}">hero_description</label>
                        <textarea name="hero_description" rows="3" class="{{ $inputClass }}">{{ $val('hero_description') }}</textarea>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">hero_btn1_text</label>
                        <input type="text" name="hero_btn1_text" value="{{ $val('hero_btn1_text') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">hero_btn1_link</label>
                        <input type="text" dir="ltr" name="hero_btn1_link" value="{{ $val('hero_btn1_link') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">hero_btn2_text</label>
                        <input type="text" name="hero_btn2_text" value="{{ $val('hero_btn2_text') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">hero_btn2_link</label>
                        <input type="text" dir="ltr" name="hero_btn2_link" value="{{ $val('hero_btn2_link') }}" class="{{ $inputClass }}">
                    </div>
                </div>
            </div>

            <div x-show="tab === 'problem'" x-cloak class="{{ $cardClass }} space-y-4">
                <h3 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">Problem</h3>
                <div class="space-y-4">
                    <div>
                        <label class="{{ $labelClass }}">problem_badge_text</label>
                        <input type="text" name="problem_badge_text" value="{{ $val('problem_badge_text') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">problem_title</label>
                        <input type="text" name="problem_title" value="{{ $val('problem_title') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">problem_sup_title</label>
                        <textarea name="problem_sup_title" rows="2" class="{{ $inputClass }}">{{ $val('problem_sup_title') }}</textarea>
                    </div>
                </div>
            </div>

            <div x-show="tab === 'solution'" x-cloak class="{{ $cardClass }} space-y-4">
                <h3 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">Solution</h3>
                <div class="space-y-4">
                    <div>
                        <label class="{{ $labelClass }}">solution_badge_text</label>
                        <input type="text" name="solution_badge_text" value="{{ $val('solution_badge_text') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">solution_title</label>
                        <input type="text" name="solution_title" value="{{ $val('solution_title') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">solution_description</label>
                        <textarea name="solution_description" rows="3" class="{{ $inputClass }}">{{ $val('solution_description') }}</textarea>
                    </div>
                </div>
            </div>

            <div x-show="tab === 'offerings'" x-cloak class="{{ $cardClass }} space-y-4">
                <h3 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">Offerings</h3>
                <div class="space-y-4">
                    <div>
                        <label class="{{ $labelClass }}">offerings_title</label>
                        <input type="text" name="offerings_title" value="{{ $val('offerings_title') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">offerings_sup_title</label>
                        <textarea name="offerings_sup_title" rows="2" class="{{ $inputClass }}">{{ $val('offerings_sup_title') }}</textarea>
                    </div>
                </div>
            </div>

            <div x-show="tab === 'modules'" x-cloak class="{{ $cardClass }} space-y-4">
                <h3 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">Modules</h3>
                <div class="space-y-4">
                    <div>
                        <label class="{{ $labelClass }}">modules_badge_text</label>
                        <input type="text" name="modules_badge_text" value="{{ $val('modules_badge_text') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">modules_title</label>
                        <input type="text" name="modules_title" value="{{ $val('modules_title') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">modules_sup_title</label>
                        <textarea name="modules_sup_title" rows="2" class="{{ $inputClass }}">{{ $val('modules_sup_title') }}</textarea>
                    </div>
                </div>
            </div>

            <div x-show="tab === 'previews'" x-cloak class="{{ $cardClass }} space-y-4">
                <h3 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">Product Previews</h3>
                <div class="space-y-4">
                    <div>
                        <label class="{{ $labelClass }}">previews_title</label>
                        <input type="text" name="previews_title" value="{{ $val('previews_title') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">previews_sup_title</label>
                        <textarea name="previews_sup_title" rows="2" class="{{ $inputClass }}">{{ $val('previews_sup_title') }}</textarea>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">previews_img</label>
                        @if ($val('previews_img'))
                            <p class="mb-2 text-xs text-mist-500" dir="ltr">{{ $val('previews_img') }}</p>
                        @endif
                        <input type="file" name="previews_img" accept="image/*" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">previews_video</label>
                        @if ($val('previews_video'))
                            <p class="mb-2 text-xs text-mist-500" dir="ltr">{{ $val('previews_video') }}</p>
                        @endif
                        <input type="file" name="previews_video" accept="video/*" class="{{ $inputClass }}">
                    </div>
                </div>
            </div>

            <div x-show="tab === 'ai'" x-cloak class="{{ $cardClass }} space-y-4">
                <h3 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">AI</h3>
                <div class="space-y-4">
                    <div>
                        <label class="{{ $labelClass }}">ai_badge_text</label>
                        <input type="text" name="ai_badge_text" value="{{ $val('ai_badge_text') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">ai_title</label>
                        <input type="text" name="ai_title" value="{{ $val('ai_title') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">ai_sup_title</label>
                        <textarea name="ai_sup_title" rows="2" class="{{ $inputClass }}">{{ $val('ai_sup_title') }}</textarea>
                    </div>
                </div>
            </div>

            <div x-show="tab === 'features'" x-cloak class="{{ $cardClass }} space-y-4">
                <h3 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">Features</h3>
                <div class="space-y-4">
                    <div>
                        <label class="{{ $labelClass }}">features_title</label>
                        <input type="text" name="features_title" value="{{ $val('features_title') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">features_sup_title</label>
                        <textarea name="features_sup_title" rows="2" class="{{ $inputClass }}">{{ $val('features_sup_title') }}</textarea>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">features_badge_text</label>
                        <input type="text" name="features_badge_text" value="{{ $val('features_badge_text') }}" class="{{ $inputClass }}">
                    </div>
                </div>
            </div>

            <div x-show="tab === 'testimonials'" x-cloak class="{{ $cardClass }} space-y-4">
                <h3 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">Testimonials</h3>
                <div class="space-y-4">
                    <div>
                        <label class="{{ $labelClass }}">testimonials_badge_text</label>
                        <input type="text" name="testimonials_badge_text" value="{{ $val('testimonials_badge_text') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">testimonials_title</label>
                        <input type="text" name="testimonials_title" value="{{ $val('testimonials_title') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">testimonials_sup_title</label>
                        <textarea name="testimonials_sup_title" rows="2" class="{{ $inputClass }}">{{ $val('testimonials_sup_title') }}</textarea>
                    </div>
                </div>
            </div>

            <div x-show="tab === 'pricing'" x-cloak class="{{ $cardClass }} space-y-4">
                <h3 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">Pricing</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="{{ $labelClass }}">pricing_title</label>
                        <input type="text" name="pricing_title" value="{{ $val('pricing_title') }}" class="{{ $inputClass }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $labelClass }}">pricing_sup_title</label>
                        <textarea name="pricing_sup_title" rows="2" class="{{ $inputClass }}">{{ $val('pricing_sup_title') }}</textarea>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">pricing_btn_text</label>
                        <input type="text" name="pricing_btn_text" value="{{ $val('pricing_btn_text') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">pricing_btn_link</label>
                        <input type="text" dir="ltr" name="pricing_btn_link" value="{{ $val('pricing_btn_link') }}" class="{{ $inputClass }}">
                    </div>
                </div>
            </div>

            <div x-show="tab === 'faq'" x-cloak class="{{ $cardClass }} space-y-4">
                <h3 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">FAQ</h3>
                <div class="space-y-4">
                    <div>
                        <label class="{{ $labelClass }}">faq_title</label>
                        <input type="text" name="faq_title" value="{{ $val('faq_title') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">faq_sup_title</label>
                        <textarea name="faq_sup_title" rows="2" class="{{ $inputClass }}">{{ $val('faq_sup_title') }}</textarea>
                    </div>
                </div>
            </div>

            <div x-show="tab === 'cta'" x-cloak class="{{ $cardClass }} space-y-4">
                <h3 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">CTA</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="{{ $labelClass }}">cta_title</label>
                        <input type="text" name="cta_title" value="{{ $val('cta_title') }}" class="{{ $inputClass }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $labelClass }}">cta_sup_title</label>
                        <textarea name="cta_sup_title" rows="2" class="{{ $inputClass }}">{{ $val('cta_sup_title') }}</textarea>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">cta_btn1_text</label>
                        <input type="text" name="cta_btn1_text" value="{{ $val('cta_btn1_text') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">cta_btn1_link</label>
                        <input type="text" dir="ltr" name="cta_btn1_link" value="{{ $val('cta_btn1_link') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">cta_btn2_text</label>
                        <input type="text" name="cta_btn2_text" value="{{ $val('cta_btn2_text') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">cta_btn2_link</label>
                        <input type="text" dir="ltr" name="cta_btn2_link" value="{{ $val('cta_btn2_link') }}" class="{{ $inputClass }}">
                    </div>
                </div>
            </div>

            <div x-show="tab === 'footer'" x-cloak class="{{ $cardClass }} space-y-6">
                <h3 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">Footer</h3>

                <div class="space-y-4 rounded-xl border border-mist-200 p-4 dark:border-ink-600">
                    <p class="text-xs font-semibold uppercase tracking-wide text-mist-500">Column 1</p>
                    <div>
                        <label class="{{ $labelClass }}">footer_title1</label>
                        <input type="text" name="footer_title1" value="{{ $val('footer_title1') }}" class="{{ $inputClass }}">
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach ([1, 2, 3, 4] as $i)
                            <div>
                                <label class="{{ $labelClass }}">footer_btn{{ $i }}_text</label>
                                <input type="text" name="footer_btn{{ $i }}_text" value="{{ $val('footer_btn'.$i.'_text') }}" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">footer_btn{{ $i }}_link</label>
                                <input type="text" dir="ltr" name="footer_btn{{ $i }}_link" value="{{ $val('footer_btn'.$i.'_link') }}" class="{{ $inputClass }}">
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="space-y-4 rounded-xl border border-mist-200 p-4 dark:border-ink-600">
                    <p class="text-xs font-semibold uppercase tracking-wide text-mist-500">Column 2</p>
                    <div>
                        <label class="{{ $labelClass }}">footer_title2</label>
                        <input type="text" name="footer_title2" value="{{ $val('footer_title2') }}" class="{{ $inputClass }}">
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach ([5, 6, 7] as $i)
                            <div>
                                <label class="{{ $labelClass }}">footer_btn{{ $i }}_text</label>
                                <input type="text" name="footer_btn{{ $i }}_text" value="{{ $val('footer_btn'.$i.'_text') }}" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">footer_btn{{ $i }}_link</label>
                                <input type="text" dir="ltr" name="footer_btn{{ $i }}_link" value="{{ $val('footer_btn'.$i.'_link') }}" class="{{ $inputClass }}">
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="space-y-4 rounded-xl border border-mist-200 p-4 dark:border-ink-600">
                    <p class="text-xs font-semibold uppercase tracking-wide text-mist-500">Column 3</p>
                    <div>
                        <label class="{{ $labelClass }}">footer_title3</label>
                        <input type="text" name="footer_title3" value="{{ $val('footer_title3') }}" class="{{ $inputClass }}">
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach ([8, 9] as $i)
                            <div>
                                <label class="{{ $labelClass }}">footer_btn{{ $i }}_text</label>
                                <input type="text" name="footer_btn{{ $i }}_text" value="{{ $val('footer_btn'.$i.'_text') }}" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">footer_btn{{ $i }}_link</label>
                                <input type="text" dir="ltr" name="footer_btn{{ $i }}_link" value="{{ $val('footer_btn'.$i.'_link') }}" class="{{ $inputClass }}">
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="space-y-4 rounded-xl border border-mist-200 p-4 dark:border-ink-600">
                    <p class="text-xs font-semibold uppercase tracking-wide text-mist-500">Social Media</p>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach ([1, 2, 3, 4, 5] as $i)
                            <div>
                                <label class="{{ $labelClass }}">social_btn{{ $i }}_text</label>
                                <input type="text" name="social_btn{{ $i }}_text" value="{{ $val('social_btn'.$i.'_text') }}" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">social_btn{{ $i }}_link</label>
                                <input type="text" dir="ltr" name="social_btn{{ $i }}_link" value="{{ $val('social_btn'.$i.'_link') }}" class="{{ $inputClass }}">
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div x-show="tab === 'privacy'" x-cloak class="{{ $cardClass }} space-y-4">
                <h3 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">Privacy Policy</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="{{ $labelClass }}">privacy_badge_text</label>
                        <input type="text" name="privacy_badge_text" value="{{ $val('privacy_badge_text') }}" class="{{ $inputClass }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $labelClass }}">privacy_title</label>
                        <input type="text" name="privacy_title" value="{{ $val('privacy_title') }}" class="{{ $inputClass }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $labelClass }}">privacy_sub_title</label>
                        <input type="text" name="privacy_sub_title" value="{{ $val('privacy_sub_title') }}" class="{{ $inputClass }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $labelClass }}">privacy_description</label>
                        <textarea name="privacy_description" rows="5" class="{{ $inputClass }}">{{ $val('privacy_description') }}</textarea>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">privacy_btn_text</label>
                        <input type="text" name="privacy_btn_text" value="{{ $val('privacy_btn_text') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">privacy_btn_link</label>
                        <input type="text" dir="ltr" name="privacy_btn_link" value="{{ $val('privacy_btn_link') }}" class="{{ $inputClass }}">
                    </div>
                </div>
            </div>

            <div x-show="tab === 'terms'" x-cloak class="{{ $cardClass }} space-y-4">
                <h3 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">Terms &amp; Conditions</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="{{ $labelClass }}">terms_badge_text</label>
                        <input type="text" name="terms_badge_text" value="{{ $val('terms_badge_text') }}" class="{{ $inputClass }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $labelClass }}">terms_title</label>
                        <input type="text" name="terms_title" value="{{ $val('terms_title') }}" class="{{ $inputClass }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $labelClass }}">terms_sub_title</label>
                        <input type="text" name="terms_sub_title" value="{{ $val('terms_sub_title') }}" class="{{ $inputClass }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $labelClass }}">terms_description</label>
                        <textarea name="terms_description" rows="5" class="{{ $inputClass }}">{{ $val('terms_description') }}</textarea>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">terms_btn_text</label>
                        <input type="text" name="terms_btn_text" value="{{ $val('terms_btn_text') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">terms_btn_link</label>
                        <input type="text" dir="ltr" name="terms_btn_link" value="{{ $val('terms_btn_link') }}" class="{{ $inputClass }}">
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3 border-t border-mist-200 pt-5 dark:border-ink-700">
                <a href="{{ route('admin.landing.settings.edit') }}" class="rounded-xl px-4 py-2 text-sm font-semibold text-mist-600 transition hover:bg-mist-100 dark:text-mist-300 dark:hover:bg-ink-800">إلغاء</a>
                <button type="submit" class="rounded-xl bg-emerald-400 px-5 py-2 text-sm font-semibold text-emerald-900 shadow-glow transition hover:bg-emerald-300 active:scale-95">حفظ الإعدادات</button>
            </div>
        </form>
    </div>
@endsection
