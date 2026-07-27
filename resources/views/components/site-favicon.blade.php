<link rel="icon" href="{{ ! empty($settings['site_favicon'] ?? null) ? \App\Models\Setting::assetUrl($settings['site_favicon']) : asset('favicon.svg') }}">
