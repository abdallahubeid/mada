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
