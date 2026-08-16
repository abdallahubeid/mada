# Mada ERP — User Journeys

> Part of the Mada ERP documentation set. See `MADA_DOCS.md` for the full SDD, `ARCHITECTURE.md` for the tenant lifecycle state machine, and `MODULES.md` for BR-701 (Employee Workspace).

## 1. Admin Onboarding Flow (Tenant Signup → Active)

**Actor:** prospective CEO/Owner.

```mermaid
flowchart TD
    A[Visits Landing Page] --> B[Fills multi-step Registration form]
    B --> C[Tenant + Owner user created: pending_verification]
    C --> D[Verifies email]
    D --> E[Status: pending_approval. Auto-login to /dashboard/setup]
    E --> F[Setup Wizard: temp password change, logo upload, currency, work calendar]
    F --> G{Super Admin reviews}
    G -->|Approve| H[Status: active. Full sidebar unlocks, no re-login needed]
    G -->|Reject / 30-day timeout| I[Status: cancelled]
    H --> J[CEO sees empty-state dashboard with guided CTAs: add first employee, add first project]
```

**Governing rules:** BR-201–BR-206 (`ARCHITECTURE.md` §3). Key constraint: no operational route (HR/Projects/Finance) is reachable before step H; the setup wizard sidebar is fully locked during steps E–G.

---

## 2. Applicant-to-Employee Lifecycle

**Actors:** external Applicant, HR Manager/Owner.

```mermaid
flowchart TD
    A[Applicant browses /companies/slug/careers] --> B[Applies to a job opening with CV]
    B --> C[Applicant record created - not a system user]
    C --> D[HR moves applicant through Kanban stages: Submitted, Interview, Offer]
    D --> E{Manager clicks Accept}
    E --> F[Employee + Contract record auto-created - BR-303]
    F --> G[Login credentials emailed to new Employee]
    G --> H[Employee logs in for the first time -> My Space / Employee Workspace]
    E -->|Rejected| I[Applicant data retained 12 months, then anonymized - BR-304]
```

**Governing rules:** BR-301–BR-304 (`MODULES.md` §1.2). This journey is Phase 3 scope (`DEVELOPMENT_ROADMAP.md`).

---

## 3. Employee Workspace Journey (Daily Use)

**Actor:** a regular Employee, scoped per BR-701 — this is the highest-frequency journey in the product and must be fast and frictionless.

```mermaid
flowchart TD
    A[Employee logs in] --> B[Lands on My Space dashboard]
    B --> C[Taps Check-In - single action, no form]
    C --> D[Works through the day]
    D --> E[Opens My Tasks - filtered to assigned_to = self only]
    E --> F[Updates task status: Todo -> In Progress -> Done]
    F --> G[Logs timesheet entry against active task]
    D --> H[Needs time off?]
    H -->|Yes| I[Submits Leave Request via Approval Engine]
    I --> J[Awaits Owner/HR Manager decision]
    D --> K[Taps Check-Out at end of day]
    K --> L[End of pay period: views own Payslip - read-only, prorated if mid-period join/exit]
```

### 3.1 Step-by-step detail

1. **Check-In / Check-Out:** exactly one primary action per state (Check-In when not yet checked in today; Check-Out when checked in). No employee ever sees another employee's attendance UI.
2. **My Tasks:** a single filtered view (by status/project/due date) strictly scoped to the employee's own assignments (BR-701 §5.2). No "switch view" to see teammates' tasks exists for this role.
3. **Timesheets:** logged only against the employee's own assigned tasks; `is_billable` is set by the Project Manager at the task level, not by the employee (BR-502).
4. **Leave Requests:** routed through the generic Approval Engine (ADR-08); on approval, the Work Ledger is updated automatically (BR-401) — the employee never manually reconciles attendance vs. leave.
5. **Payslip viewing:** read-only, print-friendly view of the employee's own locked/approved payslip only (BR-603) — prorated correctly for mid-period joiners/leavers (BR-605). Employees cannot view draft or other employees' payslips under any condition.

**Governing rules:** BR-701 (`MODULES.md` §5), BR-401/BR-402 (Work Ledger reconciliation), BR-603/BR-605 (Payroll), ADR-08 (Approval Engine), ADR-24 (one light canvas on every screen in this journey — see `DESIGN_SYSTEM.md` §2).

---

## 4. Tenant Support Inquiry Journey

**Actors:** tenant Owner/CEO, Super Admin.

```mermaid
flowchart TD
    A[Owner/CEO has a question or issue] --> B[Submits a Support Thread from the tenant app]
    B --> C[Thread created: status open, tenant_id attached]
    C --> D[Super Admin sees it in /admin/messages inbox]
    D --> E[Super Admin opens thread - explicit audited cross-tenant read]
    E --> F[Super Admin replies, sets status in_progress]
    F --> G[Owner sees reply, may respond again]
    G -->|Issue solved| H[Super Admin marks status resolved]
    G -->|Owner replies after resolution| I[Status automatically reverts to open]
```

**Governing rules:** BR-805/BR-806 (`MODULES.md` §6.2), the cross-tenant access discipline in `ARCHITECTURE.md` §8. Only the `Owner` role may create a thread — no other tenant role has this capability (mirrors BR-103's "Owner, or Super Admin" pattern for tenant-level authority). This journey's tenant-side entry point (a "Contact Support" action) is a small addition to the tenant app shell, not a page in its own right; the Super Admin-side inbox is the substantial page (`/admin/messages`).

---

## 5. Journey Design Principles (binding)

- Every journey above must degrade gracefully to an **empty state** with a guided CTA on first use (e.g., "No tasks assigned yet," "Check in to start your day") — never a blank screen.
- Every journey must render correctly in both Dark and Light mode, and in both RTL (Arabic) and LTR (English) layouts, from first implementation — not retrofitted later (`DESIGN_SYSTEM.md`).
- No journey step for the Employee role may expose a route or action outside the BR-701 boundary; this must be covered by an explicit authorization test per journey.
