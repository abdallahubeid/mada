# Veyra ERP — Module Business Rules

> Part of the Veyra ERP documentation set. See `VEYRA_DOCS.md` for the full Software Design Document and `ARCHITECTURE.md` for tenancy/RBAC foundations referenced below.

## 1. HR & Recruitment Module

**Purpose:** manage the employee lifecycle end-to-end: attract, hire, track presence, manage leave, pay.

### 1.1 Entities

| Sub-module | Key entities | Notes |
|---|---|---|
| Org foundation | `departments`, `work_calendars` (holidays, working days, timezone) | Must exist before Attendance/Payroll can compute correctly. |
| Recruitment/ATS | `job_openings`, `applicants`, `applicant_stages` | Public-facing; Phase 3. |
| Employees & Contracts | `employees`, `employee_contracts` | Contract types: `salaried`, `hourly`, `volunteer`. |
| Attendance | `attendances` | Daily check-in/out. |
| Time Off | `leave_types`, `leave_policies`, `leave_requests`, `leave_balances` | Routed through the generic Approval engine (§4). |

### 1.2 Business Rules

- **BR-301:** Contract type determines pay computation: `salaried` = fixed base minus absence deductions; `hourly` = payable hours × rate; `volunteer` = no base pay, ad hoc bonuses only.
- **BR-302:** Job opening visibility is either `published` (visible on public careers page) or `closed`.
- **BR-303:** On Applicant status change to `accepted`, the system automatically creates an `Employee` + default `Contract` record and emails login credentials. This is Event-driven (`ApplicantAccepted` → `CreateEmployeeFromApplicant` listener), never inline controller logic.
- **BR-304:** Rejected/withdrawn applicant data is retained for 12 months then anonymized (default; configurable per tenant in Phase 4).
- **BR-305:** Attendance check-in/out is self-service per employee (see BR-701); managers have a read-only team view plus manual correction capability, which is audited.
- **BR-306:** A Leave Request requires a `leave_type` with a configured `leave_policy` (accrual rate, annual entitlement, carry-over rules) before it can be submitted.
- **BR-401 (cross-module):** Approving a Leave Request marks the corresponding calendar days as `excused` in the Work Ledger. Absence deduction logic must never flag an `excused` day as an unexcused absence (ADR-06).
- **BR-402:** Unexcused absence days for payroll = Work Calendar workdays in the period − Attendance records − Approved/excused leave days. This calculation is centralized in one service, never duplicated per feature.

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

### 3.1 Entities

`clients`, `payroll_runs`, `payslips`, `payslip_line_items`, `invoices`, `invoice_line_items`, `expenses`.

### 3.2 Business Rules

- **BR-601:** Payslip = Base + sum(allowance line items) − sum(deduction line items), where every allowance/deduction is a typed, tenant-configurable line item — never a hardcoded formula.
- **BR-602:** Absence deductions on a payslip are sourced exclusively from the reconciled Work Ledger (BR-402), never raw attendance.
- **BR-603 (Maker-Checker, ADR-09):** A Payroll Run has states `draft → pending_approval → approved (locked) → paid`. Once `approved`, line items are immutable; corrections require a new adjustment entry in the following run, never an edit to a locked run.
- **BR-604:** Invoices are generated from timesheet entries where `is_billable = true`, grouped by `client` and `project`, at the employee's configured billing rate (distinct from their pay rate).
- **BR-605:** New employees joining mid-period, or employees offboarded mid-period, have salary/deductions prorated by actual calendar workdays present, not a flat full-period amount.
- **BR-606 (Offboarding):** Employee termination requires: contract end date, final settlement calculation (unused leave payout + prorated final pay), and automatic account/access revocation on the effective date.
- **BR-607:** Financial Dashboard figures (Revenue, Expenses+Payroll, Net Operating Profit) are computed from `approved`/`paid` Payroll runs and `issued` Invoices only — never from `draft` data.

---

## 4. Platform Services (shared across modules)

| Service | Responsibility | Used by |
|---|---|---|
| **Approval Engine** (ADR-08) | Generic polymorphic request → approve/reject → status change → downstream event. | Leave Requests, Payroll finalization, Job Offers, Expense claims. |
| Notifications | In-app (drawer) + email delivery of events (leave approved, payroll ready, new applicant, tenant approved, etc.). | All modules. |
| Activity/Audit Log | Immutable record of who changed what, when. | HR, Finance, RBAC — mandatory before Payroll ships. |
| Document Storage | Tenant-isolated file storage (CVs, contracts, logos, payslips) via MediaLibrary, signed URLs (ADR-13). | HR, Finance, Onboarding. |
| Org Settings | Work calendar, currency, department list, company profile. | Prerequisite for Attendance/Payroll (BR-402). |

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

**Purpose:** the operational surface for Veyra's own platform operators (`users` rows with `tenant_id = null`) to run the business — tenant lifecycle decisions, platform-wide configuration, system health visibility, and tenant support — kept strictly separate from any single tenant's data and its `TenantContext`/global-scope machinery.

### 6.1 Entities

| Sub-module | Key entities | Notes |
|---|---|---|
| Platform Settings | `platform_settings` | Singleton, platform-level configuration row/key-value store; **no `tenant_id`** — never queried through the tenant global scope. Sensitive fields (SMTP credentials, payment gateway keys) are encrypted at rest (ADR-16). |
| Notifications Console | reuses `notifications` (§4) | Adds platform-level alert categories surfaced only to Super Admin users; not a new table — see BR-804. |
| Support Inquiries | `support_threads`, `support_messages` | A tenant Owner/CEO-initiated conversation with Veyra support. Deliberately **not** routed through the generic Approval Engine (ADR-08) — a support inquiry is a conversation, not an approve/reject decision (ADR-17). |
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
- **BR-808:** Platform lockout is prevented by two hard, server-enforced safeguards: (1) any operator can never suspend, revoke, or delete their **own** account; (2) the **last remaining active Super Admin** (full-access tier — Support Admins do not count) can never be suspended, revoked, or downgraded to Support Admin. Every invite, role/permission change, suspend, reactivate, and revoke action on an operator account is audit-logged (NFR-05).
