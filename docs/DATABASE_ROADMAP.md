# Veyra ERP — Database Roadmap

> Part of the Veyra ERP documentation set. Conceptual schema reference — no DDL/code by design (per project convention). See `ARCHITECTURE.md` for tenancy enforcement and `MODULES.md` for the business rules these entities support.

## 1. Global Conventions (apply to every table below)

1. **`tenant_id` column** on every tenant-scoped table, always the **leading column** in its primary composite index (e.g., index on `(tenant_id, status)`, `(tenant_id, employee_id, date)`), per NFR-06.
2. **Soft deletes** (`deleted_at`) required on all HR and Finance records — `employees`, `employee_contracts`, `payroll_runs`, `payslips`, `invoices`, `expenses`, `leave_requests`. Hard deletes are not permitted on these tables (NFR-10).
3. **Audit columns**: `created_by`, `updated_by` on every mutable business record, feeding the Activity/Audit Log.
4. **Timestamps** (`created_at`, `updated_at`) on all tables per Laravel convention.
5. **Polymorphic tables** used for two cross-cutting concerns (never duplicated per-module):
   - **Approvals** — a single polymorphic `approvable_type` / `approvable_id` table backs the generic Approval Engine (ADR-08), reused by Leave Requests, Payroll Run approval, Job Offers, and Expense claims.
   - **Media/Attachments** — a single polymorphic media table (via `spatie/laravel-medialibrary`) backs all file attachments (CVs, company logos, contracts, payslip PDFs), with tenant-isolated storage paths and signed URLs (ADR-13).

## 2. Entity Groups by Module

### 2.1 Foundation (build first — everything else depends on this)

- `tenants` — status (5-state lifecycle, see `ARCHITECTURE.md` §3), plan reference, slug. **Marketing opt-in (see `MARKETING_CMS.md`):** `show_on_marketing` (bool, default false), `marketing_logo_path` (nullable) for the public Trusted By ticker.
- `users` — nullable `tenant_id` (null for Super Admin), role assignment via Spatie Teams.
- `roles` / `permissions` / team pivot — Spatie Permission tables, Teams-scoped by `tenant_id` (ADR-03).
- `plans` / `plan_features` — SaaS subscription plan definitions and feature limits, referenced by tenant. Also powers public pricing (`MARKETING_CMS.md` maps from current `config/plans.php`).
- `subscriptions` — tenant's own billing relationship to Veyra (Phase 2 dependency).
- `platform_settings` — singleton / key-value platform-wide configuration (branding, SMTP, payment gateway keys, registration auto-approval toggle, legal documents, **and marketing CMS JSON groups** `marketing.*` — see `MARKETING_CMS.md`). **No `tenant_id`** — never queried through the tenant global scope. Sensitive fields encrypted at rest (`ARCHITECTURE.md` §8, `MODULES.md` BR-801/BR-802).
- `faqs` — platform-global FAQ rows for the marketing site (`category`, `question`, `answer`, `sort_order`, `is_published`). **No `tenant_id`.** See `MARKETING_CMS.md`.
- `testimonials` — curated marketing success stories (`quote`, client attribution fields, `sort_order`, `is_published`, optional nullable `tenant_id` for attribution only). **Not tenant-scoped via global scope.** See `MARKETING_CMS.md`.
- `admin_invitations` — pending Super Admin invites: email, signed token, expiry, `invited_by`, accepted-at. **No `tenant_id`** (Super Admins are platform-level). Consumed when the invitee sets a password and enrolls 2FA (`MODULES.md` BR-807).
- `org_settings` — currency, timezone, company profile, per tenant (one row per tenant).
- `work_calendars` — working days, public holidays, per tenant.
- `departments` — per tenant.

### 2.2 HR & Recruitment

