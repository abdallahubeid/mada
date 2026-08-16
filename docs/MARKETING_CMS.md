# Mada ERP — Marketing CMS Database Architecture

> Part of the Mada ERP documentation set. See `MARKETING.md` for the public site sitemap and section flow, `DATABASE_ROADMAP.md` for global schema conventions, `MODULES.md` BR-801–BR-803 / ADR-16 for `platform_settings`, and `ARCHITECTURE.md` for tenancy boundaries.
>
> **Status:** Binding design for the marketing backend cutover. **No migrations or application code in this document** — implement only after this schema is approved.
>
> **Scope:** How the static MVP marketing site (`config/plans.php`, `config/faq.php`, hardcoded Blade props) becomes database-backed without rewriting Blade section components.

---

## 1. Guiding Principles

1. **Hybrid storage.** CMS *copy* lives in `platform_settings` as grouped JSON. Ordered, curatable *collections* (FAQs, testimonials, plans) are first-class tables. Live *metrics* are runtime queries with cache — never persisted as settings.
2. **No tenancy scope on marketing content.** `faqs`, `testimonials` (except optional attribution), plan definitions, and `platform_settings` are platform-global. They must never be resolved through `TenantContext` / `TenantScope` (same rule as BR-801).
3. **Prop-driven Blade.** Section components (`<x-marketing.hero>`, etc.) accept arrays/DTOs. They do not query Eloquent or `config()` long-term. A single `MarketingContent` service is the bridge.
4. **Consent for “Trusted By”.** Tenant names/logos appear on the public site only when `show_on_marketing = true`. Fallback partner names live in settings.
5. **Honesty for testimonials.** Rows default unpublished; empty published set → hide section or show a metrics treatment (see `MARKETING.md` §4 honesty note).
6. **Non-breaking cutover.** `MarketingContent` may read config/hardcoded defaults until DB seeds exist; then DB-first with temporary fallback; then config removed.

---

## 2. New / Extended Tables

### 2.1 `faqs`

Platform-global FAQ items consumed by the landing accordion preview and `/faq`.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `category` | string | Group label (e.g. `عام`, `الأمان`). Arabic-first. |
| `question` | string | Display question. |
| `answer` | text | Display answer body. |
| `sort_order` | unsigned int | Ascending within and across categories (admin drag-sort). Default `0`. |
| `is_published` | boolean | Only `true` rows are public. Default `false` until reviewed. |
| `created_at` / `updated_at` | timestamps | Laravel convention. |

**Indexes:** `(is_published, sort_order)`, `(category, sort_order)`.

**Source today:** `config/faq.php` → seed into this table on cutover.

**No `tenant_id`.**

---

### 2.2 `testimonials`

Curated success stories for the landing Testimonials section.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `quote` | text | Testimonial body (Arabic-first). |
| `client_name` | string | Attribution name (maps from UI “name”). |
| `client_role` | string nullable | Job title (maps from UI “role”). |
| `organization_name` | string nullable | Company / org label (maps from UI “org”). |
| `logo_path` | string nullable | Optional org logo path (disk/media); null → initial avatar. |
| `sort_order` | unsigned int | Display order. Default `0`. |
| `is_published` | boolean | Public only when `true`. Default `false`. |
| `tenant_id` | FK nullable → `tenants.id` | Optional link for internal attribution / consent audit. **Not** used for TenantScope filtering of the public query — public listing is `whereNull` not applied; query is platform-wide `is_published`. Null for external / non-tenant quotes. |
| `created_at` / `updated_at` | timestamps | |

**Indexes:** `(is_published, sort_order)`.

**Soft delete:** optional later; not required for v1 CMS.

---

### 2.3 `plans` and `plan_features`

Already listed in `DATABASE_ROADMAP.md` §2.1. Marketing pricing and admin Plan editor share this source. Maps from `config/plans.php`.

#### `plans`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `name` | string | Locked tier names: `Startup`, `Growth`, `Enterprise`. |
| `slug` | string unique | e.g. `startup`, `growth`, `enterprise`. |
| `tagline` | string nullable | Short Arabic/EN marketing line. |
| `price_monthly` | decimal(10,2) nullable | Null = “contact sales” (Enterprise). |
| `price_yearly` | decimal(10,2) nullable | Per-month equivalent when billed annually (matches current config). Null if contact-sales. |
| `currency` | char(3) / string | Default `USD` (UI may still render `$`). |
| `cta_label` | string | Button text (`ابدأ الآن`, `تواصل مع المبيعات`). |
| `cta_url` | string | e.g. `/register`, `/contact`. |
| `is_highlighted` | boolean | “الأكثر طلباً” card. |
| `is_active` | boolean | Soft archive without delete; inactive plans hidden from public pricing. |
| `sort_order` | unsigned int | Card order. |
| `created_at` / `updated_at` | timestamps | |
| `deleted_at` | soft delete nullable | Align with admin “soft archive” safety. |

