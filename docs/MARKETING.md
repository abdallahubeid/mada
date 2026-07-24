# Veyra ERP — Public Marketing Site

> Part of the Veyra ERP documentation set. See `VEYRA_DOCS.md` for the full Software Design Document, `DESIGN_SYSTEM.md` for tokens/components, and `ARCHITECTURE.md` (ADR-10 RTL, ADR-15 theming) for foundations referenced below. Backend/CMS persistence for this site is specified in `MARKETING_CMS.md` (no migrations until that schema is implemented).
>
> **Scope:** the public, unauthenticated marketing website (Arabic-first, `ar`/`rtl`). It is distinct from the authenticated tenant app (`/app/*`) and the Super Admin console (`/admin/*`). It reuses `resources/views/components/layouts/marketing.blade.php`.

## 1. Guiding Principles

- **Arabic-first, RTL-native.** All copy is Arabic; every layout uses logical properties (`ps-*`, `pe-*`, `start-*`, `end-*`) per ADR-10 so a future locale switcher works without rework.
- **Dark-elevated theme parity.** Same tokens as the product: `ink-*` surfaces, `mist-*` text, `emerald` (`#4EDEA3`) accent, `shadow-glow`, `font-display` (Cairo) for headings, `font-sans` (Tajawal) for body. Light mode supported via `dark:` pass and the pre-paint theme script (ADR-15).
- **Honesty over hype.** Consistent with the product's "phase honesty" discipline: unreleased capabilities are labeled roadmap, never presented as shipped (see §4 AI section).
- **Single source of truth.** Shared content (plans, FAQ) lives in one data source consumed by both the landing previews and the dedicated pages (§5) — no duplicated arrays.
- **Extend, don't restart.** A landing page already exists (`resources/views/landing.blade.php`, served by `Route::view('/', 'landing')`). This effort refactors it into reusable section components and adds the remaining pages.

## 2. Sitemap (MVP)

| # | Page | Route (name) | Purpose |
|---|---|---|---|
| 1 | الرئيسية (Landing) | `/` (`marketing.home`) | Primary conversion page — full section flow (§4). |
| 2 | المميزات (Features) | `/features` (`marketing.features`) | Detailed capability breakdown; reuses landing feature/module sections. |
| 3 | الحلول (Solutions) | `/solutions` (`marketing.solutions`) | Industry-tailored: NGOs, charities, small businesses, educational institutions, government. **Single page with anchored sections** (`/solutions#government`) for MVP; split into `/solutions/{industry}` later once each has unique SEO-worthy copy. |
| 4 | الأسعار (Pricing) | `/pricing` (`marketing.pricing`) | Full plan comparison + monthly/annual toggle + trial CTAs. |
| 5 | الأمان والامتثال (Security & Compliance) | `/security` (`marketing.security`) | **MVP addition.** Data isolation, 5-state lifecycle, mandatory admin 2FA (ADR-14), audit log (NFR-05), secret encryption (ADR-16). Critical for government/NGO/education buyers. |
| 6 | من نحن (About) | `/about` (`marketing.about`) | Story, vision, mission (from `PROJECT_VISION.md`). |
| 7 | تواصل معنا (Contact) | `/contact` (`marketing.contact`) | Contact form, email, office location, map. Also the destination for the hero "احجز عرضًا توضيحيًا / Book a Demo" CTA (no separate demo page in MVP). |
| 8 | الأسئلة الشائعة (FAQ) | `/faq` (`marketing.faq`) | Full question list; shares data with the landing FAQ preview. |
| 9 | سياسة الخصوصية (Privacy) | `/privacy` (`marketing.privacy`) | Legal. |
| 10 | الشروط والأحكام (Terms) | `/terms` (`marketing.terms`) | Legal. |

### 2.1 Non-GET routes

| Method | Route (name) | Handler | Notes |
|---|---|---|---|
| POST | `/contact` (`marketing.contact.store`) | `Marketing\ContactController@store` | FormRequest validation + `throttle` + mail via configured SMTP (Maildev in dev). |
| POST | `/newsletter` (`marketing.newsletter.store`) | `Marketing\NewsletterController@store` | Footer signup; FormRequest + `throttle`. |

These are the only non-GET public routes. No inline closures — every route points to a dedicated controller (project convention).

### 2.2 Deferred (post-MVP)

Request-a-Demo (standalone page — folded into Contact for now), Changelog / Product Updates, Blog / Resources, Integrations (mentioned inside Features until real), Careers, standalone Case Studies, external Status page, marketing locale switcher. Reserve route namespaces where sensible; do not build thin placeholder pages.

## 3. Plan Tiers (locked)

