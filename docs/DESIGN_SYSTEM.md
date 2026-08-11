# Veyra ERP — Design System

> Part of the Veyra ERP documentation set. See `VEYRA_DOCS.md` §12 for the summary and `MODULES.md` BR-701 for the Employee Workspace this system must render correctly.

## 1. Visual Identity

- **Palette:** Emerald & Charcoal, used consistently across the public marketing site, the tenant app, the Super Admin console, and error pages (403/404/500). No module or page may introduce an off-palette color scheme.
  - Emerald is the primary action/brand accent (CTAs, active states, success indicators, brand marks).
  - Charcoal is the primary neutral (backgrounds, text, chrome) in both light and dark variants — see §2.
- **Consistency rule:** the Landing Page, Super Admin console, and internal tenant dashboard must feel like one product, not three different skins.

## 2. Appearance Strategy — Dark Mode & Light Mode (ADR-15)

### 2.1 Decision

Veyra's frontend natively supports **Dark Mode and Light Mode** using Tailwind CSS's **class-based `dark:` variant strategy** (`darkMode: 'class'` in the Tailwind configuration), not the OS-media-query-only strategy. This is a deliberate choice:

- **Class-based** means the user's explicit choice (a toggle in the app shell, not just their OS setting) is what controls the theme, and that choice is **persisted** (per-user preference, applied on next load before first paint to avoid a flash of the wrong theme).
- Every shared component must ship with both variants **from the moment it is built** — this is a Phase 1 requirement, not a Phase 4 polish item, because Veyra is a commercial product from day one.

### 2.2 Implementation rules

- Every shared component (`card`, `status-badge`, `kanban-column`, `empty-state`, `slide-over-drawer`, `wizard-stepper`, `payslip/print-view`, tables, forms, sidebar, top bar, notification drawer) must define both a light-mode and `dark:`-prefixed styling pass before it is considered complete — a component without a dark variant is not done.
- A theme toggle is present in the tenant app shell and the Super Admin console (not required on the public marketing site, which may standardize on one theme for brand consistency — confirm with brand guidelines if this changes).
- Charts/graphs on the Financial Dashboard must have distinct, tested color sets for both themes — the emerald accent must remain legible on both light and dark backgrounds.
- Print views (e.g., Payslip) always render in a fixed light/print-safe theme regardless of the active app theme, since printed output should not depend on screen appearance settings.

### 2.3 Theme bootstrap — one definition (added 2026-08-10)

- **Dark is the product default.** A visitor with no stored preference gets dark, on every surface: marketing site, registration, Platform Console, tenant app, company portal.
- The pre-paint script lives in **one** component, `<x-theme-script />`, and every page that owns its own `<html>` must include it in `<head>` **before** the stylesheet. It previously existed as six inline copies; the seventh surface that needed it (the disabled company-portal page) was written without it and rendered light for everyone regardless of their stored preference. One component means a page either has the behaviour or visibly does not include it.
- **`localStorage` holds only an explicit user choice.** The bootstrap does not write a key on first visit, so "no preference" stays distinguishable from "chose dark" — which is what keeps the default a property of the component rather than something already burned into every existing visitor's browser.

### 2.4 Muted text is theme-dependent (added 2026-08-10)

The Figma reference specifies the **dark** theme only, so `mist-400/500/600` were originally its dark-theme tones used in both themes. On a light canvas that measured **2.29:1** (`mist-400`) and **3.17:1** (`mist-500`) — well under the WCAG AA 4.5:1 floor, and the cause of the washed-out secondary text across every light-mode screen.

- These three stops are now **theme-split in `app.css`**: the `@theme` block holds the light ramp (4.8:1 / 6.2:1 / 8.5:1 on white) and `.dark` restores the Figma tones. `text-mist-500 dark:text-mist-400` therefore keeps working untouched — **do not** rewrite those pairings in markup.
- **Always-dark surfaces carry the dark tones automatically.** Panels that are dark in both themes (product-tour frame, footer, CTA panel, login showcase, 403/404) have no `dark:` variants, so the same rule keys the dark tones off the `bg-ink-700/800/900/950` utilities. Those selectors use `~=` (whole-token) matching, never `*=`: a substring match also hits `dark:bg-ink-900/60`, which appears on cards that are **white** in light mode.
- `--color-emerald-600` is likewise light-only (5.5:1 on white); Tailwind's stock value measures ~3.7:1 and is used for 12–14px eyebrow labels and active nav items.
- **Verify with measurement, not by eye.** Both themes currently measure **zero** AA failures on the landing page. Stops 50–300 and 700–900 are not theme-split — they paint borders, dividers and surfaces.

## 3. Layout Direction — RTL/LTR Native Compatibility (ADR-10)

- Veyra is bilingual from v1: **Arabic (primary) and English**, and must support both right-to-left (RTL) and left-to-right (LTR) layouts natively.
- **Rule:** use Tailwind's **logical properties** everywhere directionality matters — `ms-`/`me-` (margin-start/end) instead of `ml-`/`mr-`, `ps-`/`pe-` instead of `pl-`/`pr-`, `start-`/`end-` instead of `left-`/`right-`, and logical text alignment. Physical-direction utility classes (`ml-`, `mr-`, `left-`, `right-`) are **not permitted** in shared components or module views.
- The `<html>` element's `dir` attribute is set based on the active locale (`rtl` for Arabic, `ltr` for English) — components must never hardcode a direction assumption.
- Icons that imply direction (e.g., arrows, chevrons for "next/back") must flip automatically with `dir`, not be hardcoded to point one way.

## 4. Component Inventory (mandatory shared components)

| Component | Purpose | Notes |
|---|---|---|
| `card` | Base content container used across all dashboards/lists | Light + dark variants required |
| `status-badge` | Status indicators (`pending`, `active`, `suspended`, task statuses, payroll states) | Color-coded consistently per status across the whole app |
| `kanban-column` | Drag-and-drop board column (Applicants, Tasks) | Livewire-driven (ADR-01) |
| `empty-state` | Icon + message + guided CTA, shown on every list/board/dashboard with no data | Never a blank screen (per `USER_JOURNEYS.md` §4) |
| `slide-over-drawer` | Notifications drawer, quick-view panels | Slides from layout-appropriate side based on `dir` |
| `wizard-stepper` | Multi-step forms (Registration, Setup Wizard) | Shows step X of Y explicitly |
| `payslip/print-view` | Print-friendly payslip modal/page | Fixed light theme regardless of app theme (§2.2) |
| `message-thread` | Conversation inbox (list + detail + reply composer) | Used by the Super Admin Support Inquiries console (`/admin/messages`, `MODULES.md` §6); light + dark variants required |

## 5. Density & Typography

- ERP dashboards prioritize data density over marketing-style whitespace: default compact table row height, information-dense list views.
- Marketing/public pages (Landing, Pricing, Careers) may use more generous spacing consistent with a "premium" first impression — this is the one place default Tailwind spacing (not compact) is expected.

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
<div class="w-full overflow-x-auto rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
    <table class="min-w-full text-sm">
        <thead class="bg-mist-50 dark:bg-ink-900"> … </thead>
        <tbody class="divide-y divide-mist-100 dark:divide-ink-700"> … </tbody>
    </table>
</div>
```

### Cell classes

| Slot | Classes |
| --- | --- |
| `<th>` | `px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400` |
| `<td>` | `px-4 py-3 text-sm text-ink-700 dark:text-mist-200` |
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
| `success` | Approval, finalization, conversion — the record advances | question / emerald | «نعم، تابع» |
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
