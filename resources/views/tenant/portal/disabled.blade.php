<!DOCTYPE html>
<html lang="ar" dir="rtl" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>الموقع غير متاح — {{ $company['name'] }}</title>
    <x-site-favicon />
    <x-theme-script />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full items-center justify-center bg-neutral-50 px-4 font-sans text-ink-700 antialiased dark:bg-ink-950 dark:text-mist-200">
    <div class="w-full max-w-lg rounded-2xl border border-mist-200 bg-white p-8 text-center shadow-sm dark:border-ink-600 dark:bg-ink-900">
        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-400 font-display text-lg font-bold text-emerald-950">{{ $company['logo_initial'] }}</span>
        <h1 class="mt-4 font-display text-2xl font-bold text-ink-900 dark:text-ink-50">الموقع العام غير متاح حالياً</h1>
        <p class="mt-2 text-sm text-mist-500 dark:text-mist-400">
            بوابة التوظيف لـ <span class="font-medium text-ink-700 dark:text-mist-200">{{ $company['name'] }}</span> موقوفة مؤقتاً. يرجى المحاولة لاحقاً.
        </p>
    </div>
</body>
</html>
