# Landing CMS — Implementation Log

> Part of the Veyra ERP documentation set. Complements `MARKETING_CMS.md` (read model / cutover plan) and `ADMIN_CMS_ANALYSIS.md` (admin write path).  
> **Status:** Implemented (2026-07-22). Update this file when Landing CMS schema, admin CRUD, views, or conventions change.

---

## 1. Architecture decisions

### 1.1 Hybrid storage (settings + tables)

| Concern | Storage |
|---|---|
| Section chrome (badges, titles, subtitles, CTAs) | `settings` key/value (`Setting` model); shared to views via `AppServiceProvider` view composer as `$settings` |
| Repeatable section cards | Dedicated tables (not a single typed `landing_items` table) |
| Partner / client logos | **No `clients` table** — Approach A: `tenants.show_on_marketing` + polymorphic image (`collection = logo`) |
| Marketing images | Central `images` table + `HasImages` trait; **no** path columns on content tables |

### 1.2 Content tables (platform-global, not tenant-scoped)

| Model / table | Fields (core) | Image collection |
|---|---|---|
| `Problem` / `problems` | `title`, `description`, `icon_key`, `sort_order`, `is_published` | `icon` |
| `Solution` / `solutions` | `title`, `description`, `icon`, `sort_order`, `is_published` | `icon` (optional upload) |
| `Offering` / `offerings` | `title`, `description`, `icon`, `sort_order`, `is_published` | `icon` (optional upload) |
| `Module` / `modules` | `title`, `description`, `icon`, `sort_order`, `is_published` | `icon` (optional upload) |
| `AiFeature` / `ai_features` | `title`, `description`, `icon`, `sort_order`, `is_published` | `icon` (optional upload) |
| `Feature` / `features` | `title`, `description`, `icon`, `sort_order`, `is_published` | `icon` (optional upload) |
| `Testimonial` / `testimonials` | `quote`, `client_name`, `client_role`, `organization_name`, `rate`, `sort_order`, `is_published`, optional `tenant_id` | `avatar` (legacy `logo` cleaned on replace) |

Shared conventions on card tables: `sort_order` default `0`, `is_published` default `true`, `published()` local scope, `HasImages`.

### 1.3 Evolved existing columns

- `testimonials`: added `rate`; **dropped** `logo_path` (use `HasImages`).
- `tenants`: kept `show_on_marketing`; **dropped** `marketing_logo_path` (use `HasImages` / `logo`).

### 1.4 Polymorphic images

- `Image::imageable()` → `MorphTo`
- Parents use `App\Models\Concerns\HasImages` → `images()` MorphMany, `image($collection)` MorphOne
- Disk: `custom` (`config/filesystems.php`); uploads via `$file->store(..., 'custom')` then `images()->create([...])`
- **`custom` URL base:** `APP_URL` only (not `APP_URL/public`). Root is `public_path('')`, so `Storage::disk('custom')->url('user/avatar/x.jpg')` → `{APP_URL}/user/avatar/x.jpg`. A `/public` prefix 404s under `artisan serve` / docroots already pointed at `public/`.
- Deleting an `Image` row also deletes the file from disk

### 1.5 Hero copy (settings-driven)

Hero Blade reads `$settings['hero_*']` keys (seeded optionally by `SettingSeeder`: `hero_badge_text`, `hero_title`, `hero_description`, `hero_btn1_text`, `hero_btn1_link`, `hero_btn2_text`, `hero_btn2_link`).

### 1.5b Problems / Challenges section (settings + cards) — 2026-07-26

Public Blade: `resources/views/components/marketing/problems.blade.php`.

**Section chrome (`settings` keys, seeded by `SettingSeeder`):**

| Key | Seeded value |
|---|---|
| `problems_badge_text` | التحديات |
| `problems_title` | هل تبدو هذه المشاكل مألوفة؟ |
| `problems_sub_title` | معظم المؤسسات تُدار عبر أدوات متفرقة تخلق فوضى تشغيلية بدل أن تحلّها. |

Keys were renamed from singular `problem_*` / `problem_sup_title` → `problems_*` / `problems_sub_title` via migration `rename_problem_setting_keys_to_problems`. Catalog: `Setting::landingKeys()`. Admin tab labels match the new key names.

**Cards (`problems` table, seeded by `ProblemSeeder`):** four published rows with `icon_key` values `ph:link-bold`, `ph:clock-bold`, `ph:chart-bar-bold`, `ph:warning-bold`. Landing page loads them via `MarketingContent::problems()` (`Problem::published()`), passed into `<x-marketing.problems :problems="…" />`. Icons render with Iconify (`iconify-icon`) from `icon_key`.

### 1.5c Solutions section (settings + modules sidebar) — 2026-07-26

Public Blade: `resources/views/components/marketing/solution.blade.php`.

