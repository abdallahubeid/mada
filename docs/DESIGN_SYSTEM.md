# Mada ERP — Design System

> Part of the Mada ERP documentation set. See `MADA_DOCS.md` §12 for the summary and `MODULES.md` BR-701 for the Employee Workspace this system must render correctly.

## 1. Visual Identity

Brand name: **مدى** (latin transliteration *Mada*, used only in code comments and identifiers).

- **Palette (amended 2026-11-10 — one identity platform-wide):** the orange identity of §1.3 now covers every surface — public site, auth screens, the Platform Console, the tenant app (owner, managers, employees) and error pages. It reaches the app by **re-pointing the token values**, not the class names, so every `bg-brand-500` / `text-ink-900` / `border-mist-200` moved at once. No module or page may introduce an off-palette color scheme.
  - **Orange** (`brand-*`): `brand-500` `#fe6019` is the accent for marks, bars, icons and focus (3:1 contexts). `brand-600` `#c2410c` is orange **text** (5.2:1 on white, 4.6:1 on a 10% orange tint). `brand-action` `#cf4510` is the fill for anything carrying **white text** — buttons, the current page, chat bubbles, badges (4.65:1). White on `brand-500` is 3.0:1 and is not allowed for text.
  - **Neutrals:** `ink-*` structure (`ink-900` `#222` headings), `mist-*` muted text and fills (`mist-500` `#555` secondary copy, `mist-400` `#5b636b` meta, `mist-200/300` `#e9ecef`/`#dee2e6` hairlines), and `mist-50` `#f8f6f2` the warm off-white page canvas (amended 2026-11-11, was cream `#fffaf3`). White cards sit on it, so a page is never a sheet of white glare. Placeholders use `#636a71` (5.5:1), lighter than meta text so they never read as typed values.
- **Brand ≠ action ≠ success.** Green means one thing only: success. `success-*`, `warning-*`, `critical-*` and `accent-*` (informational blue) are separate token families and never double as the brand. A filled orange button is an action; a status is always a chip, never a fill.
- **Type:** `--font-sans` and `--font-display` are both Poppins → Tajawal (every Arabic glyph renders in Tajawal at 400–700).
- **Consistency rule:** the public site, the Super Admin console and the tenant dashboards are one product. The marketing site keeps its roomier `site-*` metrics (§1.3); the dashboards use the denser `ui-*` components (§4).

### 1.1 Geometry & elevation

Radii are applied by redefining Tailwind's scale in `app.css`, not by rewriting class names: `rounded-md`/`rounded-lg` **6px** (controls, buttons), `rounded-xl` **8px**, `rounded-2xl` **12px** (cards), `rounded-3xl` 16px. `rounded-full` is reserved for avatars, toggle knobs and unread dots.

Elevation is semantic and flat by default: **border or shadow, never both.** `shadow-sm` is the identity's soft card lift (`0 4px 25px rgb(0 0 0 / .05)`), `shadow-lg` is for dropdowns/popovers, `shadow-2xl` for modals and drawers. No shadow token carries a horizontal offset, so nothing appears lit from the wrong side when the layout mirrors to RTL.

### 1.2 Motion

Three durations (120 / 180 / 260ms) and three curves (`--ease-standard`, `--ease-enter`, `--ease-exit`). Never `transition: all` — enumerate the properties. Nothing containing text may scale on hover (scaling resamples glyphs and shimmers a 1px border); press states use `translateY(1px)`.

### 1.3 Public site identity — Andromeda (amended 2026-11-09)

At the owner's direction the public marketing site (`resources/views/marketing/*`, `x-marketing.*`, `components/layouts/marketing.blade.php`) replicates the **Andromeda** template (Themefisher, MIT). The guest auth screens follow it too (`resources/views/auth/*` on `components/layouts/auth-centered.blade.php`: sign-in, register, forgot/reset password, email verification, two-factor challenge), so a visitor keeps one canvas from the landing page to the password field. It is a presentation-only change: routes, controllers, CMS data and copy are untouched.

