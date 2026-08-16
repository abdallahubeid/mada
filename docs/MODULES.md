# Mada ERP — Module Business Rules

> Part of the Mada ERP documentation set. See `MADA_DOCS.md` for the full Software Design Document and `ARCHITECTURE.md` for tenancy/RBAC foundations referenced below.

## 1. HR & Recruitment Module

**Purpose:** manage the employee lifecycle end-to-end: attract, hire, track presence, manage leave, pay.

### 1.1 Entities

| Sub-module | Key entities | Notes |
|---|---|---|
| Org foundation | `departments`, `work_calendars` (holidays, working days, timezone) | Must exist before Attendance/Payroll can compute correctly. |
| Recruitment/ATS | `job_openings`, `applicants`, `applicant_stages` | Public-facing; Phase 3. |
| Employees & Contracts | `employees`, `employee_contracts` | Two independent axes (ADR-19): `contract_type` = `full_time` \| `part_time` \| `fixed_term` \| `freelance`; `pay_basis` = `salaried` \| `hourly` \| `unpaid`. |
| Attendance | `attendances` | Daily check-in/out. |
| Time Off | `leave_types`, `leave_policies`, `leave_requests`, `leave_balances` | Routed through the generic Approval engine (§4). |

### 1.2 Business Rules

- **BR-301 (amended 2026-08-06, ADR-19):** **`pay_basis`** — not `contract_type` — determines pay computation: `salaried` = fixed base minus absence deductions; `hourly` = payable hours × rate; `unpaid` = no base pay, ad hoc bonuses only. `contract_type` describes the employment *form* (`full_time`/`part_time`/`fixed_term`/`freelance`) and carries **no pay semantics whatsoever**. The two axes cross freely and neither may ever be derived from the other.
  - *Prior wording made `contract_type` the pay driver with values `salaried|hourly|volunteer`. The implemented enum diverged, so the rule was unimplementable as written. The axis is now split rather than one side forced to match the other.*
- **BR-301a:** `base_rate` is interpreted **through** `pay_basis`: for `salaried` it is the gross amount per pay period; for `hourly` it is the amount per hour; for `unpaid` it must be `0`. Storage is minor units (ADR-20). `billing_rate` is the client-chargeable rate — always distinct from `base_rate` (BR-604), nullable, and unused until Phase 2B.
- **BR-301b:** `pay_currency` is frozen on the contract at creation from `org_settings.currency`. It is **never** read live from org settings at payroll time — changing the tenant's currency must not retroactively reinterpret historical contracts or locked payslips.
- **BR-302:** Job opening visibility is either `published` (visible on public careers page) or `closed`.
- **BR-303:** On Applicant status change to `accepted`, the system automatically creates an `Employee` + default `Contract` record and emails login credentials. This is Event-driven (`ApplicantAccepted` → `CreateEmployeeFromApplicant` listener), never inline controller logic.
- **BR-304:** Rejected/withdrawn applicant data is retained for 12 months then anonymized (default; configurable per tenant in Phase 4).
- **BR-305:** Attendance check-in/out is self-service per employee (see BR-701); managers have a read-only team view plus manual correction capability, which is audited.
- **BR-306:** A Leave Request requires a `leave_type` with a configured `leave_policy` (accrual rate, annual entitlement, carry-over rules) before it can be submitted.
- **BR-401 (cross-module):** Approving a Leave Request marks the corresponding calendar days as `excused` in the Work Ledger. Absence deduction logic must never flag an `excused` day as an unexcused absence (ADR-06).
- **BR-402 (amended 2026-08-06, ADR-21):** Unexcused absence days for payroll = Work Calendar workdays in the period − Attendance records − Approved/excused leave days. This calculation is centralized in **exactly one service** (`WorkLedgerReconciler`), never duplicated per feature, and is **materialized** into `work_ledger_entries` rather than computed live. Full rules: §4.2 (BR-403–BR-407).

---

## 2. Operations & Projects Module

**Purpose:** plan and execute work, and produce the payable/billable time data Finance depends on.

### 2.1 Entities

`goals`, `programs` (optional, ADR-12), `projects`, `tasks`, `task_statuses`, `timesheets`.