**Section chrome (`settings` keys, seeded by `SettingSeeder`):**

| Key | Seeded value |
|---|---|
| `solutions_badge_text` | الحل |
| `solutions_title` | منصّة واحدة تدير كل شيء بسلاسة |
| `solutions_sub_title` | يوحّد Veyra ERP كل عمليات مؤسستك في نظام واحد متكامل، فتختفي الفوضى ويحلّ محلّها الوضوح. |
| `solutions_btn_text` | اكتشف كل المميزات |
| `solutions_btn_link` | `#modules` |

Keys were renamed from singular `solution_*` / `solution_description` → `solutions_*` / `solutions_sub_title` via migration `rename_solution_setting_keys_to_solutions`. CTA keys (`solutions_btn_text`, `solutions_btn_link`) registered in the same migration.

**Bullet points (`solutions` table, seeded by `SolutionSeeder`):** four published rows with `title` (bullet copy) and `icon` (`ph:check-bold`). Loaded via `MarketingContent::solutions()` and passed into `<x-marketing.solution :solutions="…" />`. Migration `update_solutions_table_for_bullet_points` dropped `btn_text` / `btn_link` and replaced `icon_key` with nullable `icon`.

**Sidebar modules (`modules` table, seeded by `ModuleSeeder`):** first four published rows by `sort_order` via `MarketingContent::modules(4)` into `<x-marketing.solution :modules="…" />`. Full six-card grid uses `MarketingContent::modules()` — see §1.5e.

### 1.5d Offerings section (settings + cards) — 2026-07-26

Public Blade: `resources/views/components/marketing/feature-grid.blade.php` (landing `#features` section).

**Section chrome (`settings` keys, seeded by `SettingSeeder`):**

| Key | Seeded value |
|---|---|
| `offerings_title` | قوة تتناسب مع طموحاتك |
| `offerings_sub_title` | كل ما تحتاجه مؤسستك من أدوات إدارية وتشغيلية في نظام واحد متكامل. |

Key renamed from `offerings_sup_title` → `offerings_sub_title` via migration `update_offerings_table_and_settings_keys`.

**Cards (`offerings` table, seeded by `OfferingSeeder`):** four published rows with Phosphor `icon` keys (`ph:shield-check-bold`, `ph:users-three-bold`, `ph:kanban-bold`, `ph:credit-card-bold`). Loaded via `MarketingContent::offerings()` and passed into `<x-marketing.feature-grid :offerings="…" />`. Migration replaced `icon_key` with nullable `icon`.

### 1.5e Modules section (settings + cards) — 2026-07-26

Public Blade: `resources/views/components/marketing/module-grid.blade.php` (landing `#modules` section).

**Section chrome (`settings` keys, seeded by `SettingSeeder`):**

| Key | Seeded value |
|---|---|
| `modules_badge_text` | الوحدات |
| `modules_title` | وحدات متكاملة لكل احتياجات مؤسستك |
| `modules_sub_title` | كل وحدة مصممة لتعمل بتناغم مع البقية، فتنساب البيانات بينها دون جهد. |

Key renamed from `modules_sup_title` → `modules_sub_title` via migration `update_modules_table_and_settings_keys`.

**Cards (`modules` table, seeded by `ModuleSeeder`):** six published rows with Phosphor `icon` keys. Loaded via `MarketingContent::modules()` into `<x-marketing.module-grid :modules="…" />`. The Solutions sidebar reuses the same table via `MarketingContent::modules(4)` (first four by `sort_order`). Migration replaced `icon_key` with nullable `icon`.

### 1.5f Product previews section (settings + live stats) — 2026-07-26

Public Blade: `resources/views/components/marketing/showcase.blade.php`.

**Section chrome (`settings` keys, seeded by `SettingSeeder`):**

| Key | Seeded value |
|---|---|
| `product_previews_badge_text` | جولة في المنتج |
| `product_previews_title` | واجهة أنيقة تجعل العمل متعة |
| `product_previews_sub_title` | تصميم عصري يركّز على الوضوح والسرعة، بدعم كامل للعربية والوضعين الفاتح والداكن. |

Text keys renamed from `previews_title` / `previews_sup_title` via migration `rename_previews_setting_keys_to_product_previews`. Media uploads remain `previews_img` / `previews_video`.

**Dashboard stat strip:** `MarketingContent::productPreviewStats()` (cached `marketing.product_preview.stats`, busted via `MarketingCache::flush()`):

| Stat | Source | Fallback |
|---|---|---|
| المستأجرون | `Tenant::count()` | `1,284` |
| الموظفون | `User::count()` | `18,420` |
| الإيرادات | `config('marketing.product_preview.revenue_k')` (458K until invoices ship) | `458` + `K` suffix |
| الجاهزية | `config('marketing.uptime')` | `99.9%` |

