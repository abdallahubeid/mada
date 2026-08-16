@extends('layouts.admin')

@section('title', 'الملف الشخصي')

@section('breadcrumbs')
    <span class="text-mist-500 dark:text-mist-400">الحساب والوصول</span>
    <span class="mx-1.5 text-mist-300 dark:text-mist-600">/</span>
    <span class="text-ink-700 dark:text-mist-200">الملف الشخصي</span>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">
@endpush

@section('content')
    @php
        $inputClass =
            'w-full rounded-xl border border-mist-200 bg-white px-3 py-2 text-sm text-ink-700 placeholder:text-mist-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50';
        $labelClass = 'mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200';
        $cardClass =
            'rounded-2xl border border-mist-200 bg-white p-4 shadow-sm sm:p-6 dark:border-ink-600 dark:bg-ink-800';
        $avatarUrl = $user->avatar_url;
        $hasUploadedAvatar = $user->avatar !== null;
    @endphp

    <div
        x-data="madaProfileAvatar({
            initialTab: @js(old('password') || old('current_password') ? 'security' : 'personal'),
            avatarUrl: @js($avatarUrl),
            hasUploadedAvatar: @js($hasUploadedAvatar),
        })"
        class="mx-auto max-w-4xl"
    >
        <div>
            <h2 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">الملف الشخصي</h2>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">إدارة معلوماتك الشخصية وصورة الحساب وكلمة المرور.</p>
        </div>

        {{-- Header card --}}
        <div class="{{ $cardClass }} mt-6">
            <div class="flex flex-col items-start gap-5 sm:flex-row sm:items-center">
                <div class="relative h-20 w-20 shrink-0">
                    <button
                        type="button"
                        @click="openLightbox()"
                        class="group h-20 w-20 overflow-hidden rounded-md border border-mist-200 bg-brand-500/15 transition ring-offset-2 hover:ring-2 hover:ring-brand-500/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-ink-600 dark:ring-offset-ink-800"
                        title="عرض الصورة"
                        aria-label="عرض الصورة الشخصية"
                    >
                        <img
                            :src="displayAvatar"
                            alt="{{ $user->name }}"
                            class="h-full w-full object-cover transition duration-200 group-hover:scale-105"
                        >
                    </button>
                    <button
                        type="button"
                        @click="pickAvatar()"
                        class="absolute -bottom-1 -end-1 flex h-8 w-8 cursor-pointer items-center justify-center rounded-md border-2 border-white bg-brand-500 text-white shadow-md transition duration-200 hover:bg-brand-600 active:scale-90 dark:border-ink-800"
                        title="تغيير الصورة"
                        aria-label="تغيير الصورة"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z" />
                        </svg>
                    </button>
                </div>

                <div class="min-w-0 flex-1">
                    <h3 class="font-display text-xl font-medium text-ink-900 dark:text-ink-50">{{ $user->name }}</h3>
                    <p class="mt-0.5 text-sm text-mist-500 dark:text-mist-400" dir="ltr">{{ $user->email }}</p>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-lg border border-brand-500/20 bg-brand-500/10 px-3 py-1 text-xs font-medium text-brand-600 dark:text-brand-300">
                            {{ $user->profileRoleLabel() }}
                        </span>
                        @if ($user->job_title)
                            <span class="inline-flex items-center rounded-lg border border-mist-200 bg-mist-50 px-3 py-1 text-xs font-medium text-mist-600 dark:border-ink-600 dark:bg-ink-900 dark:text-mist-300">
                                {{ $user->job_title }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="mt-6 flex items-center gap-1 border-b border-mist-200 dark:border-ink-700">
            <button
                type="button"
                @click="tab = 'personal'"
                :class="tab === 'personal'
                    ? 'border-brand-500 text-brand-600 dark:text-brand-300'
                    : 'border-transparent text-mist-500 hover:text-ink-700 dark:text-mist-400 dark:hover:text-mist-200'"
                class="border-b-2 px-3 py-2 text-sm font-semibold transition"
            >
                المعلومات الشخصية
            </button>
            <button
                type="button"
                @click="tab = 'security'"
                :class="tab === 'security'
                    ? 'border-brand-500 text-brand-600 dark:text-brand-300'
                    : 'border-transparent text-mist-500 hover:text-ink-700 dark:text-mist-400 dark:hover:text-mist-200'"
                class="border-b-2 px-3 py-2 text-sm font-semibold transition"
            >
                الأمان وكلمة المرور
            </button>
        </div>

        <form
            method="POST"
            action="{{ route('admin.profile.update') }}"
            enctype="multipart/form-data"
            class="mt-6"
            @submit="prepareSubmit()"
        >
            @csrf
            @method('PUT')

            <input
                x-ref="avatarInput"
                id="avatar-upload-header"
                type="file"
                name="avatar"
                accept="image/jpeg,image/png,image/gif,image/webp"
                class="sr-only"
                @change="onFileSelected($event)"
            >

            <div x-show="tab === 'personal'" x-cloak class="{{ $cardClass }} space-y-5">
                <div>
                    <h3 class="font-display text-base font-medium text-ink-900 dark:text-ink-50">المعلومات الشخصية</h3>
                    <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">حدّث بياناتك الأساسية وصورة الملف الشخصي.</p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="name" class="{{ $labelClass }}">الاسم الكامل</label>
                        <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required class="{{ $inputClass }} @error('name') border-danger-solid @enderror">
                        @error('name')
                            <p class="mt-1 text-xs text-danger-solid">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <div class="mb-1.5 flex flex-wrap items-center gap-2">
                            <label for="email" class="text-sm font-medium text-ink-700 dark:text-mist-200">البريد الإلكتروني</label>
                            @if ($user->hasVerifiedEmail())
                                <span class="inline-flex items-center gap-1 rounded-md bg-brand-500/10 px-2 py-0.5 text-xs font-medium text-brand-600 dark:text-brand-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    متحقق منه
                                </span>
                            @endif
                        </div>
                        <input id="email" type="email" dir="ltr" name="email" value="{{ old('email', $user->email) }}" required class="{{ $inputClass }} @error('email') border-danger-solid @enderror">
                        @error('email')
                            <p class="mt-1 text-xs text-danger-solid">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="phone" class="{{ $labelClass }}">رقم الهاتف</label>
                        <input id="phone" type="tel" dir="ltr" name="phone" value="{{ old('phone', $user->phone) }}" class="{{ $inputClass }} @error('phone') border-danger-solid @enderror">
                        @error('phone')
                            <p class="mt-1 text-xs text-danger-solid">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="job_title" class="{{ $labelClass }}">المسمى الوظيفي</label>
                        <input id="job_title" type="text" name="job_title" value="{{ old('job_title', $user->job_title) }}" class="{{ $inputClass }} @error('job_title') border-danger-solid @enderror">
                        @error('job_title')
                            <p class="mt-1 text-xs text-danger-solid">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <p class="text-xs text-mist-400">JPG أو PNG أو WebP بحد أقصى 2MB. انقر على الصورة للمعاينة، أو استخدم زر الكاميرا لاختيار وقص صورة جديدة.</p>
                @error('avatar')
                    <p class="text-xs text-danger-solid">{{ $message }}</p>
                @enderror
            </div>

            <div x-show="tab === 'security'" x-cloak class="{{ $cardClass }} space-y-5">
                <div>
                    <h3 class="font-display text-base font-medium text-ink-900 dark:text-ink-50">الأمان وكلمة المرور</h3>
                    <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">اترك حقول كلمة المرور فارغة إن لم ترغب بتغييرها.</p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="current_password" class="{{ $labelClass }}">كلمة المرور الحالية</label>
                        <input id="current_password" type="password" name="current_password" autocomplete="current-password" class="{{ $inputClass }} @error('current_password') border-danger-solid @enderror">
                        @error('current_password')
                            <p class="mt-1 text-xs text-danger-solid">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="password" class="{{ $labelClass }}">كلمة المرور الجديدة</label>
                        <input id="password" type="password" name="password" autocomplete="new-password" class="{{ $inputClass }} @error('password') border-danger-solid @enderror">
                        @error('password')
                            <p class="mt-1 text-xs text-danger-solid">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="{{ $labelClass }}">تأكيد كلمة المرور</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" class="{{ $inputClass }}">
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3 border-t border-mist-200 pt-5 dark:border-ink-700">
                <a href="{{ route('admin.profile') }}" class="rounded-xl px-4 py-2 text-sm font-semibold text-mist-600 transition hover:bg-mist-100 dark:text-mist-300 dark:hover:bg-ink-800">إلغاء</a>
                <button type="submit" class="rounded-xl bg-brand-500 px-5 py-2 text-sm font-semibold text-white shadow-glow transition hover:bg-brand-600 active:scale-95">
                    حفظ التغييرات
                </button>
            </div>
        </form>

        {{-- Lightbox --}}
        <div
            x-show="lightboxOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            aria-label="معاينة الصورة الشخصية"
            @keydown.escape.window="closeLightbox()"
        >
            <div class="absolute inset-0 bg-ink-950/80 backdrop-blur-sm" @click="closeLightbox()"></div>
            <div
                x-show="lightboxOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative z-10 w-full max-w-lg overflow-hidden rounded-2xl border border-ink-700 bg-ink-900 shadow-2xl"
            >
                <div class="flex items-center justify-between border-b border-ink-700 px-4 py-3">
                    <p class="text-sm font-semibold text-white">معاينة الصورة</p>
                    <button type="button" @click="closeLightbox()" class="rounded-lg p-1.5 text-mist-400 transition hover:bg-ink-800 hover:text-white" aria-label="إغلاق">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="flex items-center justify-center bg-ink-950 p-6">
                    <img :src="displayAvatar" alt="{{ $user->name }}" class="max-h-[60vh] w-auto max-w-full rounded-xl object-contain shadow-lg">
                </div>
                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-ink-700 px-4 py-3">
                    <button
                        type="button"
                        @click="startCropFromCurrent()"
                        class="inline-flex items-center gap-2 rounded-xl border border-ink-600 px-4 py-2 text-sm font-semibold text-mist-200 transition hover:border-brand-500 hover:text-brand-300"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 3.75H6A2.25 2.25 0 0 0 3.75 6v1.5M16.5 3.75H18A2.25 2.25 0 0 1 20.25 6v1.5m0 9V18A2.25 2.25 0 0 1 18 20.25h-1.5m-9 0H6A2.25 2.25 0 0 1 3.75 18v-1.5M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        إعادة ضبط / قص الصورة
                    </button>
                    <button type="button" @click="pickAvatar()" class="rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-600">
                        اختيار صورة جديدة
                    </button>
                </div>
            </div>
        </div>

        {{-- Cropper modal --}}
        <div
            x-show="cropperOpen"
            x-cloak
            class="fixed inset-0 z-[60] flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            aria-label="قص الصورة الشخصية"
            @keydown.escape.window="cancelCrop()"
        >
            <div class="absolute inset-0 bg-ink-950/85 backdrop-blur-sm" @click="cancelCrop()"></div>
            <div class="relative z-10 flex w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-ink-700 bg-ink-900 shadow-2xl">
                <div class="flex items-center justify-between border-b border-ink-700 px-4 py-3">
                    <div>
                        <p class="text-sm font-semibold text-white">إعادة ضبط / قص الصورة</p>
                        <p class="text-xs text-mist-400">نسبة 1:1 — يمكنك التكبير والتدوير قبل الحفظ</p>
                    </div>
                    <button type="button" @click="cancelCrop()" class="rounded-lg p-1.5 text-mist-400 transition hover:bg-ink-800 hover:text-white" aria-label="إغلاق">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="bg-ink-950 p-4">
                    <div class="mx-auto max-h-[55vh] overflow-hidden rounded-xl bg-ink-900">
                        <img x-ref="cropImage" :src="cropSource" alt="قص الصورة" class="block max-w-full">
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-ink-700 px-4 py-3">
                    <div class="flex items-center gap-2">
                        <button type="button" @click="zoom(-0.1)" class="rounded-lg border border-ink-600 px-3 py-1.5 text-xs font-semibold text-mist-200 hover:border-brand-500 hover:text-brand-300" title="تصغير">−</button>
                        <button type="button" @click="zoom(0.1)" class="rounded-lg border border-ink-600 px-3 py-1.5 text-xs font-semibold text-mist-200 hover:border-brand-500 hover:text-brand-300" title="تكبير">+</button>
                        <button type="button" @click="rotate(-90)" class="inline-flex items-center justify-center rounded-md border border-ink-600 px-3 py-1.5 text-mist-200 transition hover:border-brand-500 hover:text-brand-300" title="تدوير يسار" aria-label="تدوير يسار">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                            </svg>
                        </button>
                        <button type="button" @click="rotate(90)" class="inline-flex items-center justify-center rounded-md border border-ink-600 px-3 py-1.5 text-mist-200 transition hover:border-brand-500 hover:text-brand-300" title="تدوير يمين" aria-label="تدوير يمين">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m15 15 6-6m0 0-6-6m6 6H9a6 6 0 0 0 0 12h3" />
                            </svg>
                        </button>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="cancelCrop()" class="rounded-xl px-4 py-2 text-sm font-semibold text-mist-400 transition hover:text-white">إلغاء</button>
                        <button type="button" @click="applyCrop()" class="rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-600">
                            تطبيق القص
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('madaProfileAvatar', (config) => ({
                tab: config.initialTab || 'personal',
                avatarUrl: config.avatarUrl,
                avatarPreview: null,
                hasUploadedAvatar: config.hasUploadedAvatar,
                lightboxOpen: false,
                cropperOpen: false,
                cropSource: null,
                cropper: null,
                pendingObjectUrl: null,

                get displayAvatar() {
                    return this.avatarPreview || this.avatarUrl;
                },

                openLightbox() {
                    this.lightboxOpen = true;
                },

                closeLightbox() {
                    this.lightboxOpen = false;
                },

                pickAvatar() {
                    this.closeLightbox();
                    this.$refs.avatarInput?.click();
                },

                onFileSelected(event) {
                    const file = event.target.files?.[0];
                    if (! file) {
                        return;
                    }

                    if (this.pendingObjectUrl) {
                        URL.revokeObjectURL(this.pendingObjectUrl);
                    }

                    this.pendingObjectUrl = URL.createObjectURL(file);
                    this.openCropper(this.pendingObjectUrl);
                },

                startCropFromCurrent() {
                    this.closeLightbox();
                    this.openCropper(this.displayAvatar);
                },

                openCropper(src) {
                    this.cropSource = src;
                    this.cropperOpen = true;
                    this.$nextTick(() => {
                        this.initCropper();
                    });
                },

                initCropper() {
                    if (typeof Cropper === 'undefined') {
                        return;
                    }

                    if (this.cropper) {
                        this.cropper.destroy();
                        this.cropper = null;
                    }

                    const image = this.$refs.cropImage;
                    if (! image) {
                        return;
                    }

                    this.cropper = new Cropper(image, {
                        aspectRatio: 1,
                        viewMode: 1,
                        autoCropArea: 1,
                        responsive: true,
                        background: false,
                        movable: true,
                        zoomable: true,
                        rotatable: true,
                    });
                },

                zoom(delta) {
                    this.cropper?.zoom(delta);
                },

                rotate(degrees) {
                    this.cropper?.rotate(degrees);
                },

                cancelCrop() {
                    if (this.cropper) {
                        this.cropper.destroy();
                        this.cropper = null;
                    }

                    this.cropperOpen = false;
                    this.cropSource = null;

                    if (! this.avatarPreview && this.$refs.avatarInput) {
                        this.$refs.avatarInput.value = '';
                    }
                },

                applyCrop() {
                    if (! this.cropper) {
                        return;
                    }

                    const canvas = this.cropper.getCroppedCanvas({
                        width: 512,
                        height: 512,
                        imageSmoothingQuality: 'high',
                    });

                    if (! canvas) {
                        return;
                    }

                    canvas.toBlob((blob) => {
                        if (! blob) {
                            return;
                        }

                        const file = new File([blob], 'avatar.jpg', { type: 'image/jpeg' });
                        const transfer = new DataTransfer();
                        transfer.items.add(file);

                        if (this.$refs.avatarInput) {
                            this.$refs.avatarInput.files = transfer.files;
                        }

                        if (this.avatarPreview) {
                            URL.revokeObjectURL(this.avatarPreview);
                        }

                        this.avatarPreview = URL.createObjectURL(blob);
                        this.hasUploadedAvatar = true;
                        this.tab = 'personal';

                        if (this.cropper) {
                            this.cropper.destroy();
                            this.cropper = null;
                        }

                        this.cropperOpen = false;
                        this.cropSource = null;
                    }, 'image/jpeg', 0.92);
                },

                prepareSubmit() {
                    // Ensure cropped File remains attached to the named input before submit.
                },
            }));
        });
    </script>
@endpush
