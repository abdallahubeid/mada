# Veyra ERP — Database Roadmap

> Part of the Veyra ERP documentation set. Conceptual schema reference — no DDL/code by design (per project convention). See `ARCHITECTURE.md` for tenancy enforcement and `MODULES.md` for the business rules these entities support.

## 1. Global Conventions (apply to every table below)

1. **`tenant_id` column** on every tenant-scoped table, always the **leading column** in its primary composite index (e.g., index on `(tenant_id, status)`, `(tenant_id, employee_id, date)`), per NFR-06.
2. **Soft deletes** (`deleted_at`) required on all HR and Finance records — `employees`, `employee_contracts`, `payroll_runs`, `payslips`, `invoices`, `expenses`, `leave_requests`. Hard deletes are not permitted on these tables (NFR-10).
3. **Audit columns**: `created_by`, `updated_by` on every mutable business record, feeding the Activity/Audit Log.
4. **Timestamps** (`created_at`, `updated_at`) on all tables per Laravel convention.
5. **Polymorphic tables** used for two cross-cutting concerns (never duplicated per-module):
   - **Approvals** — a single polymorphic `approvable_type` / `approvable_id` table backs the generic Approval Engine (ADR-08), reused by Leave Requests, Payroll Run approval, Offboarding settlement, Job Offers, and Expense claims.
   - **Media/Attachments** — a single polymorphic media table (via `spatie/laravel-medialibrary`) backs all file attachments (CVs, company logos, contracts, payslip PDFs), with tenant-isolated storage paths and signed URLs (ADR-13).
6. **Money is `bigInteger` minor units** (ADR-20). No `float`, `double`, or `decimal` column may hold a monetary value. Rates are `unsignedBigInteger`; amounts that can legitimately be negative (deductions, adjustments, credit notes) are signed `bigInteger`. Every monetary column travels with an explicit currency column frozen on the record — never resolved live from `org_settings` at read time.
7. **Polymorphic type columns store morph-map aliases**, never fully-qualified class names. `Relation::enforceMorphMap()` is registered in `AppServiceProvider`, so a namespace move can never orphan a stored financial reference.
8. **Derived projections** (currently only `work_ledger_entries`) are the sole documented exception to rule 2's soft-delete requirement — they are rebuilt by hard-delete-and-reinsert and are fully reconstructible from their sources (ADR-21). Every other table follows rule 2.

## 2. Entity Groups by Module

### 2.1 Foundation (build first — everything else depends on this)

- `tenants` — status (6-state lifecycle, see `ARCHITECTURE.md` §3), plan reference, slug. **Marketing opt-in (see `MARKETING_CMS.md`):** `show_on_marketing` (bool, default false), `marketing_logo_path` (nullable) for the public Trusted By ticker. **Lifecycle audit columns:** `activated_at`/`reviewed_at`/`reviewed_by`/`rejection_reason` record the one-time registration decision (BR-205, BR-207); `suspended_at`/`suspension_reason`/`suspended_by` record the **current** suspension only and are cleared on reactivation (BR-209). The two sets are deliberately separate — suspension is repeatable, so reusing the review columns would let a later suspension destroy the record of who originally approved the tenant. Full transition history lives in `platform_audit_logs`.
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
- `employee_contracts` — **two independent axes (ADR-19)**: `contract_type` (`full_time` \| `part_time` \| `fixed_term` \| `freelance`, employment form, no pay semantics) and `pay_basis` (`salaried` \| `hourly` \| `unpaid`, the sole pay-computation input). Plus `base_rate` (unsigned bigInteger minor units), `billing_rate` (nullable unsigned bigInteger minor units, reserved for Phase 2B), `pay_currency` (3-char ISO, frozen at creation from `org_settings.currency` — BR-301b), `start_date`, `end_date`, `probation_end_date`, `status` (BR-301, BR-301a, BR-606).
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

**Phase 2A — Payroll (ADR-18, ready to build):**

- `payroll_runs` — `period` (`YYYY-MM`), `status` (`draft → pending_approval → approved → paid`, plus `cancelled`), `maker_id` / `approver_id` user references, `approved_at`, `paid_at`, `rejection_reason`, `currency`, snapshot totals in minor units (BR-603).
  **BR-611 is enforced by a stored generated column**, `active_period` = `if(deleted_at is null and status <> 'cancelled', period, null)`, unique with `tenant_id`. A plain unique on `(tenant_id, period)` would permanently burn a period once a draft is cancelled, and including `deleted_at` in the key does not work at all — MySQL/MariaDB treat NULLs as distinct, so two live rows would both pass. Here that same NULL-distinctness is the mechanism: any number of cancelled or soft-deleted runs may coexist, but only one live run can hold a period.