Passed into `<x-marketing.showcase :stats="…" />`.

### 1.5g AI roadmap section (settings + cards) — 2026-07-26

Public Blade: `resources/views/components/marketing/ai-capabilities.blade.php`.

**Section chrome (`settings` keys, seeded by `SettingSeeder`):**

| Key | Seeded value |
|---|---|
| `ai_badge_text` | قريباً · خارطة الطريق |
| `ai_title` | ذكاء اصطناعي يعمل لصالحك |
| `ai_sub_title` | قدرات ذكية قيد التطوير ضمن خارطة طريق Veyra — نشاركك رؤيتنا القادمة بشفافية. |

Key renamed from `ai_sup_title` → `ai_sub_title` via migration `update_ai_features_table_and_settings_keys`.

**Cards (`ai_features` table, seeded by `AiFeatureSeeder`):** three published rows with `icon` `ph:sparkle-bold`. Loaded via `MarketingContent::aiFeatures()` into `<x-marketing.ai-capabilities :ai-features="…" />`. Each card keeps a static **قريباً** status badge in Blade (roadmap honesty).

### 1.5h Why Us section (settings + cards) — 2026-07-26

Public Blade: `resources/views/components/marketing/differentiators.blade.php`.

**Section chrome (`settings` keys, seeded by `SettingSeeder`):**

| Key | Seeded value |
|---|---|
| `why_us_badge_text` | لماذا Veyra |
| `why_us_title` | ما الذي يميّزنا عن غيرنا |
| `why_us_sub_title` | لم نبنِ مجرد أداة أخرى، بل منصّة تفهم طبيعة المؤسسات في منطقتنا. |

Keys renamed from `features_badge_text` / `features_title` / `features_sup_title` → `why_us_*` / `why_us_sub_title` via migration `update_features_table_and_settings_keys`.

**Cards (`features` table, seeded by `FeatureSeeder`):** four published rows with Phosphor `icon` keys (`ph:translate-bold`, `ph:shield-check-bold`, `ph:arrow-down-bold`, `ph:chat-dots-bold`). Loaded via `MarketingContent::whyUsFeatures()` into `<x-marketing.differentiators :features="…" />`. Migration replaced `icon_key` with nullable `icon`.

### 1.5i Testimonials section (settings + cards) — 2026-07-26

Public Blade: `resources/views/components/marketing/testimonials.blade.php`.

**Section chrome (`settings` keys, seeded by `SettingSeeder`):**

| Key | Seeded value |
|---|---|
| `testimonials_badge_text` | قصص نجاح |
| `testimonials_title` | مؤسسات تنمو مع Veyra |
| `testimonials_sub_title` | لا تأخذ كلامنا فقط — استمع لمن اختبروا الفرق بأنفسهم. |

Key renamed from `testimonials_sup_title` → `testimonials_sub_title` via migration `rename_testimonials_setting_keys_to_sub_title`.

**Cards (`testimonials` table, seeded by `TestimonialSeeder`):** three published rows with `rate` 5, `client_name`, `client_role`, `organization_name`, and `quote`. Loaded via `MarketingContent::testimonials()` (Eloquent collection with `images`) into `<x-marketing.testimonials :testimonials="…" />`. Avatar initials derive from `client_name`; optional uploaded avatar via `HasImages` (`collection = avatar`).

### 1.5j Pricing section (settings + plans) — 2026-07-26

Public Blade: `resources/views/components/marketing/pricing-table.blade.php`.

**Section chrome (`settings` keys, seeded by `SettingSeeder`):**

| Key | Seeded value |
|---|---|
| `pricing_title` | استثمار ذكي لنمو مستدام |
| `pricing_sub_title` | اختر الخطة التي تناسب حجم مؤسستك، وطوّرها متى شئت. |
| `pricing_btn_text` | قارن جميع المزايا بالتفصيل |
| `pricing_btn_link` | `/pricing` |

Key renamed from `pricing_sup_title` → `pricing_sub_title` via migration `rename_pricing_setting_keys_to_sub_title`. Landing compact mode renders the comparison CTA from `pricing_btn_*`.

**Plans (`plans` + `plan_features` tables, seeded by `PlanSeeder`):** three active tiers — **الأساسية** (`startup`, 49/39 USD), **النمو** (`growth`, 129/99 USD, highlighted), **Enterprise** (contact sales). Loaded via `MarketingContent::plans()` into `<x-marketing.pricing-table :plans="…" />`. Config fallback: `config/plans.php`.

### 1.5k FAQ section (settings + rows) — 2026-07-26

Public Blade: `resources/views/components/marketing/faq-accordion.blade.php`.

**Section chrome (`settings` keys, seeded by `SettingSeeder`):**

| Key | Seeded value |
|---|---|
| `faq_title` | الأسئلة الشائعة |
| `faq_sub_title` | إجابات سريعة عن أكثر ما يسأل عنه عملاؤنا. |

