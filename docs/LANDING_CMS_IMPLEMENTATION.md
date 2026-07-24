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
| `Solution` / `solutions` | + optional `btn_text`, `btn_link` | `icon` |
| `Offering` / `offerings` | same as Problem | `icon` |
| `Module` / `modules` | same as Problem | `icon` |
| `AiFeature` / `ai_features` | same as Problem | `icon` |
| `Feature` / `features` | same as Problem | `icon` |
| `Testimonial` / `testimonials` | `quote`, `client_name`, `client_role`, `organization_name`, `rate`, `sort_order`, `is_published`, optional `tenant_id` | `avatar` (legacy `logo` cleaned on replace) |

Shared conventions on card tables: `sort_order` default `0`, `is_published` default `true`, `published()` local scope, `HasImages`.

### 1.3 Evolved existing columns

- `testimonials`: added `rate`; **dropped** `logo_path` (use `HasImages`).
- `tenants`: kept `show_on_marketing`; **dropped** `marketing_logo_path` (use `HasImages` / `logo`).

### 1.4 Polymorphic images

- `Image::imageable()` → `MorphTo`
- Parents use `App\Models\Concerns\HasImages` → `images()` MorphMany, `image($collection)` MorphOne
- Disk: `custom` (`config/filesystems.php`); uploads via `$file->store(..., 'custom')` then `images()->create([...])`
- Deleting an `Image` row also deletes the file from disk

### 1.5 Hero copy (settings-driven)

Hero Blade reads `$settings['hero_*']` keys (seeded optionally by `SettingSeeder`: `hero_badge_text`, `hero_title`, `hero_description`, `hero_btn1_text`, `hero_btn1_link`, `hero_btn2_text`, `hero_btn2_link`).

### 1.6 Settings keys — privacy, terms & social (2026-07-23)

Key/value rows on `settings` (registered via migration `register_privacy_terms_and_social_setting_keys`; **no seeder content**). Catalog: `Setting::landingKeys()`.

**Privacy (`privacy_*`)**

- `privacy_badge_text`, `privacy_title`, `privacy_sub_title`, `privacy_description`, `privacy_btn_text`, `privacy_btn_link`

**Terms (`terms_*`)**

- `terms_badge_text`, `terms_title`, `terms_sub_title`, `terms_description`, `terms_btn_text`, `terms_btn_link`

**Social (footer subsection — `social_*`)**

- `social_btn1_text` / `social_btn1_link` … through `social_btn5_text` / `social_btn5_link`

Admin UI: `resources/views/admin/landing/settings/index.blade.php` — Privacy & Terms tabs; Social Media block inside Footer tab. Controller: `App\Http\Controllers\Admin\SettingController` (`edit` / `update`). Successful save uses `flash()->info('Settings Updated successfully')`.

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
| Seeder | `database/seeders/SettingSeeder.php` |
| Docs (design) | `docs/MARKETING_CMS.md`, `docs/ADMIN_CMS_ANALYSIS.md` |