**Numeric feature limits** (employees, projects, etc.) used by the admin console / Phase 4 enforcement may live on `plans` as typed columns *or* as rows in `plan_features` with `type = limit`. Marketing checklist bullets are separate (below).

#### `plan_features`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `plan_id` | FK → `plans.id` | Cascade on delete. |
| `label` | string | Marketing bullet text (from config `features[]`). |
| `sort_order` | unsigned int | Bullet order on the card. |
| `feature_key` | string nullable | Optional machine key for limit enforcement (`max_users`, `module_hr`, …) — used by admin/Phase 4; null for display-only bullets. |
| `value` | string/json nullable | Limit value or module toggle payload when `feature_key` is set. |
| `created_at` / `updated_at` | timestamps | |

**Config → DB mapping (`config/plans.php`):**

| Config field | Column |
|---|---|
| `tiers[].name` | `plans.name` |
| `tiers[].tagline` | `plans.tagline` |
| `tiers[].monthly` | `plans.price_monthly` |
| `tiers[].yearly` | `plans.price_yearly` |
| `tiers[].cta` | `plans.cta_label` |
| `tiers[].href` | `plans.cta_url` |
| `tiers[].highlighted` | `plans.is_highlighted` |
| `tiers[].features[]` | `plan_features.label` (+ `sort_order`) |
| `currency` | `plans.currency` (or platform default) |

Public API shape for Blade remains today’s tier array via `PlanCatalog::publicTiers()`.

---

### 2.4 `tenants` modifications (marketing opt-in)

Additive columns on the existing `tenants` table (no new table):

| Column | Type | Notes |
|---|---|---|
| `show_on_marketing` | boolean | Default `false`. When `true` **and** tenant is `active`, eligible for the Trusted By logo ticker. |
| `marketing_logo_path` | string nullable | Public logo path for the ticker. If null while opted-in, fall back to tenant `name` text treatment or skip until logo uploaded. |

**Public query (conceptual):** active tenants where `show_on_marketing = true`, ordered by tenure / prominence rule (e.g. `created_at` asc, limit 7). If fewer than N results, pad with `marketing.partners_fallback` from settings.

**Privacy:** never auto-enable; Super Admin (or a future tenant Owner self-serve toggle) must opt in.

---

## 3. `platform_settings` — Marketing CMS JSON Groups

Operational keys (SMTP, payments, `auto_approve_tenants`, branding, legal) remain as already specified in `MODULES.md` / `DATABASE_ROADMAP.md`.

Marketing **section copy** is stored as **grouped keys** whose values are JSON objects matching Blade prop shapes. Recommended physical shape for `platform_settings`:

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | Singleton-friendly; or |
| `key` | string unique | e.g. `marketing.hero` |
| `value` | json | Section payload |
| `updated_at` / `updated_by` | | Audit (NFR-05 on sensitive/ops keys; marketing edits should also audit) |

Sensitive ops values use encrypted casts (ADR-16). Marketing JSON is **not** secret — store as plain JSON.

### 3.1 Key catalog and JSON schemas

#### `marketing.hero`

```json
{
  "eyebrow": "منصة SaaS متكاملة لإدارة المؤسسات",
  "title_line_1": "مستقبل إدارة",
  "title_accent": "المؤسسات",
  "title_line_2": "بذكاء وفخامة",
  "subtitle": "منصة Mada ERP الشاملة...",
  "primary_cta": { "label": "ابدأ التجربة المجانية", "url": "/register" },
  "secondary_cta": { "label": "احجز عرضًا توضيحيًا", "url": "/contact" },
  "metrics": {
    "mode": "hybrid",
    "items": [
      { "key": "active_users", "source": "live", "prefix": "+", "fallback": 8500, "label": "مستخدم نشط" },
      { "key": "uptime", "source": "cms", "prefix": "%", "value": 99.9, "decimals": 1, "label": "نسبة الجاهزية" },
      { "key": "active_tenants", "source": "live", "prefix": "+", "fallback": 1200, "label": "مؤسسة تثق بنا" }
    ]
  }
}
```