Key renamed from `faq_sup_title` → `faq_sub_title` via migration `rename_faq_and_cta_setting_keys_to_sub_title`.

**Rows (`faqs` table, seeded by `FaqSeeder`):** six published Q&A items. Loaded via `MarketingContent::faqs(6)` into `<x-marketing.faq-accordion :faqs="…" />`. Full `/faq` page groups all published rows by `category`.

### 1.5l Bottom CTA section (settings) — 2026-07-26

Public Blade: `resources/views/components/marketing/cta-band.blade.php`.

**Section chrome (`settings` keys, seeded by `SettingSeeder`):**

| Key | Seeded value |
|---|---|
| `cta_title` | جاهز لتحويل مؤسستك؟ |
| `cta_sub_title` | ابدأ تجربتك المجانية اليوم — دون بطاقة ائتمان، وبإعداد يستغرق دقائق. |
| `cta_btn1_text` | ابدأ التجربة المجانية |
| `cta_btn1_link` | `/register` |
| `cta_btn2_text` | تواصل مع المبيعات |
| `cta_btn2_link` | `/contact` |

Key renamed from `cta_sup_title` → `cta_sub_title` via migration `rename_faq_and_cta_setting_keys_to_sub_title`. Landing page uses `<x-marketing.cta-band />` (reads `$settings` directly). Other marketing pages may pass prop overrides for page-specific CTAs.

### 1.5m Site branding uploads (settings) — 2026-07-26

Admin tab **Site** (`resources/views/admin/landing/settings/index.blade.php`):

| Key | Upload | Public binding |
|---|---|---|
| `site_logo` | image (`jpeg`, `png`, `gif`, `webp`, `svg`) | `<x-marketing.nav />` — `<img>` when set; default **V** wordmark otherwise |
| `site_favicon` | `.ico`, `.png`, `.svg` | `components/layouts/marketing.blade.php` `<link rel="icon">`; fallback `public/favicon.svg` |

Paths stored on the `custom` disk under `uploads/settings/`; public URLs via `Setting::assetUrl()`. Favicon tag: shared `<x-site-favicon />` included in all app layouts (marketing, app, guest, auth-split, admin, 404). Site tab supports AJAX deletion for `site_logo` / `site_favicon` via `DELETE admin.landing.settings.image.destroy` with SweetAlert2 confirmation (removes file on `custom` disk, nulls setting value).

### 1.5n Footer & social (settings-driven) — 2026-07-26

Admin tab **Footer & Social** (`resources/views/admin/landing/settings/index.blade.php`). Public binding: `<x-marketing.footer />` (reads global `$settings`; logo via `site_logo` with nav wordmark fallback).

| Group | Keys | Seeded default (excerpt) |
|---|---|---|
| Brand & about | `footer_description` | نظام إدارة الموارد المؤسسي الذكي… |
| Newsletter CTA | `footer_newsletter_title`, `footer_newsletter_btn_text` | البريد الإلكتروني / اشتراك |
| Column 1 (المنتج) | `footer_title1`, `footer_btn1_text` / `footer_btn1_link` … `footer_btn4_*` | المميزات → `/features`, … |
| Column 2 (الشركة) | `footer_title2`, `footer_btn5_*` … `footer_btn7_*` | من نحن → `/about`, … |
| Column 3 (القانونية) | `footer_title3`, `footer_btn8_*`, `footer_btn9_*` | سياسة الخصوصية → `/privacy`, … |
| Social | `social_btn1_text` / `social_btn1_link` … `social_btn5_*` | X/Twitter, LinkedIn, Facebook, GitHub, YouTube URLs |

Migration `register_footer_newsletter_setting_keys` registers footer nav + newsletter keys; `SettingSeeder` seeds all footer/social copy. `MarketingContent::footer()` mirrors the same settings map for cached home payload. Social icons render only when `social_btnN_link` is non-empty. Copyright line remains from `config/marketing.php` (`© {year} Veyra ERP…`).

### 1.6 Settings keys — privacy, terms & social (2026-07-23)

Key/value rows on `settings` (registered via migration `register_privacy_terms_and_social_setting_keys`; **no seeder content**). Catalog: `Setting::landingKeys()`.

**Privacy (`privacy_*`)**

- `privacy_badge_text`, `privacy_title`, `privacy_sub_title`, `privacy_description`, `privacy_btn_text`, `privacy_btn_link`

**Terms (`terms_*`)**

- `terms_badge_text`, `terms_title`, `terms_sub_title`, `terms_description`, `terms_btn_text`, `terms_btn_link`

**Social (footer subsection — `social_*`)**

- `social_btn1_text` / `social_btn1_link` … through `social_btn5_text` / `social_btn5_link` (seeded by `SettingSeeder`; see §1.5n for footer nav/newsletter keys)