- `employees` — linked to `users`, `department_id`.
- `employee_contracts` — contract type (`salaried` | `hourly` | `volunteer`), base rate, billing rate, start/end date (BR-301, BR-606).
- `attendances` — `employee_id`, `date`, check-in/check-out timestamps (indexed `(tenant_id, employee_id, date)`).
- `leave_types` — per tenant (annual, sick, unpaid, etc.).
- `leave_policies` — accrual rate, entitlement, carry-over rules per `leave_type`.
- `leave_requests` — routed through the polymorphic Approval table (§1.5); on approval, updates `leave_balances` and the Work Ledger reconciliation used by BR-401/BR-402.
- `leave_balances` — running balance per employee per leave type.
- `job_openings` — Phase 3; `status` (`published` | `closed`).
- `applicants` / `applicant_stages` — Phase 3; not linked to `users` (BR-303 creates the `employees` row only on acceptance).

### 2.3 Operations & Projects

- `goals` / `programs` — optional per-tenant (ADR-12/BR-503), only populated if the tenant opts in to Strategic Hierarchy.
- `projects` — optionally linked to a `program_id`; always linked to a `client_id` when billable (see §2.4).
- `tasks` — `project_id`, `assigned_to` (employee), `status` (custom per project, BR-501).
- `task_statuses` — per-project custom pipeline definitions (default seeded: Todo/In Progress/Done).
- `timesheets` — `task_id`, `employee_id`, hours, **`is_billable`** flag (BR-502, ADR-07) — independent of the employee's contract type.

### 2.4 Finance & Payroll

- `clients` — per tenant, linked from `projects` for invoicing (BR-604).
- `payroll_runs` — period, `status` (`draft → pending_approval → approved → paid`), maker/checker user references (BR-603).
- `payslips` — one per employee per `payroll_run`, immutable once the run is `approved` (NFR-11).
- `payslip_line_items` — typed allowance/deduction rows (BR-601) — flexible, never a hardcoded formula.
- `invoices` / `invoice_line_items` — generated from billable timesheets, grouped by client/project (BR-604).
- `expenses` — operational expense records, routed through the Approval Engine.

### 2.5 Platform Services (cross-cutting)

- `approvals` (polymorphic) — see §1.5.
- `notifications` — in-app + email delivery record per user/event. Powers both the tenant-facing notification list and the Super Admin's Notifications Console (`MODULES.md` BR-804) — same table, filtered by recipient, not a separate schema.
- `activity_log` — immutable audit trail (via `spatie/laravel-activitylog` or equivalent), covering RBAC changes, financial record changes, and Super Admin impersonation events (NFR-05).
- `media` (polymorphic, via MediaLibrary) — see §1.5.
- `support_threads` — `tenant_id` (originating tenant, always set), `status` (`open` \| `in_progress` \| `resolved`), created-by (tenant Owner) (`MODULES.md` BR-805/BR-806).
- `support_messages` — `support_thread_id`, sender (`user_id`, may be a tenant Owner or a Super Admin), body, timestamps.

## 3. Indexing Strategy Summary

| Query pattern | Required index |
|---|---|
| Any tenant-scoped list/filter query | `(tenant_id, ...)` composite, tenant_id leading |
| Attendance lookups | `(tenant_id, employee_id, date)` |
| Timesheet aggregation for payroll/invoicing | `(tenant_id, employee_id, task_id, is_billable)` |
| Payroll run lookups | `(tenant_id, period, status)` |
| Approval engine lookups | `(tenant_id, approvable_type, approvable_id, status)` |
| Role/permission resolution | `(tenant_id/team_id, model_id)` per Spatie Teams convention |
| Support thread lookups (tenant side and Super Admin console) | `(tenant_id, status)` |

## 4. Data Retention Policy

| Data | Retention rule |
|---|---|
| Cancelled tenant data | Retained 90 days (recoverable), then purged (NFR-12). |
| Rejected/withdrawn applicant data | Anonymized after 12 months (BR-304, NFR-13). |
| Locked payroll runs | Never purged; permanent financial record (soft-delete only, NFR-10/NFR-11). |
| Activity/Audit Log | Retained indefinitely (compliance requirement) — purge policy to be defined only if/when required by a specific regulatory scope. |
| Support threads/messages | Follow the retention of their parent tenant — purged on the same 90-day schedule as cancelled tenant data (NFR-12), not retained independently. |