### 2.2 Business Rules

- **BR-501:** Default task pipeline is `Todo → In Progress → Done`; tenants may add custom statuses per project (not per task) in Phase 2+.
- **BR-502:** Every timesheet entry carries `is_billable` (boolean) independent of the assigned employee's contract type (ADR-07).
- **BR-503:** Strategic Hierarchy (`goals`/`programs`) is a tenant-level opt-in setting; when disabled, Projects attach directly to the tenant with no parent required.
- **BR-504:** Only assigned project members (or roles with `projects.manage`) may log time against a task.

---

## 3. Finance & Payroll Module

**Purpose:** convert HR + Projects data into payroll disbursement and client revenue, with financial visibility for the CEO.

**Scope boundary (unchanged):** Mada's Finance module is **not** a general ledger. There is no chart of accounts, no double-entry bookkeeping, no journals, no trial balance (`PROJECT_VISION.md` §4). "Ledger" in this codebase means the **Work Ledger** — attendance reconciliation — never a financial GL.

### 3.0 Delivery Split (ADR-18)

| | Phase 2A — Payroll | Phase 2B — Revenue |
|---|---|---|
| **Entities** | `approvals`, `work_ledger_entries`, `payroll_runs`, `payslips`, `payslip_line_items`, `payslip_line_item_types`, `payroll_run_adjustments`, `expenses`, `expense_categories` | `clients`, `invoices`, `invoice_line_items`, `invoice_payments`, tax entities (ADR-22) |
| **Depends on** | Attendance, Leave, Work Calendars, Official Holidays, Employee Contracts — **all built** | Projects, Tasks-with-projects, Timesheets — **none built** |
| **Status** | Ready to build | **Blocked** |

Phase 2B may not begin until the Projects & Timesheets module lands. Until then the Financial Dashboard renders the **cost side only** (payroll + expenses); revenue and net-profit tiles are absent, not zeroed — a zero would misread as "no revenue" rather than "not tracked here yet."

### 3.1 Entities

**Phase 2A:** `payroll_runs`, `payslips`, `payslip_line_items`, `payslip_line_item_types`, `payroll_run_adjustments`, `expenses`, `expense_categories` — plus the two shared platform services they are built on, `approvals` and `work_ledger_entries` (§4).

**Phase 2B (deferred):** `clients`, `invoices`, `invoice_line_items`, `invoice_payments`.

> **Naming:** the tenant-facing revenue entity is **`client_invoices` / `ClientInvoice`**, not `invoices`. `tenant_invoices` already exists and means the opposite thing — Mada billing the tenant for its subscription. Two tables named `*invoices*` carrying money in opposite directions is a wrong-model bug waiting to happen (BR-616).

### 3.2 Business Rules

