# Mada ERP — Development Roadmap

> Part of the Mada ERP documentation set. Phase boundaries are binding per ADR-11 (`MADA_DOCS.md`). Re-scoping a phase requires updating this document first, not a side conversation mid-implementation.

## Phase 1 — Core & MVP Operations

**Goal:** a tenant can self-register, get approved, and run HR + Projects day-to-day, with no financial module yet. This validates the tenancy/RBAC foundation and the daily-use loop before financial complexity is layered on.

**Scope:**
- Tenancy core: `TenantContext`, global scope enforcement, `EnsureTenantActive` middleware.
- Auth: registration, login, email verification (ADR-05), Super Admin 2FA (ADR-14).
- Onboarding: 5-state lifecycle (`ARCHITECTURE.md` §3), setup wizard.
- RBAC: Spatie Permission with Teams (ADR-03), default seeded roles (BR-102).
- Org Settings: work calendar, currency, departments.
- HR: Employees & Contracts, Attendance, Time Off (with Work Ledger reconciliation — BR-401/BR-402).
- Projects: Projects, Tasks, Kanban board, Timesheets (`is_billable` flag present even though Invoicing isn't built yet).
- **Employee Workspace (BR-701):** Check-In/Check-Out, "My Tasks" scoped view — this is core MVP, not deferred, since it's the highest-frequency user journey.
- Design System foundation: Plum/Slate palette, **one light canvas (ADR-24 — supersedes the withdrawn ADR-15 dual-theme decision)**, RTL/LTR logical spacing (ADR-10).
- Platform services (minimum viable): basic Notifications (email + simple in-app list), Activity/Audit Log, generic Approval Engine (used by Time Off in this phase; ready for Payroll/Offers in later phases), custom 403/404/500 error pages.
- **Super Admin / Platform Console (`MODULES.md` §6):** Dashboard, Tenant Management (list with search, plan filter and pagination over real records), Tenant Detail, **the four lifecycle transitions — approve, reject, suspend, reactivate — each gated on `tenants.manage`, each refusing any status but the one it accepts, each mailing the Owner and writing to the platform audit log (BR-205, BR-207, BR-209)**, Plans & Feature Limits (reference/configuration only — enforcement is Phase 4), Platform Settings (branding, SMTP, registration auto-approval toggle, legal documents — **payment gateway key fields are present in the UI but inert until Phase 2**), Notifications Console (pending-approval/security-flag/failed-job categories active; plan-limit-warning category stays empty until Phase 4's `CheckFeatureLimit` ships), Support Inquiries (message thread inbox + audit log), Super Admin User Management (invite-only operator accounts with mandatory 2FA and last-admin lockout safeguards — BR-807/BR-808), and the Two-Factor Challenge + Account Security surfaces that back mandatory 2FA (ADR-14).

**Exit criteria:** a new tenant can go from signup to an active, functioning HR+Projects workspace, with employees able to check in/out and manage their own tasks, entirely in their chosen theme and language direction, with zero cross-tenant data leakage under test. A Super Admin can approve/reject/suspend tenants, configure baseline platform settings, and respond to a tenant support inquiry, all from the Platform Console.

---

## Phase 2 — Finance & Payroll

> **Re-scoped 2026-08-06 (ADR-18).** Phase 2 is split into **2A — Payroll** and **2B — Revenue**. Phase 2B is blocked on the Projects & Timesheets module, which is unbuilt Phase 1 scope (see "Phase 1 carry-over debt" below). BR-604 sources invoices from billable timesheets; with no `timesheets`, `projects`, or `clients` table, invoicing has no source data. Delivering them as one phase would block payroll — which *is* buildable today against attendance, leave, calendars and contracts — behind unrelated work.

### Phase 2A — Payroll

**Goal:** turn the attendance/leave data the system already holds into a locked, auditable payroll run.

**Scope:**
- **Approval Engine (ADR-08):** the `approvals` table, built before its second consumer. Leave Requests backfilled onto it; the three bespoke escalation columns on `leave_requests` migrated in and dropped (BR-901).
- **Work Ledger (ADR-21):** materialized `work_ledger_entries`, one `WorkLedgerReconciler` service, idempotent rebuild, locked-period guard (BR-403–BR-407).
- **Contract pay fields (ADR-19):** `pay_basis`, `base_rate`, `billing_rate`, `pay_currency` on `employee_contracts` (BR-301a/b).
- Payroll: maker-checker workflow (BR-603), flexible allowance/deduction line items (BR-601), absence deductions sourced from the Work Ledger (BR-602), snapshot-on-lock (BR-608), adjustment-only corrections.
- Expenses: expense claims and categories routed through the Approval Engine (BR-613).
- Offboarding: contract end dates, final settlement calculation, automatic access revocation (BR-606).
- **Finance Settings (ADR-23, BR-624–BR-627):** per-tenant end-of-service rules — tier boundary, both accrual rates, resignation taper bands, nominal working month — configured by the Owner and Finance Manager, consumed by the pure calculator as a value object, and snapshot onto every settlement. Closes the "EOSB rates are a hardcoded assumption" item that BR-621 raised.
- **Cost-side** Financial Dashboard: payroll + expense figures, sourced only from `approved`/`paid` records (BR-607). Revenue and Net Profit tiles do **not** render — a zero would misread as "no revenue" rather than "not tracked yet."
- Employee Workspace extension: employees can view their own (locked) payslips, read-only, print-friendly (BR-614).

**Exit criteria:** a full payroll run can be produced end-to-end from real attendance/leave data — reconciled through the Work Ledger, approved by a checker who is provably not the maker, locked against edit at the model layer, and visible read-only to the employee — with the cost-side dashboard reflecting only finalized data.

### Phase 2B — Revenue *(blocked)*

**Goal:** close the loop from tracked work to client revenue.

**Entry condition:** the Projects & Timesheets module exists (`projects`, `tasks.project_id`, `task_statuses`, `timesheets` with `is_billable`).

**Scope:**
- Clients (CRM-lite) entity.
- Invoicing: `client_invoices` generated from billable timesheets (BR-604, BR-616), per-tenant gapless numbering, number assigned at issue.
- **VAT/tax (ADR-22):** line-level tax designed in from the first migration, per `DATABASE_ROADMAP.md` §5 — not retrofitted onto issued invoices.
- Revenue-side Financial Dashboard: revenue and net-profit tiles activate.
- Tenant's own subscription billing (payment gateway integration — selection is an open item, see `MADA_DOCS.md` §16). This is when the payment gateway key fields already present in Phase 1's Platform Settings page (`/admin/settings`) become live/functional.

**Exit criteria:** a full client invoice can be produced end-to-end from real timesheet data, with tax correctly applied and the Financial Dashboard reflecting only finalized data.

---

## Phase 1 Carry-Over Debt

Tracked explicitly rather than left implicit, because Phase 2B and Phase 3 both depend on it:

- **Projects & Timesheets module — not built.** Phase 1 scope listed "Projects, Tasks, Kanban board, Timesheets (`is_billable` flag present even though Invoicing isn't built yet)." None of it exists. The `tasks` table that does exist is an HR line-manager assignment tool (`manager_id`/`employee_id` → `employees`, no `project_id`) and does not satisfy this; reconciling the two shapes is part of the work.
- **Approval Engine — not built in Phase 1** as scoped ("generic Approval Engine (used by Time Off in this phase; ready for Payroll/Offers in later phases)"). Leave shipped with bespoke escalation columns instead. Phase 2A now builds the engine and backfills Leave onto it.
- **MediaLibrary (ADR-13) — not installed.** Files use path columns on a `custom` disk. Must be resolved before payslip PDFs ship, since signed tenant-isolated URLs are a security requirement (NFR-03).

---

## Phase 3 — ATS & Recruitment

**Goal:** close the loop at the front end — from public job posting to a working employee account.

**Scope:**
- Public Careers pages (`/companies/{slug}/careers`, job detail + application form).
- Job Openings management (publish/close).
- Applicants Kanban board with CV viewer modal.
- Auto-conversion: accepted applicant → Employee + Contract record + credentials email (BR-303).
- Applicant data retention/anonymization policy enforcement (BR-304).

**Exit criteria:** the full canonical loop (recruit → employ → track work → get paid/invoice) is closed end-to-end without manual data re-entry at any step.

---

## Phase 4 — Platform Maturity & SaaS Limits

**Goal:** make Mada enterprise-defensible, not just functional — the features that support scale, trust, and plan-based monetization.

**Scope:**
- Strategic Hierarchy (Goals/Programs) as an opt-in tenant feature (ADR-12).
- Feature-gating enforcement: `CheckFeatureLimit` checks at point-of-creation for every plan-limited resource (`ARCHITECTURE.md` §4) — not just Phase 1's basic plan reference, but active enforcement. This also activates the plan-limit-warning category in the Super Admin Notifications Console, scaffolded but empty since Phase 1.
- Global search across employees/projects/tasks.
- Reports/export (PDF/Excel) for payroll, financial dashboard, attendance.
- Super Admin "impersonate tenant" for support, with mandatory audit logging of every impersonation session (NFR-05).
- Real-time notifications (upgrade from Phase 1's basic list to live push/slide-over updates).
- Revisit open items from `MADA_DOCS.md` §16 (multi-currency evaluation, applicant portal auth model) if commercially justified by then.

**Exit criteria:** Mada operates safely at multi-hundred-tenant scale with enforced plan limits, auditable support access, and reporting/search parity with the enterprise tools it competes against.

---

## Roadmap Governance

- No phase may begin before the prior phase's exit criteria are met and verified, unless explicitly re-approved by updating this document.
- Any request to build a Phase 2+ feature "early" must be evaluated against the risk noted in `MADA_DOCS.md` §15 ("Scope creep re-bundling Phase 2/3 into MVP") before proceeding.
