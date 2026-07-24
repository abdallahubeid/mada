# Veyra ERP — Development Roadmap

> Part of the Veyra ERP documentation set. Phase boundaries are binding per ADR-11 (`VEYRA_DOCS.md`). Re-scoping a phase requires updating this document first, not a side conversation mid-implementation.

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
- Design System foundation: Emerald/Charcoal palette, **Dark/Light mode (ADR-15) built into every component from the start**, RTL/LTR logical spacing (ADR-10).
- Platform services (minimum viable): basic Notifications (email + simple in-app list), Activity/Audit Log, generic Approval Engine (used by Time Off in this phase; ready for Payroll/Offers in later phases), custom 403/404/500 error pages.
- **Super Admin / Platform Console (`MODULES.md` §6):** Dashboard, Tenant Management (list), Tenant Detail, Plans & Feature Limits (reference/configuration only — enforcement is Phase 4), Platform Settings (branding, SMTP, registration auto-approval toggle, legal documents — **payment gateway key fields are present in the UI but inert until Phase 2**), Notifications Console (pending-approval/security-flag/failed-job categories active; plan-limit-warning category stays empty until Phase 4's `CheckFeatureLimit` ships), Support Inquiries (message thread inbox + audit log), Super Admin User Management (invite-only operator accounts with mandatory 2FA and last-admin lockout safeguards — BR-807/BR-808), and the Two-Factor Challenge + Account Security surfaces that back mandatory 2FA (ADR-14).

**Exit criteria:** a new tenant can go from signup to an active, functioning HR+Projects workspace, with employees able to check in/out and manage their own tasks, entirely in their chosen theme and language direction, with zero cross-tenant data leakage under test. A Super Admin can approve/reject/suspend tenants, configure baseline platform settings, and respond to a tenant support inquiry, all from the Platform Console.

---

## Phase 2 — Finance & Payroll

**Goal:** close the loop from tracked work to money — both employee pay and client revenue.

**Scope:**
- Clients (CRM-lite) entity.
- Payroll: maker-checker workflow (BR-603), flexible allowance/deduction line items (BR-601), absence deductions sourced from the Work Ledger (BR-602).
- Offboarding: contract end dates, final settlement calculation, automatic access revocation (BR-606).
- Invoicing & Expenses: invoices generated from billable timesheets (BR-604), expense claims routed through the Approval Engine.
- Financial Dashboard: revenue/expense/net-profit view, sourced only from `approved`/`paid`/`issued` records (BR-607).
- Employee Workspace extension: employees can view their own (locked) payslips, read-only, print-friendly.
- Tenant's own subscription billing (payment gateway integration — selection is an open item, see `VEYRA_DOCS.md` §16). This is when the payment gateway key fields already present in Phase 1's Platform Settings page (`/admin/settings`) become live/functional.

**Exit criteria:** a full payroll run and a full client invoice can be produced end-to-end from real timesheet/attendance data, with maker-checker approval enforced and the Financial Dashboard reflecting only finalized data.

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

**Goal:** make Veyra enterprise-defensible, not just functional — the features that support scale, trust, and plan-based monetization.

**Scope:**
- Strategic Hierarchy (Goals/Programs) as an opt-in tenant feature (ADR-12).
- Feature-gating enforcement: `CheckFeatureLimit` checks at point-of-creation for every plan-limited resource (`ARCHITECTURE.md` §4) — not just Phase 1's basic plan reference, but active enforcement. This also activates the plan-limit-warning category in the Super Admin Notifications Console, scaffolded but empty since Phase 1.
- Global search across employees/projects/tasks.
- Reports/export (PDF/Excel) for payroll, financial dashboard, attendance.
- Super Admin "impersonate tenant" for support, with mandatory audit logging of every impersonation session (NFR-05).
- Real-time notifications (upgrade from Phase 1's basic list to live push/slide-over updates).
- Revisit open items from `VEYRA_DOCS.md` §16 (multi-currency evaluation, applicant portal auth model) if commercially justified by then.

**Exit criteria:** Veyra operates safely at multi-hundred-tenant scale with enforced plan limits, auditable support access, and reporting/search parity with the enterprise tools it competes against.

---

## Roadmap Governance

- No phase may begin before the prior phase's exit criteria are met and verified, unless explicitly re-approved by updating this document.
- Any request to build a Phase 2+ feature "early" must be evaluated against the risk noted in `VEYRA_DOCS.md` §15 ("Scope creep re-bundling Phase 2/3 into MVP") before proceeding.