- **BR-601 (amended):** Payslip = Base + sum(allowance line items) − sum(deduction line items), where every allowance/deduction is a typed, tenant-configurable line item — never a hardcoded formula. Line item *types* are tenant-configurable rows (`payslip_line_item_types`), not an application enum, so a tenant can add "housing allowance" or "GOSI deduction" without a code change. All amounts are minor units (ADR-20); rounding occurs once at the payslip total, never per line item.
- **BR-602 (amended):** Absence deductions on a payslip are sourced **exclusively** from `work_ledger_entries` rows with `day_type = absent` for the period (BR-402/BR-403), never from raw attendance and never from a live recomputation at payslip render time.
- **BR-603 (Maker-Checker, ADR-09):** A Payroll Run has states `draft → pending_approval → approved (locked) → paid`. Rejection returns a run from `pending_approval` to `draft` with the reason recorded. Once `approved`, the run and all its payslips and line items are immutable; corrections require a `payroll_run_adjustments` entry in a **subsequent** run referencing the original payslip, never an edit to a locked run.
- **BR-604 *(Phase 2B)*:** Invoices are generated from timesheet entries where `is_billable = true`, grouped by `client` and `project`, at the employee's configured `billing_rate` (distinct from `base_rate`). Timesheet entries are stamped as billed at issue time so they can never be invoiced twice.
- **BR-605:** New employees joining mid-period, or employees offboarded mid-period, have salary/deductions prorated by actual calendar workdays present, not a flat full-period amount. Proration reads the Work Ledger, so a mid-period joiner simply has fewer ledger rows — there is no separate proration calendar.
- **BR-606 (Offboarding):** Employee termination requires: contract end date, final settlement calculation (unused leave payout + prorated final pay), and automatic account/access revocation on the effective date. The settlement is routed through the **same** Approval Engine and maker-checker discipline as a payroll run (ADR-08/ADR-09); revocation fires from a listener on the approved settlement, never inline in a controller.
- **BR-607 (amended):** Financial Dashboard figures are computed from `approved`/`paid` Payroll runs, `approved` Expenses, and — from Phase 2B — `issued`/`paid` Client Invoices only. Never from `draft` data. In Phase 2A the dashboard exposes cost and headcount-cost figures only; Revenue and Net Operating Profit tiles do not render.
- **BR-608 (Snapshot on lock):** When a payroll run transitions to `approved`, every payslip **freezes a snapshot** of: employee name and number, `pay_basis`, `base_rate`, `pay_currency`, scheduled workdays, present days, excused days, absent days, and the resulting deduction. A locked payslip must render identically forever, without joining to `employees`, `employee_contracts`, or `work_ledger_entries`. Editing a contract in September may never change what August's payslip shows.
- **BR-609 (Monetary precision, ADR-20):** Every monetary column is `bigInteger` minor units with an accompanying frozen currency. Rates are unsigned; line item amounts and adjustments are signed. No `float`, `double`, or `decimal` column may hold money.
- **BR-610 (Immutability enforcement, NFR-11):** Immutability is enforced at the **model/observer layer**, not by hiding routes. An observer on `PayrollRun`, `Payslip`, and `PayslipLineItem` throws on `updating`/`deleting`/`restoring` whenever the owning run is `approved` or `paid`. Because `Builder::update()` fires **no** model events, every write path to these tables must go through model instances; mass-update helpers on locked records are forbidden and covered by an explicit failing-path test.
- **BR-611 (Payroll run uniqueness):** At most one non-cancelled payroll run may exist per `(tenant_id, period)`. Enforced by a unique index plus an application guard, so a second run for the same month cannot silently double-pay.
- **BR-612 (Zero-value payslips):** An `unpaid` pay basis still produces a payslip — with a zero base and any ad hoc bonus line items. Employees on unpaid contracts must appear in the run, so headcount reconciliation stays honest.
- **BR-613 (Expense claims):** An expense claim carries a category, amount (minor units), currency, date, optional receipt attachment, and a claimant. It is routed through the Approval Engine (ADR-08). Only `approved`/`paid` expenses reach the dashboard. Approving is a finance permission.
  - **BR-613a (Separation of duties):** The user who submitted a claim may not approve or reject it. Asserted in the actions, not only by permission — an employee approving their own reimbursement is the primary abuse an expense workflow exists to prevent.
  - **BR-613b (Claimable vs settled):** `is_claimable` distinguishes an out-of-pocket claim owed back to a person from a company cost already settled directly. A non-claimable expense still requires approval and still counts as cost, but **may not be disbursed** — marking it paid would assert a reimbursement that never occurred.
  - **BR-613c (Rejection is not terminal):** A rejected claim returns to an editable state and resubmits against the same record, so its approval history stays attached to one subject rather than fragmenting across replacements.
