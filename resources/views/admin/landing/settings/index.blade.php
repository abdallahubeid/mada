@extends('layouts.admin')

@section('title', 'إعدادات الصفحة')

@section('breadcrumbs')
    <span class="text-mist-500 dark:text-mist-400">محتوى الصفحة الرئيسية</span>
    <span class="mx-1.5 text-mist-400 dark:text-mist-600" aria-hidden="true">/</span>
    <span class="text-ink-700 dark:text-mist-200">إعدادات الصفحة</span>
@endsection

@section('content')
    @php
        $inputClass = 'ui-control w-full';
        $labelClass = 'ui-label';
        $cardClass = 'ui-card p-4';
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
            'features' => 'Why Us',
            'testimonials' => 'Testimonials',
            'pricing' => 'Pricing',
            'faq' => 'FAQ',
            'cta' => 'CTA',
            'footer' => 'Footer & Social',
            'privacy' => 'Privacy',
            'terms' => 'Terms',
        ];
    @endphp

    <div x-data="{ tab: 'hero' }">
        <div>
            <h1 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">إعدادات صفحة الهبوط</h1>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">إدارة محتوى الصفحة عبر مفاتيح فريدة لكل قسم.</p>
        </div>

        <div class="mt-6 w-full overflow-x-auto">
            <div class="flex min-w-max items-center gap-1 border-b border-mist-200 dark:border-ink-700">
                @foreach ($tabs as $key => $label)
                    <button
                        type="button"
                        @click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}'
                            ? 'border-brand-500 text-brand-600 dark:text-brand-300'
                            : 'border-transparent text-mist-500 hover:text-ink-700 dark:text-mist-400 dark:hover:text-mist-200'"
                        class="whitespace-nowrap border-b-2 px-3 py-2 text-sm font-medium transition-colors duration-150"
                    >{{ $label }}</button>
                @endforeach
            </div>
        </div>

        <form method="POST" action="{{ route('admin.landing.settings.update') }}" enctype="multipart/form-data" class="mt-6">
            @csrf
            @method('PUT')

            <div x-show="tab === 'site'" x-cloak class="{{ $cardClass }} space-y-4">
                <h3 class="font-display text-base font-medium text-ink-900 dark:text-ink-50">Site</h3>
                @include('admin.landing.settings._setting-image-field', [
                    'key' => 'site_logo',
                    'label' => 'site_logo',
                    'accept' => 'image/*',
                    'previewClass' => 'h-20 w-auto max-w-xs object-contain',
                ])
                @include('admin.landing.settings._setting-image-field', [
                    'key' => 'site_favicon',
                    'label' => 'site_favicon',
                    'accept' => '.ico,.png,.svg,image/x-icon,image/svg+xml',
                    'previewClass' => 'h-16 w-auto max-w-[4rem] object-contain',
                ])
            </div>

            <div x-show="tab === 'hero'" x-cloak class="{{ $cardClass }} space-y-4">
                <h3 class="font-display text-base font-medium text-ink-900 dark:text-ink-50">Hero</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="f-index-hero-badge-text-1" class="{{ $labelClass }}">hero_badge_text</label>
                        <input id="f-index-hero-badge-text-1" type="text" name="hero_badge_text" value="{{ $val('hero_badge_text') }}" class="{{ $inputClass }}">
                    </div>
                    <div class="sm:col-span-2">
                        @include('admin.landing.settings._decorated-title-field', [
                            'key' => 'hero_title',
                            'label' => 'hero_title',
                            'context' => 'hero',
                        ])
                    </div>
                    <div class="sm:col-span-2">
                        <label for="f-index-hero-description-2" class="{{ $labelClass }}">hero_description</label>
                        <textarea id="f-index-hero-description-2" name="hero_description" rows="3" class="{{ $inputClass }}">{{ $val('hero_description') }}</textarea>
                    </div>
                    <div>
                        <label for="f-index-hero-btn1-text-3" class="{{ $labelClass }}">hero_btn1_text</label>
                        <input id="f-index-hero-btn1-text-3" type="text" name="hero_btn1_text" value="{{ $val('hero_btn1_text') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label for="f-index-hero-btn1-link-4" class="{{ $labelClass }}">hero_btn1_link</label>
                        <input id="f-index-hero-btn1-link-4" type="text" dir="ltr" name="hero_btn1_link" value="{{ $val('hero_btn1_link') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label for="f-index-hero-btn2-text-5" class="{{ $labelClass }}">hero_btn2_text</label>
                        <input id="f-index-hero-btn2-text-5" type="text" name="hero_btn2_text" value="{{ $val('hero_btn2_text') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label for="f-index-hero-btn2-link-6" class="{{ $labelClass }}">hero_btn2_link</label>
                        <input id="f-index-hero-btn2-link-6" type="text" dir="ltr" name="hero_btn2_link" value="{{ $val('hero_btn2_link') }}" class="{{ $inputClass }}">
                    </div>
                </div>
            </div>

            <div x-show="tab === 'problem'" x-cloak class="{{ $cardClass }} space-y-4">
                <h3 class="font-display text-base font-medium text-ink-900 dark:text-ink-50">Problem</h3>
                <div class="space-y-4">
                    <div>
                        <label for="f-index-problems-badge-text-7" class="{{ $labelClass }}">problems_badge_text</label>
                        <input id="f-index-problems-badge-text-7" type="text" name="problems_badge_text" value="{{ $val('problems_badge_text') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        @include('admin.landing.settings._decorated-title-field', [
                            'key' => 'problems_title',
                            'label' => 'problems_title',
                        ])
                    </div>
                    <div>
                        <label for="f-index-problems-sub-title-8" class="{{ $labelClass }}">problems_sub_title</label>
                        <textarea id="f-index-problems-sub-title-8" name="problems_sub_title" rows="2" class="{{ $inputClass }}">{{ $val('problems_sub_title') }}</textarea>
                    </div>
                </div>
            </div>

            <div x-show="tab === 'solution'" x-cloak class="{{ $cardClass }} space-y-4">
                <h3 class="font-display text-base font-medium text-ink-900 dark:text-ink-50">Solution</h3>
                <div class="space-y-4">
                    <div>
                        <label for="f-index-solutions-badge-text-9" class="{{ $labelClass }}">solutions_badge_text</label>
                        <input id="f-index-solutions-badge-text-9" type="text" name="solutions_badge_text" value="{{ $val('solutions_badge_text') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        @include('admin.landing.settings._decorated-title-field', [
                            'key' => 'solutions_title',
                            'label' => 'solutions_title',
                        ])
                    </div>
                    <div>
                        <label for="f-index-solutions-sub-title-10" class="{{ $labelClass }}">solutions_sub_title</label>
                        <textarea id="f-index-solutions-sub-title-10" name="solutions_sub_title" rows="3" class="{{ $inputClass }}">{{ $val('solutions_sub_title') }}</textarea>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="f-index-solutions-btn-text-11" class="{{ $labelClass }}">solutions_btn_text</label>
                            <input id="f-index-solutions-btn-text-11" type="text" name="solutions_btn_text" value="{{ $val('solutions_btn_text') }}" class="{{ $inputClass }}">
                        </div>
                        <div>
                            <label for="f-index-solutions-btn-link-12" class="{{ $labelClass }}">solutions_btn_link</label>
                            <input id="f-index-solutions-btn-link-12" type="text" dir="ltr" name="solutions_btn_link" value="{{ $val('solutions_btn_link') }}" class="{{ $inputClass }}">
                        </div>
                    </div>
                </div>
            </div>

            <div x-show="tab === 'offerings'" x-cloak class="{{ $cardClass }} space-y-4">
                <h3 class="font-display text-base font-medium text-ink-900 dark:text-ink-50">Offerings</h3>
                <div class="space-y-4">
                    <div>
                        @include('admin.landing.settings._decorated-title-field', [
                            'key' => 'offerings_title',
                            'label' => 'offerings_title',
                        ])
                    </div>
                    <div>
                        <label for="f-index-offerings-sub-title-13" class="{{ $labelClass }}">offerings_sub_title</label>
                        <textarea id="f-index-offerings-sub-title-13" name="offerings_sub_title" rows="2" class="{{ $inputClass }}">{{ $val('offerings_sub_title') }}</textarea>
                    </div>
                </div>
            </div>

            <div x-show="tab === 'modules'" x-cloak class="{{ $cardClass }} space-y-4">
                <h3 class="font-display text-base font-medium text-ink-900 dark:text-ink-50">Modules</h3>
                <div class="space-y-4">
                    <div>
                        <label for="f-index-modules-badge-text-14" class="{{ $labelClass }}">modules_badge_text</label>
                        <input id="f-index-modules-badge-text-14" type="text" name="modules_badge_text" value="{{ $val('modules_badge_text') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        @include('admin.landing.settings._decorated-title-field', [
                            'key' => 'modules_title',
                            'label' => 'modules_title',
                        ])
                    </div>
                    <div>
                        <label for="f-index-modules-sub-title-15" class="{{ $labelClass }}">modules_sub_title</label>
                        <textarea id="f-index-modules-sub-title-15" name="modules_sub_title" rows="2" class="{{ $inputClass }}">{{ $val('modules_sub_title') }}</textarea>
                    </div>
                </div>
            </div>

            <div x-show="tab === 'previews'" x-cloak class="{{ $cardClass }} space-y-4">
                <h3 class="font-display text-base font-medium text-ink-900 dark:text-ink-50">Product Previews</h3>
                <div class="space-y-4">
                    <div>
                        <label for="f-index-product-previews-badge-text-16" class="{{ $labelClass }}">product_previews_badge_text</label>
                        <input id="f-index-product-previews-badge-text-16" type="text" name="product_previews_badge_text" value="{{ $val('product_previews_badge_text') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        @include('admin.landing.settings._decorated-title-field', [
                            'key' => 'product_previews_title',
                            'label' => 'product_previews_title',
                        ])
                    </div>
                    <div>
                        <label for="f-index-product-previews-sub-title-17" class="{{ $labelClass }}">product_previews_sub_title</label>
                        <textarea id="f-index-product-previews-sub-title-17" name="product_previews_sub_title" rows="2" class="{{ $inputClass }}">{{ $val('product_previews_sub_title') }}</textarea>
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

                {{--
                    ── Video Section ────────────────────────────────────────
                    Controls the public landing video band. Lives inside the
                    Previews tab because it shares `previews_video`: that upload
                    is the fallback source when no external URL is set.
                --}}
                <div class="mt-8 border-t border-mist-200 pt-6 dark:border-ink-700">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="font-display text-base font-medium text-ink-900 dark:text-ink-50">Video Section</h3>

                        {{--
                            The hidden input is load-bearing: an unchecked
                            checkbox submits nothing at all, so without a
                            paired "0" the controller could never distinguish
                            "switched off" from "field not on this form" and
                            the section could be turned on but never off.
                        --}}
                        <label class="inline-flex cursor-pointer items-center gap-2.5">
                            <input type="hidden" name="is_video_section_active" value="0">
                            <input
                                type="checkbox"
                                name="is_video_section_active"
                                value="1"
                                @checked(\App\Models\Setting::isVideoSectionActive())
                                class="h-4 w-4 rounded-xs border-mist-300 text-brand-500 focus:ring-brand-500/40 dark:border-ink-600"
                            >
                            <span class="text-sm font-medium text-ink-700 dark:text-mist-200">إظهار قسم الفيديو في الصفحة العامة</span>
                        </label>
                    </div>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label for="f-index-video-title-18" class="{{ $labelClass }}">video_title</label>
                            <input id="f-index-video-title-18" type="text" name="video_title" value="{{ $val('video_title') }}" class="{{ $inputClass }}">
                        </div>
                        <div>
                            <label for="f-index-video-description-19" class="{{ $labelClass }}">video_description</label>
                            <textarea id="f-index-video-description-19" name="video_description" rows="2" class="{{ $inputClass }}">{{ $val('video_description') }}</textarea>
                        </div>
                        <div>
                            <label for="f-index-video-url-20" class="{{ $labelClass }}">video_url</label>
                            <input id="f-index-video-url-20" type="url" name="video_url" value="{{ $val('video_url') }}" dir="ltr" placeholder="https://cdn.example.com/tour.mp4" class="{{ $inputClass }}">
                            <p class="mt-1.5 text-xs text-mist-500">
                                يُستخدم هذا الرابط إن وُجد، وإلا فسيُعرض الملف المرفوع في <span dir="ltr">previews_video</span> أعلاه. اتركه فارغاً للرجوع للملف المرفوع.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div x-show="tab === 'ai'" x-cloak class="{{ $cardClass }} space-y-4">
                <h3 class="font-display text-base font-medium text-ink-900 dark:text-ink-50">AI</h3>
                <div class="space-y-4">
                    <div>
                        <label for="f-index-ai-badge-text-21" class="{{ $labelClass }}">ai_badge_text</label>
                        <input id="f-index-ai-badge-text-21" type="text" name="ai_badge_text" value="{{ $val('ai_badge_text') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label for="f-index-ai-title-22" class="{{ $labelClass }}">ai_title</label>
                        <input id="f-index-ai-title-22" type="text" name="ai_title" value="{{ $val('ai_title') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label for="f-index-ai-sub-title-23" class="{{ $labelClass }}">ai_sub_title</label>
                        <textarea id="f-index-ai-sub-title-23" name="ai_sub_title" rows="2" class="{{ $inputClass }}">{{ $val('ai_sub_title') }}</textarea>
                    </div>
                </div>
            </div>

            <div x-show="tab === 'features'" x-cloak class="{{ $cardClass }} space-y-4">
                <h3 class="font-display text-base font-medium text-ink-900 dark:text-ink-50">Why Us</h3>
                <div class="space-y-4">
                    <div>
                        <label for="f-index-why-us-badge-text-24" class="{{ $labelClass }}">why_us_badge_text</label>
                        <input id="f-index-why-us-badge-text-24" type="text" name="why_us_badge_text" value="{{ $val('why_us_badge_text') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        @include('admin.landing.settings._decorated-title-field', [
                            'key' => 'why_us_title',
                            'label' => 'why_us_title',
                        ])
                    </div>
                    <div>
                        <label for="f-index-why-us-sub-title-25" class="{{ $labelClass }}">why_us_sub_title</label>
                        <textarea id="f-index-why-us-sub-title-25" name="why_us_sub_title" rows="2" class="{{ $inputClass }}">{{ $val('why_us_sub_title') }}</textarea>
                    </div>
                </div>
            </div>

            <div x-show="tab === 'testimonials'" x-cloak class="{{ $cardClass }} space-y-4">
                <h3 class="font-display text-base font-medium text-ink-900 dark:text-ink-50">Testimonials</h3>
                <div class="space-y-4">
                    <div>
                        <label for="f-index-testimonials-badge-text-26" class="{{ $labelClass }}">testimonials_badge_text</label>
                        <input id="f-index-testimonials-badge-text-26" type="text" name="testimonials_badge_text" value="{{ $val('testimonials_badge_text') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        @include('admin.landing.settings._decorated-title-field', [
                            'key' => 'testimonials_title',
                            'label' => 'testimonials_title',
                        ])
                    </div>
                    <div>
                        <label for="f-index-testimonials-sub-title-27" class="{{ $labelClass }}">testimonials_sub_title</label>
                        <textarea id="f-index-testimonials-sub-title-27" name="testimonials_sub_title" rows="2" class="{{ $inputClass }}">{{ $val('testimonials_sub_title') }}</textarea>
                    </div>
                </div>
            </div>

            <div x-show="tab === 'pricing'" x-cloak class="{{ $cardClass }} space-y-4">
                <h3 class="font-display text-base font-medium text-ink-900 dark:text-ink-50">Pricing</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        @include('admin.landing.settings._decorated-title-field', [
                            'key' => 'pricing_title',
                            'label' => 'pricing_title',
                        ])
                    </div>
                    <div class="sm:col-span-2">
                        <label for="f-index-pricing-sub-title-28" class="{{ $labelClass }}">pricing_sub_title</label>
                        <textarea id="f-index-pricing-sub-title-28" name="pricing_sub_title" rows="2" class="{{ $inputClass }}">{{ $val('pricing_sub_title') }}</textarea>
                    </div>
                    <div>
                        <label for="f-index-pricing-btn-text-29" class="{{ $labelClass }}">pricing_btn_text</label>
                        <input id="f-index-pricing-btn-text-29" type="text" name="pricing_btn_text" value="{{ $val('pricing_btn_text') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label for="f-index-pricing-btn-link-30" class="{{ $labelClass }}">pricing_btn_link</label>
                        <input id="f-index-pricing-btn-link-30" type="text" dir="ltr" name="pricing_btn_link" value="{{ $val('pricing_btn_link') }}" class="{{ $inputClass }}">
                    </div>
                </div>
            </div>

            <div x-show="tab === 'faq'" x-cloak class="{{ $cardClass }} space-y-4">
                <h3 class="font-display text-base font-medium text-ink-900 dark:text-ink-50">FAQ</h3>
                <div class="space-y-4">
                    <div>
                        @include('admin.landing.settings._decorated-title-field', [
                            'key' => 'faq_title',
                            'label' => 'faq_title',
                        ])
                    </div>
                    <div>
                        <label for="f-index-faq-sub-title-31" class="{{ $labelClass }}">faq_sub_title</label>
                        <textarea id="f-index-faq-sub-title-31" name="faq_sub_title" rows="2" class="{{ $inputClass }}">{{ $val('faq_sub_title') }}</textarea>
                    </div>
                </div>
            </div>

            <div x-show="tab === 'cta'" x-cloak class="{{ $cardClass }} space-y-4">
                <h3 class="font-display text-base font-medium text-ink-900 dark:text-ink-50">CTA</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="f-index-cta-title-32" class="{{ $labelClass }}">cta_title</label>
                        <input id="f-index-cta-title-32" type="text" name="cta_title" value="{{ $val('cta_title') }}" class="{{ $inputClass }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="f-index-cta-sub-title-33" class="{{ $labelClass }}">cta_sub_title</label>
                        <textarea id="f-index-cta-sub-title-33" name="cta_sub_title" rows="2" class="{{ $inputClass }}">{{ $val('cta_sub_title') }}</textarea>
                    </div>
                    <div>
                        <label for="f-index-cta-btn1-text-34" class="{{ $labelClass }}">cta_btn1_text</label>
                        <input id="f-index-cta-btn1-text-34" type="text" name="cta_btn1_text" value="{{ $val('cta_btn1_text') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label for="f-index-cta-btn1-link-35" class="{{ $labelClass }}">cta_btn1_link</label>
                        <input id="f-index-cta-btn1-link-35" type="text" dir="ltr" name="cta_btn1_link" value="{{ $val('cta_btn1_link') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label for="f-index-cta-btn2-text-36" class="{{ $labelClass }}">cta_btn2_text</label>
                        <input id="f-index-cta-btn2-text-36" type="text" name="cta_btn2_text" value="{{ $val('cta_btn2_text') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label for="f-index-cta-btn2-link-37" class="{{ $labelClass }}">cta_btn2_link</label>
                        <input id="f-index-cta-btn2-link-37" type="text" dir="ltr" name="cta_btn2_link" value="{{ $val('cta_btn2_link') }}" class="{{ $inputClass }}">
                    </div>
                </div>
            </div>

            <div x-show="tab === 'footer'" x-cloak class="{{ $cardClass }} space-y-6">
                <h3 class="font-display text-base font-medium text-ink-900 dark:text-ink-50">Footer & Social</h3>

                <div class="space-y-4 rounded-xl border border-mist-200 p-4 dark:border-ink-600">
                    <p class="text-xs font-semibold uppercase tracking-wide text-mist-500">Brand & About</p>
                    <p class="text-xs text-mist-500">الشعار يُدار من تبويب «الموقع» (<code class="text-brand-600">site_logo</code>).</p>
                    <div>
                        <label for="f-index-footer-description-38" class="{{ $labelClass }}">footer_description</label>
                        <textarea id="f-index-footer-description-38" name="footer_description" rows="3" class="{{ $inputClass }}">{{ $val('footer_description') }}</textarea>
                    </div>
                </div>

                <div class="space-y-4 rounded-xl border border-mist-200 p-4 dark:border-ink-600">
                    <p class="text-xs font-semibold uppercase tracking-wide text-mist-500">Newsletter</p>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="f-index-footer-newsletter-title-39" class="{{ $labelClass }}">footer_newsletter_title</label>
                            <input id="f-index-footer-newsletter-title-39" type="text" name="footer_newsletter_title" value="{{ $val('footer_newsletter_title') }}" class="{{ $inputClass }}">
                        </div>
                        <div>
                            <label for="f-index-footer-newsletter-btn-text-40" class="{{ $labelClass }}">footer_newsletter_btn_text</label>
                            <input id="f-index-footer-newsletter-btn-text-40" type="text" name="footer_newsletter_btn_text" value="{{ $val('footer_newsletter_btn_text') }}" class="{{ $inputClass }}">
                        </div>
                    </div>
                </div>

                <div class="space-y-4 rounded-xl border border-mist-200 p-4 dark:border-ink-600">
                    <p class="text-xs font-semibold uppercase text-mist-500">Column 1 — المنتج</p>
                    <div>
                        <label for="f-index-footer-title1-41" class="{{ $labelClass }}">footer_title1</label>
                        <input id="f-index-footer-title1-41" type="text" name="footer_title1" value="{{ $val('footer_title1') }}" class="{{ $inputClass }}">
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
                    <p class="text-xs font-semibold uppercase text-mist-500">Column 2 — الشركة</p>
                    <div>
                        <label for="f-index-footer-title2-42" class="{{ $labelClass }}">footer_title2</label>
                        <input id="f-index-footer-title2-42" type="text" name="footer_title2" value="{{ $val('footer_title2') }}" class="{{ $inputClass }}">
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
                    <p class="text-xs font-semibold uppercase text-mist-500">Column 3 — القانونية</p>
                    <div>
                        <label for="f-index-footer-title3-43" class="{{ $labelClass }}">footer_title3</label>
                        <input id="f-index-footer-title3-43" type="text" name="footer_title3" value="{{ $val('footer_title3') }}" class="{{ $inputClass }}">
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
                <h3 class="font-display text-base font-medium text-ink-900 dark:text-ink-50">Privacy Policy</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="f-index-privacy-badge-text-44" class="{{ $labelClass }}">privacy_badge_text</label>
                        <input id="f-index-privacy-badge-text-44" type="text" name="privacy_badge_text" value="{{ $val('privacy_badge_text') }}" class="{{ $inputClass }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="f-index-privacy-title-45" class="{{ $labelClass }}">privacy_title</label>
                        <input id="f-index-privacy-title-45" type="text" name="privacy_title" value="{{ $val('privacy_title') }}" class="{{ $inputClass }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="f-index-privacy-sub-title-46" class="{{ $labelClass }}">privacy_sub_title</label>
                        <input id="f-index-privacy-sub-title-46" type="text" name="privacy_sub_title" value="{{ $val('privacy_sub_title') }}" class="{{ $inputClass }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="f-index-privacy-description-47" class="{{ $labelClass }}">privacy_description</label>
                        <textarea id="f-index-privacy-description-47" name="privacy_description" rows="5" class="{{ $inputClass }}">{{ $val('privacy_description') }}</textarea>
                    </div>
                    <div>
                        <label for="f-index-privacy-btn-text-48" class="{{ $labelClass }}">privacy_btn_text</label>
                        <input id="f-index-privacy-btn-text-48" type="text" name="privacy_btn_text" value="{{ $val('privacy_btn_text') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label for="f-index-privacy-btn-link-49" class="{{ $labelClass }}">privacy_btn_link</label>
                        <input id="f-index-privacy-btn-link-49" type="text" dir="ltr" name="privacy_btn_link" value="{{ $val('privacy_btn_link') }}" class="{{ $inputClass }}">
                    </div>
                </div>
            </div>

            <div x-show="tab === 'terms'" x-cloak class="{{ $cardClass }} space-y-4">
                <h3 class="font-display text-base font-medium text-ink-900 dark:text-ink-50">Terms &amp; Conditions</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="f-index-terms-badge-text-50" class="{{ $labelClass }}">terms_badge_text</label>
                        <input id="f-index-terms-badge-text-50" type="text" name="terms_badge_text" value="{{ $val('terms_badge_text') }}" class="{{ $inputClass }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="f-index-terms-title-51" class="{{ $labelClass }}">terms_title</label>
                        <input id="f-index-terms-title-51" type="text" name="terms_title" value="{{ $val('terms_title') }}" class="{{ $inputClass }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="f-index-terms-sub-title-52" class="{{ $labelClass }}">terms_sub_title</label>
                        <input id="f-index-terms-sub-title-52" type="text" name="terms_sub_title" value="{{ $val('terms_sub_title') }}" class="{{ $inputClass }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="f-index-terms-description-53" class="{{ $labelClass }}">terms_description</label>
                        <textarea id="f-index-terms-description-53" name="terms_description" rows="5" class="{{ $inputClass }}">{{ $val('terms_description') }}</textarea>
                    </div>
                    <div>
                        <label for="f-index-terms-btn-text-54" class="{{ $labelClass }}">terms_btn_text</label>
                        <input id="f-index-terms-btn-text-54" type="text" name="terms_btn_text" value="{{ $val('terms_btn_text') }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label for="f-index-terms-btn-link-55" class="{{ $labelClass }}">terms_btn_link</label>
                        <input id="f-index-terms-btn-link-55" type="text" dir="ltr" name="terms_btn_link" value="{{ $val('terms_btn_link') }}" class="{{ $inputClass }}">
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3 border-t border-mist-200 pt-5 dark:border-ink-700">
                <a href="{{ route('admin.landing.settings.edit') }}" class="rounded-xl px-4 py-2 text-sm font-semibold text-mist-600 transition hover:bg-mist-100 dark:text-mist-300 dark:hover:bg-ink-800">إلغاء</a>
                <button type="submit" class="ui-btn ui-btn-primary">حفظ الإعدادات</button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

            document.querySelectorAll('.setting-image-delete').forEach(function (button) {
                button.addEventListener('click', function () {
                    const field = button.closest('.setting-image-field');
                    const preview = field?.querySelector('[data-preview-wrapper]');
                    const fileInput = field?.querySelector('[data-file-input]');
                    const deleteUrl = button.dataset.deleteUrl;

                    if (! field || ! deleteUrl) {
                        return;
                    }

                    Swal.fire({
                        title: 'هل أنت أثق من حذف الصورة؟',
                        text: 'سيتم حذف الملف نهائياً والعودة إلى الشعار/الأيقونة الافتراضية.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'نعم، احذف',
                        cancelButtonText: 'إلغاء',
                        confirmButtonColor: '#b42318',
                        cancelButtonColor: '#6c757d',
                        reverseButtons: true,
                    }).then(function (result) {
                        if (! result.isConfirmed) {
                            return;
                        }

                        fetch(deleteUrl, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                        })
                            .then(function (response) {
                                if (! response.ok) {
                                    throw new Error('Delete failed');
                                }

                                return response.json();
                            })
                            .then(function (data) {
                                if (! data.success) {
                                    throw new Error('Delete failed');
                                }

                                if (preview) {
                                    preview.classList.add('opacity-0');
                                    window.setTimeout(function () {
                                        preview.remove();
                                    }, 300);
                                }

                                if (fileInput) {
                                    fileInput.value = '';
                                }

                                Swal.fire({
                                    toast: true,
                                    position: 'top-end',
                                    icon: 'success',
                                    title: 'تم حذف الصورة بنجاح',
                                    showConfirmButton: false,
                                    timer: 3000,
                                    timerProgressBar: true,
                                });
                            })
                            .catch(function () {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'تعذّر حذف الصورة',
                                    text: 'حاول مرة أخرى.',
                                    confirmButtonColor: '#fe6019',
                                });
                            });
                    });
                });
            });
        });
    </script>
@endsection