Admin UI: `resources/views/admin/landing/settings/index.blade.php` — Privacy & Terms tabs; **Footer & Social** tab (brand blurb, newsletter, three nav columns, social links). Controller: `App\Http\Controllers\Admin\SettingController` (`edit` / `update`). Successful save uses `flash()->info('تم تحديث الإعدادات بنجاح.')`.

---

## 2. Admin controllers, routes & views

### 2.1 Controllers

Namespace: `App\Http\Controllers\Admin\`

Explicit resource controllers (no shared CRUD trait):

- `ProblemController`, `SolutionController`, `OfferingController`, `ModuleController`, `AiFeatureController`, `FeatureController`, `TestimonialController`
- `SettingController` — landing key/value settings (`edit` / `update`)

Index pattern:

```php
Model::query()->with('images')->latest()->paginate(config('app.paginate_page'));
```

### 2.2 Routes

Under `Route::prefix('admin')->name('admin.')` (except settings — see below):

```php
Route::resource('problems', ...)->except(['show']);
Route::resource('solutions', ...)->except(['show']);
Route::resource('offerings', ...)->except(['show']);
Route::resource('modules', ...)->except(['show']);
Route::resource('ai-features', ...)->except(['show']);
Route::resource('features', ...)->except(['show']);
Route::resource('testimonials', ...)->except(['show']);
```

Landing settings (key/value CMS):

- `GET/PUT /admin/landing/settings` → named `admin.landing.settings.edit` / `admin.landing.settings.update` (`SettingController`)
- View: `admin.landing.settings.index`
- Sidebar: only under **محتوى الصفحة الرئيسية** dropdown (not duplicated under المنصّة)

### 2.3 View layout

```text
resources/views/admin/landing/{entity}/
  index.blade.php
  create.blade.php
  edit.blade.php
  _form.blade.php