- **BR-621 (End-of-service benefit ⚠️ jurisdiction-dependent):** EOSB is a **statutory** entitlement whose rules differ by jurisdiction, and this documentation set specifies none — BR-606 requires only "unused leave payout + prorated final pay". The common GCC/Saudi tiered pattern (half a month's wage per year for the first five years, a full month per year thereafter, tapered on resignation) ships as the **default**, and accrual is computed per month rather than per whole year. These rates are **not legal advice and must be confirmed against the applicable statute before the module reaches a real customer.** *Amended 2026-08-09 (ADR-23): they are no longer constants — see BR-624.*
- **BR-624 (EOSB rules are tenant configuration):** The tier boundary, both accrual rates, the resignation taper bands, and the nominal working month are stored per tenant in `finance_settings` and reach `OffboardingCalculator` as an `EosbPolicy` value object. The calculator never reads the settings itself — the policy is a **parameter**, which is what keeps it pure (no Eloquent, no clock, no tenant context) and independently testable. A tenant with no row computes on `EosbPolicy::default()`, byte-identical to the constants BR-621 previously described.
- **BR-625 (Rates are basis points, never floats):** Accrual and taper rates are stored and computed as integer **basis points** (5_000 bps = 50%). The settings screen accepts percentages because that is how a statute reads, and the form request converts them by string surgery rather than `round($x * 100)`. ADR-20's no-float rule governs the multiplier as much as the amount: a third of an entitlement is 33.33%, and that figure multiplies straight into a payment.
- **BR-626 (A settlement snapshots its own rules):** `offboarding_settlements.eosb_policy` freezes the exact policy each settlement was computed under, and the settlement screen renders from that snapshot rather than from current settings. Without it, editing a rate would silently invalidate the explanation of every settlement already approved and paid — a locked record must render from its own columns alone (BR-608). Rows predating the column read back as the defaults, which is what they were in fact computed under.
- **BR-627 (Who may set the rules):** `finance.settings.manage` is granted to the **Owner and the Finance Manager only** — never to HR or any self-service bucket. There is deliberately **no maker-checker split** on this screen: it sets rules rather than authorizing a payment, and BR-626's snapshot means an edit can never restate what an approved settlement already paid. Disabling EOSB (`eosb_enabled = false`) zeroes the statutory benefit while leaving the contractual components — leave payout and prorated final salary — intact.
- **BR-622 (Offboarding closes the contract, not just the record):** Disbursing a settlement terminates the employee's **active contract** and deactivates their user account, in the same transaction. Terminating the contract is what actually freezes future payroll — `PayrollRunBuilder` selects contracts by `Active` status, so changing only the employee's status would leave them being paid every month while appearing offboarded in the UI. Accounts are deactivated, never deleted: audit logs, approvals and payslips reference them.
- **BR-623 (Manual settlement inputs ⚠️):** Loan and other deductions on a settlement are **manual inputs**, not derived — the system has no loans or advances entity. Until one exists, the preparer types the outstanding amount and the figure carries no independent verification.
- **BR-614 (Employee payslip visibility):** An employee may read **only** their own payslips, and only those belonging to an `approved` or `paid` run. Draft and `pending_approval` payslips are invisible to the employee under every code path, including direct id access — enforced by Policy, per BR-701's discipline.
- **BR-615 (Separation of duties, ADR-09):** The user who approves a payroll run may not be the user who prepared it. Asserted at the model layer (`approver_id !== maker_id`), because the Owner `Gate::before` bypass grants Owners the `prepare` permission implicitly and a permission check therefore cannot express this constraint. Covered by a test that must fail when the assertion is removed.
- **BR-616 (Naming):** The tenant→client revenue entity is `client_invoices`/`ClientInvoice`. `tenant_invoices`/`TenantInvoice` remains reserved for Mada→tenant subscription billing. Neither may be renamed into the other's namespace.
- **BR-617 (Retention override, tightened 2026-08-06):** A locked payroll run — and every payslip and line item under it — **cannot be deleted at all**, softly or permanently. It never enters the Trash console.
  - *The rule originally read "never force-deletable, restorable but never purgeable." Implementation showed that is not self-consistent: `active_period` (BR-611) is NULL for a soft-deleted run, so trashing an approved run frees its month and lets a second run claim it. Restoring the original would then violate the unique key and fail — leaving a "restorable" record that cannot actually be restored. Blocking deletion outright is the only reading under which BR-611 and BR-617 both hold.*
  - Drafts and cancelled runs delete and restore normally; only `approved` and `paid` are frozen. Enforced by `PayrollRunObserver`, `PayslipObserver` and `PayslipLineItemObserver` on `deleting` and `forceDeleting`.
- **BR-619 (Adjustments must move money, not just record it):** A `payroll_run_adjustments` row is written **together with** a signed line item on the carrying draft run's payslip for the same employee. Recording the correction without the line item would produce an audit trail of corrections that were never actually paid. It follows that an adjustment can only be carried by a run that **includes that employee** — otherwise it has nowhere to land — and only by a run in `draft`.
- **BR-620 (Finance notification routing):** Submission notifies holders of `finance.payroll.approve` **excluding the maker**; approval notifies the maker; disbursement notifies each employee individually, carrying their **own** payslip so the link lands on the only one they may read (BR-614). Recipients resolve by permission, never role name, so a custom tenant role holding the ability is included automatically.
- **BR-618 (Ledger emptiness is its own guard):** A payroll run may not be opened for a period whose Work Ledger holds **no rows at all**. BR-405's unresolved-day check counts `workday` sentinels, so an empty ledger passes it trivially — and with `period_scheduled_days = 0` the calculator falls back to the full base rate and computes no absence deduction, silently paying every employee in full. Emptiness and incompleteness are different failures and need separate guards.

---

## 4. Platform Services (shared across modules)

| Service | Responsibility | Used by |
|---|---|---|
| **Approval Engine** (ADR-08) | Generic polymorphic request → approve/reject → status change → downstream event. Materialized as `approvals`. | Leave Requests, Payroll finalization, Offboarding settlement, Job Offers, Expense claims. |
| **Work Ledger** (ADR-21) | Reconciles Work Calendar vs. Attendance vs. approved Leave into one materialized row per employee per date. Sole source of absence deductions. | Payroll (BR-602), HR Dashboard absence metrics, Attendance reporting. |
| Notifications | In-app (drawer) + email delivery of events (leave approved, payroll ready, new applicant, tenant approved, etc.). Recipients resolved via `TenantNotifier` by **permission**, never hardcoded to a role. | All modules. |
| Activity/Audit Log | Immutable record of who changed what, when. | HR, Finance, RBAC — mandatory before Payroll ships. |
| Document Storage | Tenant-isolated file storage (CVs, contracts, logos, payslips) via MediaLibrary, signed URLs (ADR-13). | HR, Finance, Onboarding. |
| Org Settings | Work calendar, currency, department list, company profile. | Prerequisite for Attendance/Payroll (BR-402). |

> **ADR-13 deviation on record:** MediaLibrary is not installed. Files currently use path columns on a `custom` disk. Payslip PDFs are specified as tenant-isolated, signed, time-limited URLs — a security requirement, not a convenience. This must be resolved before payslip documents ship; it is tracked as an open item, not silently accepted.

### 4.1 Approval Engine rules (ADR-08)

- **BR-901 (One engine):** Every approve/reject workflow in the product uses `approvals`. No module may add approval-state columns to its own table. The three bespoke columns on `leave_requests` (`requires_manager_escalation`, `approval_level`, `current_approval_level`) are migrated into `approvals` and dropped in Phase 2A.
- **BR-902 (Polymorphic target):** An approval points at its subject via `approvable_type` / `approvable_id`. `approvable_type` stores a **short morph-map alias** (`leave_request`, `payroll_run`, `expense`, `offboarding_settlement`), never a fully-qualified class name — a class rename or namespace move must not orphan a financial record. The map is registered non-enforcing in Phase 2A. `Relation::enforceMorphMap()` throws on write for any unmapped model, and `notifications.notifiable_type` currently stores FQCNs, so enforcement is a separate step gated on mapping every polymorphic model and backfilling that column.
- **BR-903 (Multi-level):** `level` is the total number of decisions required; `current_level` is the one awaiting a decision. A decision at `current_level < level` advances the chain and leaves status `pending`; a decision at the final level is terminal. Rejection is terminal at any level.
- **BR-904 (One open approval per subject):** A subject may have at most one non-terminal approval at a time. MySQL cannot express this as a partial unique index, so it is an application-layer guard inside the creating transaction, covered by a test.
- **BR-905 (Decision recording):** Every terminal decision records `decided_by`, `decided_at`, and — for rejections — a non-empty `reason`. Approvals are audit-logged via `TenantAuditor` under the module of their subject.
- **BR-906 (Downstream effects):** The engine changes approval state and emits an event. It never mutates the subject's own domain state directly — the subject's listener does that. This keeps the engine free of per-module knowledge and preserves the cross-module boundary in `ARCHITECTURE.md` §7.

### 4.2 Work Ledger rules (ADR-21)

- **BR-403 (Day classification):** Each `work_ledger_entries` row classifies one employee-date into exactly one mutually-exclusive `day_type`, resolved in this precedence order: `holiday` → `weekend` → `excused` → `present` → `absent`. A scheduled working day not yet resolved is `workday` (see BR-405).
- **BR-404 (Deduction source):** **Only `day_type = absent` is deductible.** `excused` (approved leave, BR-401), `holiday`, and `weekend` never produce a deduction under any code path.
- **BR-405 (`workday` is a sentinel):** `workday` means "scheduled working day, not yet reconciled" — a future date inside an open period, or an un-reconciled backfill. **A payroll run may not be generated while any `workday` row exists in its period**; the run asserts this before producing payslips and fails loudly rather than treating unresolved days as present.
- **BR-406 (Idempotent rebuild):** Reconciliation is rebuild-safe: hard-delete and re-insert the employee-period inside one transaction. Running it twice produces identical rows. It is a derived projection and therefore the documented exception to NFR-10 (ADR-21) — the sources it derives from remain soft-deleted, and nothing is lost.
- **BR-407 (Locked periods are frozen):** A period covered by an `approved` or `paid` payroll run **cannot be rebuilt**. The reconciler checks for a locked run over the date range and refuses. This is what makes the ledger safe to rebuild everywhere else: the payslip snapshot (BR-608) has already frozen the numbers that mattered.

---

## 5. Employee Workspace Permissions — BR-701

This rule is a **hard authorization boundary**, enforced via Laravel Policy, not merely a UI restriction. It defines the entire operational surface available to the `Employee` role.

### 5.1 Check-In / Check-Out

- A regular Employee has a single, dedicated, streamlined action to record **Check-In** and **Check-Out** against their own current-date attendance record.
- No employee may view, create, or modify another employee's attendance record.
- Managers (`HR Manager`, `Owner`) retain read access to team attendance and audited manual-correction rights (BR-305) — this is a separate, higher-privilege capability, not part of the Employee role.

### 5.2 Task Visibility & Tracking

- An Employee's task list/board is scoped strictly to tasks where `assigned_to = current_user`, across every project they are a member of.
- Employees may **filter** their own task list (by status, project, due date) and **update status** on their own assigned tasks (e.g., move Todo → In Progress → Done), and log timesheet entries against them.
- Employees have **no create, reassign, or delete rights** on tasks, and **no visibility into unassigned tasks or tasks assigned to other employees**, regardless of project membership.
- This scope applies identically across Kanban board view and list view — there is no "admin view" toggle available to the Employee role.

### 5.3 Enforcement Requirement

Both restrictions above must be enforced at the Policy/query-scope level (e.g., a scoped Eloquent query on `tasks` and `attendances`, checked by a Policy before any controller/Livewire action executes) — never solely by hiding UI elements. This is testable and must have explicit automated test coverage (unauthorized cross-employee access attempts must return 403, not empty results, per `ARCHITECTURE.md` §5).

---

## 6. Super Admin / Platform Console Module

**Purpose:** the operational surface for Mada's own platform operators (`users` rows with `tenant_id = null`) to run the business — tenant lifecycle decisions, platform-wide configuration, system health visibility, and tenant support — kept strictly separate from any single tenant's data and its `TenantContext`/global-scope machinery.

### 6.1 Entities

| Sub-module | Key entities | Notes |
|---|---|---|
| Platform Settings | `platform_settings` | Singleton, platform-level configuration row/key-value store; **no `tenant_id`** — never queried through the tenant global scope. Sensitive fields (SMTP credentials, payment gateway keys) are encrypted at rest (ADR-16). |
| Notifications Console | reuses `notifications` (§4) | Adds platform-level alert categories surfaced only to Super Admin users; not a new table — see BR-804. |
| Support Inquiries | `support_threads`, `support_messages` | A tenant Owner/CEO-initiated conversation with Mada support. Deliberately **not** routed through the generic Approval Engine (ADR-08) — a support inquiry is a conversation, not an approve/reject decision (ADR-17). |
| Super Admin User Management | `users` (`tenant_id = null`), `admin_invitations`, `permissions`/`roles` (Spatie, platform guard) | Manages the platform operator accounts themselves. Provisioned by invitation only; mandatory 2FA (ADR-14). Two operator tiers exist in v1: **Super Admin** (full, non-configurable access) and **Support Admin** (least-privilege, granular per-permission set from a fixed catalog) — see BR-807/BR-808. |

### 6.2 Business Rules

- **BR-801:** `platform_settings` is platform-wide, never tenant-scoped. Only the Super Admin role may read or write it; it is never resolved through `TenantContext`.
- **BR-802:** Sensitive `platform_settings` values (SMTP credentials, payment gateway API keys) are stored encrypted at rest and are never re-displayed in plaintext once saved — the UI only ever shows a "configured / not configured" state plus a "replace value" action (ADR-16).
- **BR-803:** The global registration-approval toggle (`platform_settings.auto_approve_tenants`) determines whether a tenant transitions `pending_approval → active` automatically on email verification, or waits for explicit Super Admin approval per BR-205. Default is manual approval. Changing this setting is itself an audit-logged action (NFR-05).
- **BR-804:** The Notifications Console surfaces exactly four platform alert categories: new tenant pending approval, security flags (e.g., repeated failed 2FA attempts on a Super Admin account), failed background jobs, and plan-limit warnings — the last category only populates once Phase 4's `CheckFeatureLimit` enforcement ships (`ARCHITECTURE.md` §4). Read/unread state and bulk-clear are per-Super-Admin-user, not platform-global.
- **BR-805:** A Support Thread is created only by a tenant's `Owner` role (never a plain Employee or other tenant role) and always carries the originating `tenant_id`. Super Admin access to a thread is an explicit, audited cross-tenant read, consistent with Tenant Detail access (`ARCHITECTURE.md` §1.2 point 1 and §8).
- **BR-806:** A Support Thread has states `open → in_progress → resolved`. Only Super Admin may change status; a tenant Owner replying to a `resolved` thread automatically reverts it to `open`.
- **BR-807:** Platform operator accounts are provisioned **by invitation only** — an existing Super Admin invites by email; the invitee receives a signed, time-limited link (`admin_invitations`) to set a password and complete **mandatory 2FA enrollment** (ADR-14) before the account becomes `active`. There is no self-service operator signup. Operators come in **two tiers**:
    - **Super Admin** — full, non-configurable platform access. Every permission is implicitly granted and cannot be toggled off; only a Super Admin may invite operators, edit roles, or change platform settings.
    - **Support Admin** — a **least-privilege** account whose authority is an explicit, per-account subset drawn from a fixed permission catalog. The catalog holds eight permissions grouped by module: **Tenants** — `view_tenants`, `manage_tenants`; **Plans** — `view_plans`, `manage_plans`; **Support** — `reply_support`, `manage_support`; **Audit Log** — `view_audit_log`; **Platform Settings** — `manage_settings`. The inviting Super Admin selects which permissions to grant when creating or editing the account; changing an operator's role or permission set is an audit-logged action (NFR-05).

    > **Implemented names (2026-08-10).** `PlatformPermissionCatalog` uses `resource.action` dot-notation throughout, so the catalog above reads as `tenants.view_any`/`tenants.view` for *view_tenants* and `tenants.manage` for *manage_tenants*. The Tenants group carries one extra permission this list did not anticipate: `tenants.update`, which gates **only** the marketing opt-in form on Tenant Detail. It is deliberately not the same grant as `tenants.manage` — toggling a customer's logo onto the landing page and locking every one of their users out of the product are different powers, and the four lifecycle transitions (BR-205, BR-207, BR-209) all sit behind the latter.
- **BR-808:** Platform lockout is prevented by two hard, server-enforced safeguards: (1) any operator can never suspend, revoke, or delete their **own** account; (2) the **last remaining active Super Admin** (full-access tier — Support Admins do not count) can never be suspended, revoked, or downgraded to Support Admin. Every invite, role/permission change, suspend, reactivate, and revoke action on an operator account is audit-logged (NFR-05).