- `source: "live"` → `MarketingContent` resolves counts from models + cache; uses `fallback` if query fails or cache empty on first deploy.
- `source: "cms"` → static value from JSON (e.g. uptime SLA claim until real telemetry exists).

#### `marketing.partners_fallback`

```json
{
  "eyebrow": "موثوق من قبل مؤسسات رائدة",
  "names": ["TechNova", "Al-Manar", "Global Corp", "Saudi Vision", "Emirates Lux", "Nova Bank", "Riyadh Tech"]
}
```

Used when opted-in tenants &lt; ticker minimum.

#### `marketing.problems`

```json
{
  "eyebrow": "التحديات",
  "title": "هل تبدو هذه المشاكل مألوفة؟",
  "subtitle": "...",
  "items": [
    { "title": "...", "description": "...", "icon": "link-break" }
  ]
}
```

`icon` is a stable key mapped to SVG in the Blade component (do not store raw SVG in DB for v1).

#### `marketing.solution`

```json
{
  "eyebrow": "الحل",
  "title": "...",
  "subtitle": "...",
  "points": ["...", "..."],
  "modules_preview": [
    { "label": "الموارد البشرية", "progress": 90 }
  ],
  "cta": { "label": "اكتشف كل المميزات", "url": "/features" }
}
```

#### `marketing.features`

```json
{
  "title": "قوة تتناسب مع طموحاتك",
  "subtitle": "...",
  "cards": [
    { "title": "...", "description": "...", "icon": "shield" }
  ]
}
```

#### `marketing.modules`

```json
{
  "eyebrow": "الوحدات",
  "title": "...",
  "subtitle": "...",
  "items": [
    { "title": "...", "description": "...", "icon": "users" }
  ]
}
```

#### `marketing.showcase`

```json
{
  "eyebrow": "جولة في المنتج",
  "title": "...",
  "subtitle": "...",
  "tabs": [
    { "key": "dashboard", "label": "لوحة التحكم" },
    { "key": "projects", "label": "المشاريع" },
    { "key": "payroll", "label": "الرواتب" }
  ]
}
```

Tab *visual mocks* may remain presentational in Blade for v1; CMS owns labels/copy. Screenshot assets later via media library.

#### `marketing.ai`

```json
{
  "badge": "قريباً · خارطة الطريق",
  "title": "ذكاء اصطناعي يعمل لصالحك",
  "subtitle": "...",
  "capabilities": [
    { "title": "...", "description": "...", "status": "roadmap" }
  ]
}
```

`status` must remain roadmap-honest (never imply shipped).

#### `marketing.differentiators`

```json
{
  "eyebrow": "لماذا Mada",
  "title": "...",
  "subtitle": "...",
  "pillars": [
    { "title": "...", "description": "...", "icon": "language" }
  ]
}
```

#### `marketing.cta`

```json
{
  "title": "جاهز لتحويل مؤسستك؟",
  "subtitle": "...",
  "primary": { "label": "ابدأ التجربة المجانية", "url": "/register" },
  "secondary": { "label": "تواصل مع المبيعات", "url": "/contact" }
}
```

#### `marketing.footer`

```json
{
  "blurb": "نظام إدارة الموارد المؤسسي الذكي...",
  "copyright": "© {year} Mada ERP. جميع الحقوق محفوظة.",
  "social": [
    { "platform": "x", "label": "X", "url": "https://x.com/mada" },
    { "platform": "linkedin", "label": "LinkedIn", "url": "https://linkedin.com/company/mada" }
  ],
  "columns": [
    {
      "title": "المنتج",
      "links": [
        { "label": "المميزات", "url": "/features" },
        { "label": "الأسعار", "url": "/pricing" }
      ]
    }
  ]
}
```

Nav chrome (`<x-marketing.nav>`) may stay route-driven in code for MVP; optional later key `marketing.nav` if CMS editing of top links is required.

#### Legal (existing Settings concern)

Prefer dedicated keys already implied by admin Legal tab, e.g. `legal.privacy`, `legal.terms` as `{ "title", "body", "updated_at" }`, consumed by `/privacy` and `/terms` — not duplicated inside `marketing.*` groups.

---

## 4. Live Metrics

| Metric key | Source | Notes |
|---|---|---|
| `active_tenants` | `tenants` where status = `active` | Cache ~5–15 min. |
| `active_users` | `users` with non-null `tenant_id` on active tenants (define exact rule in implementation) | Cache similarly. |
| `uptime` | CMS value until real SLO telemetry exists | Do not invent live uptime. |
| Revenue-style showcase numbers | CMS or omit | Only bind to finance aggregates when a trusted public figure is approved. |