```

Entities: `problems`, `solutions`, `offerings`, `modules`, `ai-features`, `features`, `testimonials`.

Controller view names: `admin.landing.{entity}.*`. Layout: `layouts.admin` (SweetAlert via `session('flasher')`).

### 2.4 Sidebar

Collapsible **محتوى الصفحة الرئيسية** under group **المحتوى**, linking all 7 resource indexes plus **إعدادات الصفحة الرئيسية** (`settings`).

---

## 3. Flash / alert standards

Helper: `flash()` → `App\Support\FlashNotifier` → SweetAlert2 in `layouts.admin`.

| Action | Method | SweetAlert type |
|---|---|---|
| Store / create | `flash()->success('...')` | success (green) |
| Update | `flash()->info('...')` | info (blue) |
| Destroy / delete | `flash()->warning('...')` | warning (orange) |
| Exceptions / failures | `flash()->error('...')` or `flash()->danger('...')` | error (red); `danger` aliases `error` |

---

## 4. Env / config keys

| Env key | Config | Default | Purpose |
|---|---|---|---|
| `PAGINATE_PAGE` | `config('app.paginate_page')` | `10` | Admin Landing CMS (and shared) pagination page size |

Also set in `.env`, `.env.example`, and `phpunit.xml` for tests.

---

## 5. Related files (quick index)

| Area | Location |
|---|---|
| Models | `app/Models/{Problem,Solution,Offering,Module,AiFeature,Feature,Testimonial,Image,Setting}.php` |
| Tenant marketing | `app/Domain/Tenancy/Models/Tenant.php` (`show_on_marketing`, `HasImages`) |
| Controllers | `app/Http/Controllers/Admin/*Controller.php` (`SettingController` for key/value CMS) |
| Views | `resources/views/admin/landing/**` (including `settings/index.blade.php`) |
| Seeders | `database/seeders/SettingSeeder.php`, `database/seeders/ProblemSeeder.php`, `database/seeders/SolutionSeeder.php`, `database/seeders/OfferingSeeder.php`, `database/seeders/ModuleSeeder.php`, `database/seeders/AiFeatureSeeder.php`, `database/seeders/FeatureSeeder.php`, `database/seeders/TestimonialSeeder.php`, `database/seeders/PlanSeeder.php`, `database/seeders/FaqSeeder.php` |
| Docs (design) | `docs/MARKETING_CMS.md`, `docs/ADMIN_CMS_ANALYSIS.md` |

### 1.5o User profile + polymorphic avatar — 2026-07-26

Authenticated Super Admin / operator self-service profile (persisted; no longer mocked).

| Piece | Detail |
|---|---|
| Model | `User` uses `HasImages` + `avatar(): MorphOne` (`collection = avatar`) + `avatar_url` accessor via `Storage::disk('custom')->url($path)` (uploaded URL) or initial SVG data URI |
| Columns | `users.phone`, `users.job_title` (nullable; migration `add_phone_and_job_title_to_users_table`) |
| Routes | `GET/PUT /admin/profile` (`admin.profile` / `admin.profile.update`) behind `auth` |
| Controller | `App\Http\Controllers\Admin\ProfileController` — personal fields, optional password (requires `current_password`), avatar replace deletes old file on `Storage::disk('custom')` then `store('user/avatar', 'custom')` with `images.disk = custom` |
| Form request | `App\Http\Requests\Admin\UpdateProfileRequest` |
| View | `resources/views/admin/profile/index.blade.php` — header card (clickable avatar lightbox, crop camera button, name, role badge, email) + tabs: المعلومات الشخصية / الأمان وكلمة المرور. Verified badge sits beside the email **label** (not overlaid on the input). Avatar UX: click → lightbox preview; camera / «إعادة ضبط / قص الصورة» → Cropper.js (1:1, zoom, rotate) → cropped JPEG blob into `input[name=avatar]` before PUT. CDN: Cropper.js 1.6.2 via `@push('styles'|'scripts')` on `layouts.admin`. Topbar (`layouts/partials/admin-topbar.blade.php` + app `components/layouts/partials/topbar.blade.php`) renders `auth()->user()->avatar_url` (`h-8 w-8 rounded-full object-cover border border-slate-700`) with SVG/initial fallback from the accessor. |
| Flash | `flash()->info('تم تحديث الإعدادات بنجاح.')` (create=`success`, update=`info`, delete=`warning`, failures=`error`) via `App\Support\FlashNotifier` |
| Routes | Platform console routes live in `routes/admin.php`, registered from `bootstrap/app.php` (`web` + `auth` + `platform.operator`, prefix/name `admin`) |
| Login | Platform operators (`canAccessPlatformConsole()` = `tenant_id` null + any platform team role, including custom) redirect via `preferredAdminHomeRoute()` |
| Custom roles | Created as global (`roles.tenant_id` null) via `withGlobalTeam()`; cache cleared with `forgetCachedPermissions()` after role/user mutations |
| Tests | `tests/Feature/UserProfileTest.php`; `PolymorphicImageRelationTest` includes `User` + `avatar` |

### 1.5p Support inbox + Contact Us threading — 2026-07-27

Platform-global `support_threads` / `support_messages` (ADR-17). Public Contact (`POST /contact`) uses `App\Services\Support\SupportInbox::ingestContactInquiry()`:

- Groups by email: if an **active** thread (`open` | `in_progress`) exists for that email → append message; else create a new `open` thread.
- Messages start as delivered (`delivered_at = now()`, `read_at = null`).
- Still sends `ContactInquiry` mail.

Admin `/admin/messages`: Eloquent inbox (status tabs including **مؤرشف**, search). Threads are **not** auto-selected — placeholder «اختر محادثة لبدء القراءة»; close via × or Esc. Opening a thread marks customer messages `read_at = now()` (double blue ✓✓). Reply `POST admin.messages.reply` (SweetAlert success). Archive `POST admin.messages.archive` / status `PUT` including `archived`. Soft delete `DELETE admin.messages.destroy` (AJAX + SweetAlert confirm «هل أنت تأكد من حذف هذه المحادثة؟», DOM remove without reload). Short-poll `GET admin.messages.poll` every ~7s refreshes the sidebar and appends new messages for the open thread (`SupportInboxPoller`). Relative timestamps refresh client-side every 60s. Avatars use `User::avatar_url` / initial SVG. Contact Us uses `flash()->success()` + SweetAlert on the marketing layout.

| Piece | Location |
|---|---|
| Models | `SupportThread`, `SupportMessage` |
| Service | `App\Services\Support\SupportInbox`, `SupportInboxPoller` |
| Controllers | `Marketing\ContactController`, `Admin\MessageController` |
| View | `resources/views/admin/messages/index.blade.php` + `_thread-list`, `_message-bubble`, `_receipt` |
| Poll | `GET admin.messages.poll` |
| Tests | `tests/Feature/Marketing/ContactFormTest.php`, `tests/Feature/AdminMessagesTest.php`, `tests/Feature/AdminMessagesPollTest.php` |

### 1.5q Newsletter subscribers + campaigns — 2026-07-27

`newsletter_subscribers` (soft deletes): `email` (unique), `status` (`subscribed`|`unsubscribed`), `subscribed_at`, `unsubscribed_at`.

Public:
- `POST /newsletter/subscribe` (`marketing.newsletter.subscribe`; legacy `POST /newsletter` alias) via `NewsletterService::subscribe()` — duplicate active → `flash()->info('أنت مشترك بالفعل…')`; new/reactivated → `flash()->success` + `WelcomeNewsletterMail` (includes unsubscribe link).
- `GET /newsletter/unsubscribe/{email}` updates status to `unsubscribed` and shows confirmation view.

Admin `/admin/newsletter` (sidebar dropdown تحت التواصل → المشتركين / الحملات البريدية): stats, filterable table with **#** index, column borders, LTR mono email, centered Arabic relative dates, centered status, end-aligned actions (`px-6 py-4`). Toggle / soft delete / CSV export / campaign modal with exclusion checkboxes. Short-poll `GET admin.newsletter.poll` (~7s + immediate kick) refreshes stats + rows. Campaign sends persist to `newsletter_campaigns` and appear on `/admin/newsletter/campaigns` with content modal. Mail branding: published `resources/views/vendor/mail` uses `site_logo` (or app name) in header and footer «تحياتنا، فريق عمل {app.name}».

| Piece | Location |
|---|---|
| Model | `NewsletterSubscriber`, `NewsletterCampaign` |
| Service | `App\Services\Newsletter\NewsletterService`, `NewsletterDashboardPoller` |
| Controllers | `Marketing\NewsletterController`, `Admin\NewsletterController`, `Admin\NewsletterCampaignController` |
| Mail | `WelcomeNewsletterMail`, `NewsletterCampaignMail` + branded `resources/views/vendor/mail` |
| Views | `admin/newsletter/index`, `admin/newsletter/campaigns`, `marketing/newsletter-unsubscribe` |
| Poll | `GET admin.newsletter.poll` |
| Tests | `tests/Feature/NewsletterTest.php` |

### 1.5r TopBar chrome badges + global search — 2026-07-27

Platform console TopBar (`layouts/partials/admin-topbar.blade.php`):

- **Messages icon** + **notifications bell** with red unread badges.
- **Chrome poll** `GET /admin/chrome/poll` (`admin.chrome.poll`) via `AdminChromeBadges` every ~7s (Alpine `veyraAdminChrome`).
  - `messages_unread` = distinct `SupportThread` rows with unread customer messages (`read_at` null).
  - `notifications_unread` = unread count from `PlatformNotifications` (shared with notifications page; still mocked until Phase 4 persistence).
- **Dual-mode global search** (`GlobalSearch` + `AdminNavigationCatalog`):
  1. **Navigation** — `صفحات النظام` group matches sidebar page titles/keywords (Arabic + English, diacritic-insensitive) and links to admin routes.
  2. **Entities** — tenants, support threads, newsletter subscribers, campaigns via case-insensitive `LOWER(column) LIKE %q%`.
  3. **Context** — TopBar sends `context` (e.g. `newsletter`, `messages`); matching entity group is ranked after navigation.
  4. **In-page highlight** — entity results include `anchor` / `highlight` query; on the active page Alpine `scrollIntoView` + `.veyra-search-flash`. Rows expose `id="veyra-search-…"` + `data-veyra-search`.
- Enter/submit → `/admin/search?q=…` results page with category tabs.

| Piece | Location |
|---|---|
| Services | `AdminChromeBadges`, `PlatformNotifications`, `GlobalSearch`, `AdminNavigationCatalog` |
| Controllers | `Admin\ChromeController`, `Admin\SearchController` |
| Views | `layouts/partials/admin-topbar`, `admin/search/index`, row anchors on newsletter/messages/campaigns |
| Routes | `admin.chrome.poll`, `admin.search`, `admin.search.suggest` |
| Tests | `tests/Feature/AdminChromeAndSearchTest.php` |

### 1.5s Platform notifications (DB + Reverb) — 2026-07-27

Dual-delivery Super Admin alerts (`docs/MODULES.md` BR-804):

- **Table** `platform_notifications`: `category` (`approval|security|job_failed|plan_limit|ops`), `title`, `body`, `target_url`, `read_at`.
- **Rule:** every alert is persisted first via `PlatformNotificationPublisher`.
- **Urgent (also Reverb):** tenant registration, tenant lifecycle helper, security alerts (failed-login streak, password change), `JobFailed` listener, new support message → `PlatformNotificationCreated` on `private-admin.notifications`.
- **Routine (DB only):** newsletter subscribe, campaign completed.
- **Console:** `NotificationController` reads DB; mark-all-read / destroy-all / toggle / destroy persist. TopBar badge via `AdminChromeBadges` + Echo toast when authenticated.
- **Stack:** `laravel/reverb`, `laravel-echo`, `pusher-js`, `BROADCAST_CONNECTION=reverb` (tests use `null`).

| Piece | Location |
|---|---|
| Model | `App\Models\PlatformNotification` |
| Services | `PlatformNotificationPublisher`, `PlatformNotifications` |
| Event / Listener | `PlatformNotificationCreated`, `RecordFailedJobNotification` |
| Channel | `routes/channels.php` → `admin.notifications` |
| Controllers | `Admin\NotificationController`, chrome poll |
| Frontend | `resources/js/echo.js`, TopBar `veyraAdminChrome.listenForRealtimeNotifications` |
| Tests | `tests/Feature/PlatformNotificationsTest.php` |

### 1.5t Soft Deletes (system-wide) — 2026-07-27

Mandatory SoftDeletes on all core Eloquent models/tables. Hard delete only via explicit `forceDelete()`.

| Area | Notes |
|---|---|
| Migration | `2026_07_27_141735_add_soft_deletes_to_core_tables` adds `deleted_at` to users, CMS cards, faqs, testimonials, plan_features, images, settings, audit logs, support_messages, newsletter_campaigns, platform_notifications |
| Already covered | `tenants`, `plans`, `newsletter_subscribers`, `support_threads` |
| Media | `Image` removes disk files only on `forceDeleting`; icon/avatar replace paths use `forceDelete()` |
| Plan features sync | `PlanController::syncFeatures` force-deletes prior rows (including trashed) before recreate |
| Settings | Unique on `settings.key` dropped → non-unique index so soft-deleted keys do not block upsert |
| AI rule | `.cursor/rules/soft-deletes.mdc` (alwaysApply) |
| Architecture | `docs/ARCHITECTURE.md` §6 Deletion row |
| Tests | `tests/Feature/SoftDeletesConventionTest.php` |

### 1.5t-ii Trash / Recycle Bin UI — 2026-07-29

| Area | Notes |
|---|---|
| Catalog | `App\Domain\Platform\TrashableResourceCatalog` — problems, solutions, offerings, modules, ai-features, features, testimonials, faqs, plans, admins, support-threads, newsletter-subscribers, notifications |
| Service | `App\Services\Admin\TrashManager` — list `onlyTrashed()`, restore (+ related images / plan reactivation), forceDestroy, bulk, empty |
| Routes | `admin/trash` (`admin.trash.*`) — index, restore, force-destroy, restore-selected, force-selected, empty |
| Permissions | `trash.view_any`, `trash.restore`, `trash.force_delete` (super_admin/admin full; content/support/billing get view+restore) |
| UX | Delete forms use `data-swal-confirm` → SweetAlert2; soft-delete flash includes Undo → `POST admin.trash.restore`; trash page has checkboxes + bulk restore/force + empty |
| Exclusions | Spatie `Role` remains hard-delete (not in recycle bin) |
| Tests | `tests/Feature/TrashManagementTest.php` |

### 1.5u Public navbar order + admin logo branding — 2026-07-28

| Area | Notes |
|---|---|
| Public nav | Locked order: Home → About → Modules (`/#modules`) → Features → Pricing → Contact |
| Dashboard link | `لوحة التحكم` only when `User::canAccessPlatformConsole()` (`tenant_id = null` or admin Spatie roles) |
| Admin sidebar brand | `site_logo` from settings with lettermark fallback; wraps `route('landing')` |
| Tests | `tests/Feature/MarketingNavbarAndAdminBrandingTest.php` |
| Docs | `docs/MARKETING.md` §2.3 |

### 1.5w Platform Roles & Permissions (Spatie) — 2026-07-29

| Area | Notes |
|---|---|
| Catalog | `App\Domain\Platform\PlatformPermissionCatalog` — granular perms by domain (`tenants`, `plans`, `cms`, `faqs`, `settings`, `support`, `notifications`, `newsletters`, `audit_logs`, `admins`, `roles`, `account`) |
| Seeders | `PlatformRolesAndPermissionsSeeder` then `UserSeeder` (single owner `owner@veyra.com` / `super_admin`); roles global (`roles.tenant_id` null), assignments use platform team sentinel `0` |
| Gate | `Gate::before` → `User::isPlatformSuperAdmin()` bypass |
| Middleware | `platform.operator` + Spatie `permission:` on every `/admin/*` route; binds Spatie team to `PlatformPermissionCatalog::TEAM_ID` (0) |
| Console UI | Roles: create/delete + users_count + `#`; Admins: `#` + avatar column (upload thumbnail or initial badge) + soft delete; form Alpine preselects role permissions into toggles |
| Sidebar | «مديرو المنصّة» dropdown → Users/Admins + Roles & Permissions; «سلة المحذوفات» under المنصّة (`trash.view_any`) |
| Auto-verify | `User::creating` sets `email_verified_at` when attribute omitted; SaaS registration passes `null` explicitly to keep email verification |
| Error pages | Custom `errors/403.blade.php` (brand-matched to 404); platform operators CTA → `preferredAdminHomeRoute()`, others → dashboard/landing |
| Tests | `tests/Feature/AdminAuthorizationTest.php`; `tests/Feature/ForbiddenPageTest.php`; `tests/Feature/TrashManagementTest.php`; Pest helpers `seedPlatformPermissions()` / `actingAsPlatformOperator()` |