> **Monetary sign convention (module-wide).** Every monetary column across `payroll_runs`, `payslips` and `payslip_line_items` is a **signed** value in minor units expressed as its **effect on net pay** — positive adds, negative subtracts. So `net = base + absence_deduction + allowances_total + deductions_total`, where the last two are always `<= 0`. One rule for the whole module; the display layer negates for presentation. This avoids the usual magnitude-vs-sign confusion where a "deduction total" of 500 might mean −500 or +500 depending on which developer wrote the query.
- `payslips` — one per employee per `payroll_run`; immutable once the run is `approved` (NFR-11, BR-610). Carries the **frozen snapshot** required by BR-608: employee name/number, `pay_basis`, `base_rate`, `pay_currency`, scheduled/present/excused/absent day counts, computed deduction, gross, net — all minor units. A locked payslip renders without joining `employees`, `employee_contracts`, or `work_ledger_entries`.
- `payslip_line_items` — typed allowance/deduction rows (BR-601). Signed `bigInteger` amount in minor units; references a `payslip_line_item_type_id`.
- `payslip_line_item_types` — **tenant-configurable** allowance/deduction definitions (name, `kind` = allowance\|deduction, default amount, taxable flag reserved for 2B). BR-601 requires these be data, not an application enum, so a tenant can add "housing allowance" or "GOSI deduction" without a code change.
- `payroll_run_adjustments` — corrections to a **locked** run, recorded against a subsequent run and referencing the original `payslip_id` (BR-603). This is the only legal correction path; a locked run is never edited.
- `expenses` — operational/claim expense records: category, amount (minor units), currency, date, claimant, optional receipt. Routed through the Approval Engine (BR-613).
- `expense_categories` — per tenant; required for any meaningful cost breakdown on the dashboard.
- `finance_settings` — **tenant-scoped singleton** (unique on `tenant_id`), same shape as `org_settings`. Holds the end-of-service rules (ADR-23, BR-624): `eosb_enabled`, `eosb_tier_boundary_months`, `eosb_lower_tier_bps`, `eosb_upper_tier_bps`, a JSON `eosb_resignation_taper` (`[{months, bps}]`, ascending), and the nominal working month (`nominal_month_days`, `nominal_day_hours`). Rates are **basis points as integers**, never percentages or floats (BR-625) — the same rule `tax_rates.rate_bps` follows in §5.1. Deliberately **not pre-seeded**: a tenant with no row computes on the shipped defaults, and writing a row on first read would misrepresent a default as a configured decision.
- `offboarding_settlements.eosb_policy` — nullable JSON snapshot of the rules each settlement was computed under (BR-626). The taper is JSON rather than columns because the number of bands is jurisdictional; the snapshot is nullable because rows predating it were computed on the defaults.

**Phase 2B — Revenue (ADR-18, blocked on Projects & Timesheets):**

- `clients` — per tenant, linked from `projects` for invoicing (BR-604).
- `client_invoices` / `client_invoice_line_items` — generated from billable timesheets, grouped by client/project (BR-604). **Named `client_invoices`, not `invoices`** — `tenant_invoices` already means Veyra billing the tenant, the opposite money direction (BR-616). Per-tenant gapless numbering sequence, number assigned at **issue**, never at draft. Must carry line-level tax from the first migration (ADR-22, §5).
- `client_invoice_payments` — an invoice may be settled in instalments; a single `paid` flag cannot express partial payment.

### 2.5 Platform Services (cross-cutting)