Cache keys example: `marketing.metrics.active_tenants`. Invalidate on tenant status transitions (optional) or rely on TTL.

---

## 5. `MarketingContent` Service (bridge to Blade)

### 5.1 Responsibility

`App\Domain\Marketing\MarketingContent` (name indicative) is the **only** public-site reader for landing/shared section data:

1. Load CMS JSON groups from `platform_settings` (fallback → seeded defaults / former hardcoded arrays).
2. Load published `faqs`, `testimonials`, public `plans`+`plan_features`.
3. Resolve logo ticker: opted-in tenants → pad with `marketing.partners_fallback`.
4. Resolve hybrid hero metrics (live + CMS).
5. Return plain arrays / DTOs matching current component props.
6. Apply `Cache::remember` for the assembled home payload and for metrics.

### 5.2 Controllers

- Replace `Route::view('/', 'marketing.home')` with `Marketing\HomeController` that injects `MarketingContent`.
- `PricingController` → `PlanCatalog` / same service for tiers.
- `FaqController` → published FAQs grouped by `category`.
- Features/Solutions/Security continue to reuse section props from the same service where applicable.

### 5.3 Blade contract (unchanged shapes)

```text
MarketingContent::home()
  → hero, partners, problems, solution, features, modules, showcase,
    ai, differentiators, testimonials, plans, faqs (limited), cta, footer
```

Components keep accepting props; composition in `marketing/home.blade.php` stays declarative.

### 5.4 Admin write path

Platform Settings Landing CMS tab (`/admin/settings`, route `settings`) and resource editors under `/admin/{problems,solutions,offerings,modules,ai-features,features,testimonials}` **write** settings keys and content tables. Saving flushes marketing cache where wired. Plan edits live under `/admin/plans`.

**Implemented detail:** see `docs/LANDING_CMS_IMPLEMENTATION.md` (tables, polymorphic images, view paths, flash conventions, `PAGINATE_PAGE`).

---

## 6. Implementation Steps (no code yet — ordered)

| Step | Work | Breaks public UI? |
|---|---|---|
| 1 | Document approved (this file). Update `DATABASE_ROADMAP.md` / `MARKETING.md` references. | No |
| 2 | Introduce `MarketingContent` reading **current** config + hardcoded defaults; switch `HomeController` to pass props. | No |
| 3 | Migrations: `faqs`, `testimonials`, `plans`/`plan_features`, tenant marketing columns, `platform_settings` if not yet created. | No |
| 4 | Seeders from `config/plans.php`, `config/faq.php`, and current section defaults → DB. | No |
| 5 | Point `MarketingContent` at DB with config fallback. | No |
| 6 | Wire admin Settings Landing CMS + FAQ/Testimonial CRUD + Plan model to real persistence. | No (admin only until save) |
| 7 | Enable live metrics + ticker opt-in. | No |
| 8 | Remove config fallbacks; delete or demote `config/plans.php` & `config/faq.php` to seed-only. | No if seeds green |
| 9 | Pest: public pages still `200`; content assertions against seeded DB; cache invalidation tests. | — |

---

## 7. Tenancy & Security Checklist

- [ ] Marketing tables / settings keys never use `BelongsToTenant` global scope.
- [ ] Ticker query only returns `show_on_marketing` + `active` tenants; no other tenant fields exposed.
- [ ] `testimonials.tenant_id` is attribution only; public query filters `is_published`, not “current tenant”.
- [ ] SMTP/payment secrets remain encrypted and out of marketing JSON (ADR-16).
- [ ] Marketing setting changes and plan/FAQ/testimonial publishes are audit-logged where they affect the public site (NFR-05).

---

## 8. Relationship to Existing Docs

| Doc | Relationship |
|---|---|
| `MARKETING.md` | Sitemap, section order, MVP pages — CMS feeds those sections. |
| `DATABASE_ROADMAP.md` | Adds `faqs`, `testimonials`, tenant marketing columns; details `plans`/`plan_features` and marketing keys on `platform_settings`. |
| `MODULES.md` | BR-801–803 govern settings access; Landing CMS is Super Admin–writable. |
| Admin `/admin/settings` Landing tab | UI contract for `marketing.*` JSON groups (already mocked). |
| Admin `/admin/plans` | UI contract for `plans` / `plan_features`. |
| `LANDING_CMS_IMPLEMENTATION.md` | **As-built** Landing CMS schema, admin CRUD, views, flash rules, env keys. |