- **Scoped metrics.** Its tokens and components live in `resources/css/site.css` as `site-*` (imported by `app.css`), and its base rules apply only under `body.site`, which only the marketing and auth-centered layouts set. Since 2026-11-10 the app's own tokens carry the same identity (§1), so the two layers differ in density, not in colour.
- **Tokens:** primary `#fe6019` (`site-primary`), body text `#4a4a4a` (8.3:1; the template's `#666` read washed out), headings `#222`, page canvas `#f8f6f2` (`site-canvas`, same as the app), white cards, borders `#dee2e6` / peach `#ffece4`, cream panels `#fffaf3` / `#fff7f3`, unfilled circles `#ffe6db`, dashed waves `#ffe4d1`.
- **Type:** `--font-site` = Poppins → Tajawal (Poppins has no Arabic, so every Arabic glyph falls to Tajawal at the same 400–700 weights). Body 15px / 1.625; headings 600 on the template's 1.246 scale (h1 48px, h2 38.6px, h3 31px, h4 24.8px, h5 19.9px), 80% for h1–h3 below `md`.
- **Components (`site.css`):** `site-container` (1380px) / `site-container-xl` (1440px), `site-section` (64px block padding), `site-btn` + `site-btn-primary` / `site-btn-outline` (48px, 6px radius, black-wash hover; `site-btn--compact` 44px in the header), `site-section-title` (+ `--start`) with the 70×4px orange bar, `site-panel` cream wave panels (`--banner`, `--flat`, `--wave`, `--plain`) inside a `site-panel-host`, `site-circle`, `site-feature-card` + `-icon`, `site-card`, `site-input` (cream, orange edge on the page's reading-start side even for `dir="ltr"` fields), `site-label` / `site-error`, `site-check`, `site-notice`, `site-step` (`.is-current` / `.is-done`), `site-accordion`, `site-header` (fixed, 70px at rest: 12px padding around 46px nav links, the 44px CTA and a 44px menu toggle, every target ≥ 44px; on the canvas colour; tightens and hides on scroll-down, returns on scroll-up), `site-social`, `site-testimonial-blob`, `site-prose`.
- **Blade:** `x-marketing.panel` and `x-marketing.circles` (named circle presets) draw the template's decoration; the large circles are hidden below `md` so they never sit on copy.
- **CMS title markers stay.** `**…**`, `((…))` and `__…__` still render (`mada-marker`, `mada-circle`, `mada-underline-double`), recoloured to the orange/peach palette in `app.css` so the admin preview still paints the public mark.
- **Auth screens fit one viewport (2026-11-11).** `auth-centered` pins `body.site-auth` to `h-dvh` with `overflow-hidden`: header, the panel and footer share the dynamic viewport, so the page never scrolls and mobile browser chrome cannot push the footer off. The card sits in an inner `overflow-y-auto` layer, centred with `my-auto`, which only scrolls when a viewport is shorter than the form rather than clipping its submit button. Fields are 48px (`.site-auth .site-input`). Two height variants in `app.css` keep the longest screen (register step 2) inside the viewport down to an iPhone SE with Safari bars (375×553): `short:` (≤ 680px) drops the subtitles and trims padding; `tiny:` (≤ 640px) also hides the footer and takes fields and steps to the 44px target floor. Measured: login and all three register steps fit at 1920×1080 … 1366×657, 1280×600, 768×1024 and 412×780 … 360×560.
- **Favicon (2026-11-11).** The brand mark (white "م" on `#fe6019`, 20% corner radius): `favicon.svg`, a 16/32/48px `favicon.ico`, 16/32px PNGs, a full-bleed 180px `apple-touch-icon.png`, 192/512px manifest icons and `site.webmanifest`, all linked by `x-site-favicon` with a `?v=` cache-buster. A favicon uploaded in the admin settings still replaces the tab icon.
- **Contrast (amended 2026-11-10):** `#fe6019` is 3.0:1 on white, so it is for shapes, icons, bars and marks only. Orange **text** (links, the active nav item, breadcrumbs, step numbers, prices) uses `site-primary-ink` `#c2410c` (5.2:1); a **fill under white text** (`site-btn-primary`, the pricing toggle, the "الأكثر طلباً" badge, numbered steps, `site-step.is-done`, the testimonial blob) uses `site-primary-action` `#cf4510` (4.65:1). The logo mark is exempt as a logotype.

## 2. Appearance Strategy — One Light Canvas (supersedes ADR-15)

> **ADR-15 is withdrawn.** Mada previously shipped a user-toggled dark theme that defaulted to dark and persisted in `localStorage`. It no longer does. This section describes what the code actually does; the rescinded decision is preserved in §2.5 so the reversal is legible rather than looking like drift.

### 2.1 Decision

**There is one theme: light.** Marketing site, auth screens, Platform Console, tenant app and company portal share a single canvas, so the product no longer changes tone from section to section. There is no toggle, no stored preference, and no OS-media-query fallback.

This is not the same as "dark mode is unimplemented". It was implemented, shipped, and withdrawn — so the rule for new work is that a component ships **one** styling pass, and adding a `dark:` variant to new markup is a defect, not thoroughness.

### 2.2 Implementation rules

- Shared components define a **single** light-mode styling pass. A component is not "incomplete" for lacking a dark variant.
- **No surface ships a theme toggle.** Four were removed. This is pinned by `no surface ships a theme toggle any more` in `tests/Feature/Tenant/ProfileTest.php` — that test walks the layouts and fails on any new `localStorage.setItem('mada-theme'…)`, so a reintroduced toggle cannot land quietly.
- Charts/graphs on the Financial Dashboard target one set of tones, measured against white.
- Print views (e.g., Payslip) are unchanged: they always render print-safe, which is now the same canvas the screen uses.

### 2.3 Theme bootstrap — `<x-theme-script />` is kept on purpose

The component was **not** deleted with the feature, and every page that owns its own `<html>` still includes it in `<head>` before the stylesheet. It now enforces the single theme rather than selecting one:

- It **strips `.dark` from `<html>`** on load and again on every `livewire:navigated`. `wire:navigate` copies the incoming document's `<html>` attributes onto the live element, so a `dark` class left by a cached page, an extension or a restored bfcache entry would otherwise survive the swap. Stripping after each navigation is what makes "light only" true at runtime and not merely true in the templates.
- It **clears the legacy `mada-theme` key** once. Returning visitors still carry `"dark"` in their browser from before the change. Nothing reads it today, but leaving it would let a future stored-preference feature silently inherit a choice made against a palette that no longer exists. The `localStorage` access is wrapped in `try/catch` — private-mode browsers throw on access, and losing the cleanup is acceptable where taking the page down before first paint is not.

### 2.4 Muted text is a single ramp (updated 2026-11-11)

The `mist-*` stops were theme-split while both themes existed. They are now **single-valued** in the `@theme` block of `app.css`, measured on white:

| Token | Value | Contrast on white | Role |
|---|---|---|---|
| `--color-mist-400` | `#5b636b` | 6.1:1 (5.6:1 on the canvas) | tertiary / meta text |
| `--color-mist-500` | `#555555` | 7.5:1 (6.9:1 on the canvas) | secondary body text |
| `--color-mist-600` | `#444444` | 9.7:1 | emphasis |

- **Always-dark *surfaces* still exist, and are not dark mode.** The footer, the CTA band, the login showcase and the 403/404 pages are deep-neutral brand slabs on a light site. A `:where()` block in `app.css` re-resolves the `mist-*` tones inside them so their muted text stays legible without any per-panel opt-out.
- **New always-dark panels declare `data-surface="dark"`** rather than extending the enumerated `[class~='bg-ink-*']` allowlist beside it. Those selectors match with `~=` (whole token), never `*=`: a substring match would also hit `dark:bg-ink-900/60`, which sits in the class attribute of cards that are **white** — they would then take dark muted tones on a white ground, i.e. the exact bug that block exists to fix, inverted.
- Stops 50–300 and 700–900 paint borders, dividers and surfaces and were never theme-split.
- **Verify with measurement, not by eye.** The landing page currently measures **zero** AA failures.

### 2.5 The `dark:` variants still in the markup are inert

Roughly **3,880 `dark:` utilities across 187 Blade files** remain. Nothing ever adds the class that activates them, so they render nothing.

They are left in place deliberately: the retirement is currently reversible from **one file** (`theme-script.blade.php`), and a mass strip would both destroy that property and produce a 187-file diff with no visible effect. They are removed **opportunistically, as each view is next touched for other reasons** — not in a sweep.

Do not read an existing `dark:` class as a pattern to copy. New markup gets one pass.

## 3. Layout Direction — RTL/LTR Native Compatibility (ADR-10)

- Mada is bilingual from v1: **Arabic (primary) and English**, and must support both right-to-left (RTL) and left-to-right (LTR) layouts natively.
- **Rule:** use Tailwind's **logical properties** everywhere directionality matters — `ms-`/`me-` (margin-start/end) instead of `ml-`/`mr-`, `ps-`/`pe-` instead of `pl-`/`pr-`, `start-`/`end-` instead of `left-`/`right-`, and logical text alignment. Physical-direction utility classes (`ml-`, `mr-`, `left-`, `right-`) are **not permitted** in shared components or module views.
- The `<html>` element's `dir` attribute is set based on the active locale (`rtl` for Arabic, `ltr` for English) — components must never hardcode a direction assumption.
- Icons that imply direction (e.g., arrows, chevrons for "next/back") must flip automatically with `dir`, not be hardcoded to point one way.

## 4. Component Inventory (mandatory shared components)

### 4.1 Dashboard primitives — `resources/css/ui.css` (2026-11-10)

Every dashboard (Platform Console, owner, managers, employees) builds controls from these classes instead of repeating utility strings. Layout utilities on the element (`w-full`, `flex-1`, `ms-auto`, `hidden sm:inline-flex`, `ps-9` for an icon) still apply on top; colour, border, radius, padding and type come from the class.

| Class | Use | Spec |
|---|---|---|
| `ui-btn` + `ui-btn-primary` | The one main action of a view | `brand-action` fill, white 600 text, 6px radius, 40px min-height; hover is a 10% black wash |
| `ui-btn-secondary` | Second actions, row actions, the "تصفية" filter submit | White, `mist-300` hairline, `ink-900` text; hover turns border and text orange |
| `ui-btn-neutral` | Cancel / back / reset-filter ("مسح", "إعادة ضبط") / quiet actions | `mist-100` fill, `ink-900` text |
| `ui-btn-danger` / `ui-btn-danger-outline` | Destructive actions (filled for the final confirm, outline in rows) | `critical-500` |
| `ui-btn-success` | Approve-type confirmations | `success-500` fill |
| `ui-btn-sm` / `ui-btn-lg` | Size steps | 32px / 48px min-height |
| `ui-control` | Every text input, select and textarea | 40px min-height, `mist-300` border, 6px radius, white; focus = orange border + 3px orange ring; `aria-invalid="true"` or `.is-invalid` = critical border; disabled/readonly = `mist-100` |
| `ui-label` / `ui-help` / `ui-error` | Field label, hint, inline error | 15px; label `ink-900` 500, hint `mist-500`, error `critical-500` 500 |
| `ui-card` | Every content container | White, 12px radius, `shadow-sm`, no border; padding stays in markup (`p-4` dense, `p-6` forms) |

Rules that come with them:

- **Every control has an accessible name.** A visible `<label for>` paired by `id`, or `aria-label` when there is no visible label (filters, table-row inputs, select-all checkboxes).
- **White text only on `brand-action`, `critical-500` or `success-500`.** Never on `brand-500`.
- **One `<h1>` per page.** The topbar title is a `<p>`; the page's own heading is the `<h1>`.
- **Targets are at least 24px tall.** Small text links (breadcrumbs, "عرض الكل", list titles) get `inline-flex min-h-6 items-center` or `block py-0.5`.
- **Decorative separators** (`/` in breadcrumbs, `·` between hints) use `mist-400` and `aria-hidden="true"`.
- **Transitions name their properties** (`transition-colors`, `transition-shadow`, `transition-[width]`). Never `transition-all`.
- **Pagination** is the published `resources/views/vendor/pagination/tailwind.blade.php`: Arabic range summary, 36px page buttons, current page on `brand-action`, arrows flipped under RTL.
- **Charts** use `#c2410c` (orange) and `#0369a1` (blue), validated for colour-blind separation and 3:1 on white; text in `#222`/`#666`, grid `#e9ecef`.

### 4.2 Shared patterns

| Component | Purpose | Notes |
|---|---|---|
| `card` | Base content container used across all dashboards/lists | `ui-card` (§4.1) |
| `status-badge` | Status indicators (`pending`, `active`, `suspended`, task statuses, payroll states) | Color-coded consistently per status across the whole app |
| `kanban-column` | Drag-and-drop board column (Applicants, Tasks) | Livewire-driven (ADR-01) |
| `empty-state` | Icon + message + guided CTA, shown on every list/board/dashboard with no data | Never a blank screen (per `USER_JOURNEYS.md` §4) |
| `slide-over-drawer` | Notifications drawer, quick-view panels | Slides from layout-appropriate side based on `dir` |
| `wizard-stepper` | Multi-step forms (Registration, Setup Wizard) | Shows step X of Y explicitly |
| `payslip/print-view` | Print-friendly payslip modal/page | Print-safe light, same canvas as the screen (§2.2) |
| `message-thread` | Conversation inbox (list + detail + reply composer) | Used by the Super Admin Support Inquiries console (`/admin/messages`, `MODULES.md` §6) |

## 5. Density & Typography

- ERP dashboards prioritize data density over marketing-style whitespace: default compact table row height, information-dense list views.
- Marketing/public pages (Landing, Pricing, Careers) may use more generous spacing consistent with a "premium" first impression — this is the one place default Tailwind spacing (not compact) is expected.

### 5.1 Control metrics

| Token | Value | Applies to |
| --- | --- | --- |
| `--spacing-control` | 32px | compact controls (toolbars, `ui-btn-sm`) — standard `ui-btn` / `ui-control` are 40px |
| `--spacing-row` | 36px | data table rows (`px-3 py-2` at `text-sm`) |
| `--spacing-bar` | 48px | topbar, card header, toolbar |

App-surface cards use 16px padding (`p-4`), matching Odoo's `$o-spacer`. Marketing keeps its generous scale.

### 5.2 Font weights — 400 / 500 / 600 / 700

Tajawal is imported at `wght@400;500;600;700` and **all four must stay in the import.** The font previously loaded only 400/500/700, so every `font-semibold` (600) had no matching face and the CSS font-matching algorithm resolved it upward to 700 — `font-semibold` and `font-bold` rendered identically across ~1,500 elements, collapsing the second tier of the hierarchy in the primary language. Dropping 600 from the import silently reintroduces that.

### 5.3 Arabic typography rules

- **Never `tracking-*` on Arabic text.** Arabic is cursive; letter-spacing breaks the joins between letters and renders words as disconnected glyphs. Column headers previously carried `tracking-wider` for this reason and no longer do. Letter-spacing is permitted on latin-only strings.
- **`uppercase` is a no-op on Arabic** and was removed from `<th>` along with the tracking.
- **Tabular figures are automatic.** `font-variant-numeric: tabular-nums` is applied at the `table` element in `app.css` rather than per cell — the UI runs RTL while the digits inside it run LTR, so ragged figure widths read as broken alignment.

## 6. Employee Workspace UI Requirements (ties to BR-701)

- The Check-In/Check-Out action is a single, prominent, unmistakable primary action on the Employee's "My Space" home — not buried in a settings menu.
- "My Tasks" for the Employee role never renders an "all tasks" or "team view" toggle — the component itself must be a distinct, scoped variant from the manager-facing task board, not the same component with a hidden filter (reinforces the BR-701 authorization boundary at the UI layer, backed by the Policy enforcement in `MODULES.md` §5.3).

## 7. Empty States

Every table, Kanban board, and dashboard must implement the `empty-state` component on first use, with a guided call-to-action appropriate to the context (e.g., "Add your first project," "No tasks assigned yet," "No leave requests pending"). Blank/white screens on first login are not acceptable in a commercial product.

For tables specifically, use `<x-ui.table-empty :colspan="N" message="…" />` inside a `@forelse … @empty` block. The colspan must equal the table's column count so the message stays centred under the header.

## 8. Data Tables

One markup contract for every data table in the tenant and admin apps. Print views (`reports/print/*`) are exempt — they ship standalone inline CSS with no Tailwind.

### Structure

```blade
<div class="w-full overflow-x-auto rounded-2xl border border-mist-200 bg-white shadow-sm">
    <table class="min-w-full text-sm">
        <thead class="bg-mist-50"> … </thead>
        <tbody class="divide-y divide-mist-100"> … </tbody>
    </table>
</div>
```

Existing tables in the app still carry `dark:border-ink-600 dark:bg-ink-800` and friends. Those are inert leftovers (§2.5), not part of the contract — match the block above for new tables and drop the variants from an old one when you are editing it anyway.

### Cell classes

| Slot | Classes |
| --- | --- |
| `<th>` | `px-3 py-2 text-xs font-medium text-mist-500` |
| `<td>` | `px-3 py-2 text-sm text-ink-700` |
| Index `#` (th + td) | add `w-12 text-center`; the `td` also takes `text-mist-500` |
| Status badges / actions (th + td) | add `text-center` |
| Numeric amounts & currency (th + td) | add `text-end` |
| Everything else — text, dates, names (th + td) | add `text-start` |

A `<th>` and its matching `<td>` must carry the **same** alignment and padding. Row height stays compact per §5.

### Index column

Every data table's first column is `#`. It is a display counter, never a record id.

- **Unpaginated** table → `{{ $loop->iteration }}`.
- **Paginated** table → carry the page offset so the sequence stays continuous:
  `{{ $loop->iteration + ($rows->currentPage() - 1) * $rows->perPage() }}`.

Restarting at `1` on page 2 misreads as a different record set, so paginated tables must not use bare `$loop->iteration`.

## 9. Confirmation Dialogs & Toasts

### Confirm intent must match the action

`data-swal-confirm` on a form opens a SweetAlert confirmation. The **variant** decides icon, button colour and default copy:

| `data-swal-variant` | Use for | Icon / colour | Default confirm label |
|---|---|---|---|
| `danger` *(default)* | Deletion and trash moves — **and nothing else** | warning / red | «نعم، احذف» |
| `warning` | Rejection, cancellation, deactivation, archiving, ending an employment | warning / amber | «نعم، تابع» |
| `success` | Approval, finalization, conversion — the record advances | question / green `#0f7b3d` | «نعم، تابع» |
| `info` | Submission, disbursement recording — a neutral forward step | question / sky | «نعم، تابع» |

**`danger` is the default only for backwards compatibility with existing delete forms.** Any non-deletion action MUST declare its variant. Omitting it produces a red "نعم، احذف" button over the text *«سيتم الحذف الناعم…»* — which is how an Approve button once warned the user their payroll run was about to be deleted.

Always set `data-swal-text` to what will actually happen, and `data-swal-confirm-button` to the action in the user's words («نعم، اعتمد المسيرة»), never a generic yes.

```blade
<form method="POST" action="{{ route('finance.payroll-runs.approve', $run) }}"
      data-swal-confirm
      data-swal-variant="success"
      data-swal-title="اعتماد مسيرة الرواتب؟"
      data-swal-text="سيتم قفل المسيرة نهائياً ولن يمكن تعديل أي مبلغ فيها."
      data-swal-confirm-button="نعم، اعتمد المسيرة">
```

### Flash toast tone must match the outcome

`flash()->success()` / `info()` / `warning()` / `error()` map directly to the toast icon, so the tone is a factual claim about what happened:

- **success** — the record advanced (created, approved, disbursed)
- **info** — a neutral change (updated, recalculated, activated)
- **warning** — the record did *not* advance (rejected, deactivated, soft-deleted with undo)
- **error** — the action was refused; the message names the reason

A rejection flashed green tells the approver the opposite of what they just did.

### RTL: never put `dir="ltr"` on a `<td>`

Wrap latin-script values in `<x-ui.ltr>` instead:

```blade
<td class="px-4 py-3 text-start"><x-ui.ltr>{{ $row->date?->format('Y-m-d') }}</x-ui.ltr></td>
```

`dir` changes what the logical `text-start` resolves to — left inside an LTR cell, right inside the RTL header. Setting it on the cell therefore pushes the value to the opposite edge from its own header while `getComputedStyle` still reports `start` on both, which makes the resulting misalignment look unrelated to direction. Isolating the value on an inner span keeps the cell RTL so header and cell share one edge, while digits stay in reading order.