- `approvals` (polymorphic) — see §1.5 and `MODULES.md` §4.1. `tenant_id`, `approvable_type` (morph-map alias) / `approvable_id`, `status`, `level`, `current_level`, `requested_by`, `decided_by`, `decided_at`, `reason`, soft deletes. Backs Leave, Payroll finalization, Expenses, and Offboarding settlement. Leave's three bespoke escalation columns migrate here in Phase 2A and are dropped (BR-901).
- `work_ledger_entries` — **derived projection** (ADR-21, `MODULES.md` §4.2). One row per `(tenant_id, employee_id, date)`, unique on that triple. `day_type` (`workday` \| `weekend` \| `holiday` \| `excused` \| `present` \| `absent`), `source` (which reconciliation input produced the classification), nullable `attendance_id` / `leave_request_id` provenance FKs, `worked_minutes` (integer — never float hours, since it multiplies into money on hourly contracts). **No soft deletes** — rebuilt by hard-delete-and-reinsert per §1.8. Sole source of absence deductions (BR-602/BR-404).
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
| Approval engine lookups | `(tenant_id, approvable_type, approvable_id, status)` — **not** Laravel's default `morphs()` index, which leads with `approvable_type` and violates NFR-06 |
| Approval inbox ("what awaits my decision") | `(tenant_id, status, current_level)` |
| Work Ledger period reconciliation | unique `(tenant_id, employee_id, date)`; plus `(tenant_id, date, day_type)` for org-wide absence rollups |
| Payslip lookup by employee (self-service) | `(tenant_id, employee_id, payroll_run_id)` |
| Role/permission resolution | `(tenant_id/team_id, model_id)` per Spatie Teams convention |
| Support thread lookups (tenant side and Super Admin console) | `(tenant_id, status)` |

## 4. Data Retention Policy

| Data | Retention rule |
|---|---|
| Cancelled tenant data | Retained 90 days (recoverable), then purged (NFR-12). |
| Rejected/withdrawn applicant data | Anonymized after 12 months (BR-304, NFR-13). |
| Locked payroll runs | Never purged; permanent financial record (soft-delete only, NFR-10/NFR-11). **Excluded from Trash force-delete/empty even for an Owner** — `TrashableResourceCatalog` must express "restorable but never purgeable" as a first-class state (BR-617). |
| Work Ledger entries | Not retained as a record — a derived projection, rebuilt on demand (ADR-21). Purged freely with their period, except where a locked payroll run covers the range (BR-407). The durable copy of what mattered lives in the payslip snapshot (BR-608). |
| Approvals | Follow the retention of their subject. An approval attached to a locked payroll run inherits that run's never-purge rule. |
| Activity/Audit Log | Retained indefinitely (compliance requirement) — purge policy to be defined only if/when required by a specific regulatory scope. |
| Support threads/messages | Follow the retention of their parent tenant — purged on the same 90-day schedule as cancelled tenant data (NFR-12), not retained independently. |

## 5. Tax / VAT — Reserved Future Spec (ADR-22)

**Status: reserved, not built.** No tax column ships in Phase 2A — payroll carries no VAT. This section exists so that Phase 2B's invoicing schema is designed with tax in place rather than retrofitted onto issued, immutable invoices, which is materially harder.

**Why it is not optional:** the primary market is MENA. KSA (15%) and UAE (5%) operate VAT regimes with statutory invoice-content requirements. An invoicing module that cannot express VAT is not sellable there.

### 5.1 Entities to add in Phase 2B

- `tax_rates` — per tenant: name, `rate_bps` (basis points — integer, so 15% is `1500`; never a float percentage), effective-from/to dates, `is_default`. Historical rates must be retained, because an invoice issued before a rate change must keep rendering the rate it was issued under.
- `org_settings` additions — tax registration number (VAT/TRN), tax-registered flag, and whether prices are entered tax-inclusive or tax-exclusive.

### 5.2 Column requirements on `client_invoices` / `client_invoice_line_items`

Each line item carries: `tax_rate_id`, the `rate_bps` **copied onto the line at issue** (rates change; issued invoices must not), `tax_amount`, and both `subtotal_excl_tax` and `total_incl_tax`. The invoice header carries the same three totals plus a per-rate tax summary, since a single invoice may mix rates (standard-rated, zero-rated, exempt).

### 5.3 Rules to specify before the first 2B migration

1. Rounding: per line or per invoice, and half-up vs. banker's — must be a single documented policy, consistent with ADR-20's "round once" principle.
2. Zero-rated vs. exempt vs. out-of-scope are three different things and must be distinguishable, not collapsed into `rate = 0`.
3. Credit notes / refunds — negative-amount documents that must reference the original invoice and reverse its tax.
4. Whether tax applies to reimbursable expenses re-billed to a client.
5. Statutory invoice-content requirements per target jurisdiction (KSA e-invoicing/ZATCA in particular imposes format and QR requirements) — a compliance question to answer before committing to a market, not an engineering detail.