Single source of truth: **Startup / Growth / Enterprise** (matches `landing.blade.php`, admin `PlanController`, and docs). The Pricing page and landing preview must both read from one shared data source (§5) rather than re-declaring plan arrays.

> Decision: the alternative "Starter / Professional / Enterprise" naming was **rejected** to avoid a rename across the admin console and docs.

## 4. Landing Page Section Flow (locked order)

Reordered from the original proposal so features/modules are established **before** the product screenshot pays them off.

1. **Hero** — value proposition + dual CTAs: "ابدأ التجربة المجانية" (`/register`) & "احجز عرضًا توضيحيًا" (`/contact`).
2. **Trusted By / Social Proof** — logo ticker.
3. **Problems** — pain points of organizations without a modern ERP.
4. **Solution** — how Veyra resolves them.
5. **Core Features Highlight** — key features overview.
6. **Modules Breakdown** — grid: HR, Finance, Projects/Operations, Support, Tenancy, Security.
7. **Dashboard Preview / Product Showcase** — high-res UI preview (lazy-loaded).
8. **AI Capabilities** — **labeled roadmap / "قريباً"** (not presented as shipped).
9. **Why Choose Us / Differentiators** — value pillars.
10. **Testimonials & Success Stories** — customer proof.
11. **Pricing Preview** — compact plans (§3) + monthly/annual toggle.
12. **FAQ Accordion** — top 5–8 questions (shared source, §5).
13. **Final High-Impact CTA** — "جاهز لتحويل مؤسستك؟".
14. **Footer** — navigation, legal links, newsletter signup, social icons.

Plus a **sticky top nav** (blurred, persistent trial CTA, theme toggle) present on every marketing page.

> **Open honesty note (not blocking):** the Testimonials section (10) is kept as planned per decision, but must use real, attributable customers when live — no fabricated logos or quotes. Until real proof exists, populate with an early-partner / metrics treatment.

## 5. Execution Plan (Blade / routes / controllers)

### 5.1 Layout & components
- Reuse `components/layouts/marketing.blade.php`; add `title`/`description`/OG meta props.
- Extract shared chrome: `<x-marketing.nav>` (sticky, blurred, CTA, theme toggle) and `<x-marketing.footer>` (nav columns, legal, newsletter, socials).
- Decompose each section into a component under `resources/views/components/marketing/`: `hero`, `logo-ticker`, `problems`, `solution`, `feature-grid`, `module-grid`, `showcase`, `differentiators`, `testimonials`, `pricing-table`, `faq-accordion`, `cta-band`. Dedicated pages (Features, Pricing, FAQ) reuse these exact components.

### 5.2 Controllers & routes
- `app/Http/Controllers/Marketing/` — invokable controller per page (`HomeController`, `FeaturesController`, `SolutionsController`, `PricingController`, `SecurityController`, `AboutController`, `ContactController`, `FaqController`, `PrivacyController`, `TermsController`), all named `marketing.*`.
- `ContactController@store` + `NewsletterController@store` for the POST routes (§2.1), each with a FormRequest and rate limiting.

### 5.3 Shared data (single source of truth)
- **Plans:** extract to `config/plans.php` (or a `PlanCatalog` class) consumed by landing, `/pricing`, **and** the admin console — removes the current duplication between `landing.blade.php` and admin `PlanController`. **Target state:** `plans` / `plan_features` tables via `MARKETING_CMS.md`.
- **FAQ:** `config/faq.php` consumed by the landing preview and `/faq`. **Target state:** `faqs` table via `MARKETING_CMS.md`.
- **Copy:** keep marketing copy in config/lang data (Arabic-first, i18n-ready per ADR-10) rather than hardcoded in Blade. **Target state:** `platform_settings` JSON groups (`marketing.*`) assembled by a `MarketingContent` service — see `MARKETING_CMS.md`.

### 5.4 SEO & polish
- Per-page `<title>` + meta description + Open Graph tags via layout props.
- `sitemap.xml`, `robots.txt`, JSON-LD (`Organization` + `Product`).
- Lazy-load showcase imagery; honor `prefers-reduced-motion` (already respected in `app.css`).
- Semantic heading hierarchy; keyboard-accessible nav, accordion, and toggles.

### 5.5 Testing
- Pest smoke tests: every public GET route returns `200` and renders without JS errors.
- Contact/newsletter: validation + throttle + mail-dispatch tests.

### 5.6 Suggested build order
1. Shared nav + footer + layout meta props.
2. Refactor existing Landing into section components (locked order, §4).
3. Pricing (shared plan source, §5.3).
4. Features, Solutions, Security & Compliance.
5. Contact (+ mail) and Newsletter.
6. About, FAQ.
7. Privacy, Terms.
8. SEO artifacts + Pest smoke tests.
