<?php

namespace App\Domain\Tenancy;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Canonical Spatie permission catalog for the Tenant app (/app/*).
 *
 * Permissions are global names; roles are Teams-scoped by tenant_id (ADR-03).
 *
 * @phpstan-type PermissionGroup array{label: string, permissions: array<string, string>}
 */
final class TenantPermissionCatalog
{
    public const GUARD = 'web';

    public const ROLE_OWNER = 'Owner';

    public const ROLE_HR_MANAGER = 'HR Manager';

    public const ROLE_FINANCE_MANAGER = 'Finance Manager';

    public const ROLE_PROJECT_MANAGER = 'Project Manager';

    public const ROLE_EMPLOYEE = 'Employee';

    /**
     * @return list<string>
     */
    public static function roleNames(): array
    {
        return [
            self::ROLE_OWNER,
            self::ROLE_HR_MANAGER,
            self::ROLE_FINANCE_MANAGER,
            self::ROLE_PROJECT_MANAGER,
            self::ROLE_EMPLOYEE,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function roleLabels(): array
    {
        return [
            self::ROLE_OWNER => 'المالك',
            self::ROLE_HR_MANAGER => 'مدير الموارد البشرية',
            self::ROLE_FINANCE_MANAGER => 'مدير المالية',
            self::ROLE_PROJECT_MANAGER => 'مدير المشاريع',
            self::ROLE_EMPLOYEE => 'موظف',
        ];
    }

    public static function isProtectedRole(string $name): bool
    {
        return in_array($name, self::roleNames(), true);
    }

    /**
     * @return array<string, PermissionGroup>
     */
    public static function groups(): array
    {
        return [
            'dashboard' => [
                'label' => 'لوحة التحكم',
                'permissions' => [
                    'tenant.dashboard.view' => 'عرض لوحة تحكم المستأجر',
                    'hr.dashboard.view' => 'عرض لوحة تحكم الموارد البشرية',
                    'tenant.audit_logs.view' => 'عرض سجل النشاط (المالك فقط)',
                    'tenant.reports.view' => 'عرض وتصدير التقارير',
                    'tenant.trash.view_any' => 'عرض سلة المحذوفات',
                    'tenant.trash.restore' => 'استعادة العناصر من سلة المحذوفات',
                    'tenant.trash.force_delete' => 'الحذف النهائي وتفريغ سلة المحذوفات',
                ],
            ],
            'settings' => [
                'label' => 'إعدادات المؤسسة',
                'permissions' => [
                    'tenant.settings.view' => 'عرض إعدادات المؤسسة',
                    'tenant.settings.update' => 'تحديث إعدادات المؤسسة',
                    'tenant.subscription.view' => 'عرض الاشتراك والخطط',
                    'tenant.announcements.view_any' => 'عرض التعميمات والإعلانات',
                    'tenant.announcements.manage' => 'إدارة التعميمات والإعلانات',
                    'tenant.holidays.view_any' => 'عرض العطلات الرسمية',
                    'tenant.holidays.manage' => 'إدارة العطلات الرسمية',
                    'tenant.contact_messages.view_any' => 'عرض رسائل التواصل من الموقع العام',
                    'tenant.contact_messages.manage' => 'الرد وإغلاق محادثات رسائل التواصل',
                ],
            ],
            'departments' => [
                'label' => 'الأقسام',
                'permissions' => [
                    'hr.departments.view_any' => 'عرض الأقسام',
                    'hr.departments.create' => 'إنشاء قسم',
                    'hr.departments.update' => 'تحديث قسم',
                    'hr.departments.delete' => 'حذف قسم',
                ],
            ],
            'employees' => [
                'label' => 'الموظفون',
                'permissions' => [
                    'hr.employees.view_any' => 'عرض الموظفين',
                    'hr.employees.view' => 'عرض ملف موظف',
                    'hr.employees.create' => 'إنشاء موظف',
                    'hr.employees.update' => 'تحديث موظف',
                    'hr.employees.delete' => 'حذف موظف',
                ],
            ],
            'contracts' => [
                'label' => 'العقود',
                'permissions' => [
                    'hr.contracts.view_any' => 'عرض العقود',
                    'hr.contracts.create' => 'إنشاء عقد',
                    'hr.contracts.update' => 'تحديث عقد',
                    'hr.contracts.delete' => 'حذف عقد',
                ],
            ],
            'jobs' => [
                'label' => 'الوظائف والتوظيف',
                'permissions' => [
                    'hr.jobs.view_any' => 'عرض الوظائف',
                    'hr.jobs.create' => 'إنشاء وظيفة',
                    'hr.jobs.update' => 'تحديث وظيفة',
                    'hr.jobs.delete' => 'حذف وظيفة',
                ],
            ],
            'applications' => [
                'label' => 'طلبات التقديم',
                'permissions' => [
                    'hr.applications.view_any' => 'عرض طلبات التقديم',
                    'hr.applications.view' => 'عرض طلب تقديم',
                    'hr.applications.update' => 'تحديث حالة طلب',
                    'hr.applications.delete' => 'حذف طلب تقديم',
                    'hr.applications.convert' => 'تحويل متقدم إلى موظف',
                    /*
                     * Scheduling an interview sends mail from the tenant's
                     * domain to an external candidate, so it is a separate
                     * ability from merely editing an application's stage.
                     */
                    'hr.recruitment.manage' => 'جدولة مقابلات المتقدمين وإرسال الدعوات',
                ],
            ],
            'attendance' => [
                'label' => 'الحضور والغياب',
                'permissions' => [
                    'hr.attendance.view_any' => 'عرض سجل الحضور',
                    'hr.attendance.create' => 'تسجيل حضور',
                    'hr.attendance.update' => 'تحديث سجل حضور',
                ],
            ],
            'leaves' => [
                'label' => 'الإجازات',
                'permissions' => [
                    'hr.leaves.view_any' => 'عرض طلبات الإجازة',
                    'hr.leaves.create' => 'إنشاء طلب إجازة',
                    'hr.leaves.approve' => 'اعتماد / رفض الإجازات',
                    'hr.leaves.manage_types' => 'إدارة أنواع الإجازات',
                ],
            ],
            'performance' => [
                'label' => 'تقييم الأداء',
                'permissions' => [
                    'hr.evaluations.view_any' => 'عرض تقييمات الأداء الهرمية',
                    'hr.evaluations.manage' => 'إدخال وإرسال تقييمات الأداء',
                    'hr.evaluations.approve' => 'اعتماد وتقفيل تقييمات الفترة',
                ],
            ],
            'assets' => [
                'label' => 'العُهد والأصول',
                'permissions' => [
                    'hr.assets.view_any' => 'عرض العُهد والأصول',
                    'hr.assets.manage' => 'إدارة العُهد والأصول (إضافة، إسناد، إعادة)',
                ],
            ],
            'tasks' => [
                'label' => 'مهام الفريق',
                'permissions' => [
                    'hr.tasks.manage' => 'إنشاء وإسناد المهام لأعضاء الفريق المباشرين',
                ],
            ],
            /*
             * Messaging carries exactly ONE permission, and it is about
             * creating groups — never about reading them.
             *
             * Access to a conversation is participant membership, checked in
             * ConversationChannel and Conversation::scopeVisibleTo(). It is
             * deliberately not expressible here: `Gate::before` grants the
             * Owner every ability, so a `messaging.*.view` permission would
             * hand every Owner every private thread in their company, which is
             * the opposite of the agreed policy.
             *
             * Group CREATION is a genuine capability rather than an access
             * grant — it does not let the holder read anything they were not
             * added to — so a permission is the right shape for it, and it
             * extends to custom tenant roles that a hardcoded role-name check
             * would miss.
             */
            'messaging' => [
                'label' => 'المراسلات الداخلية',
                'permissions' => [
                    'messaging.groups.create' => 'إنشاء مجموعات المحادثة وإدارة أعضائها',
                ],
            ],
            // Group key kept as `my_space` for stability; the My Space *page*
            // it was named after is retired, and each permission below now
            // gates its own standalone self-service route. `hr.my_space.view`
            // was dropped with that page — it no longer gates anything.
            'my_space' => [
                'label' => 'الخدمة الذاتية للموظف',
                'permissions' => [
                    'hr.my_evaluations.view' => 'عرض تقييماتي الشخصية',
                    'hr.my_leaves.view' => 'عرض وتقديم طلبات إجازتي',
                    'hr.attendance.check_in_out' => 'تسجيل حضوري وانصرافي الذاتي',
                    'hr.my_tasks.view' => 'عرض لوحة مهامي وتحديث حالتها',
                    'hr.my_dashboard.view' => 'عرض لوحة تحكمي الشخصية',
                    'hr.my_payslips.view' => 'عرض مسيرات رواتبي المعتمدة',
                    'tenant.announcements.view_self' => 'قراءة التعميمات المنشورة',
                ],
            ],
            'finance' => [
                'label' => 'المالية والرواتب',
                'permissions' => [
                    'finance.dashboard.view' => 'عرض لوحة التحكم المالية',
                    'finance.payroll.view_any' => 'عرض مسيرات الرواتب',
                    'finance.payroll.prepare' => 'إعداد وتعديل مسيرة رواتب (مسودة)',
                    'finance.payroll.approve' => 'اعتماد مسيرة الرواتب (فصل المهام)',
                    'finance.payroll.pay' => 'تسجيل صرف مسيرة رواتب معتمدة',
                    'finance.payroll.delete' => 'حذف مسيرة رواتب (المسودات فقط)',
                    'finance.line_item_types.manage' => 'إدارة أنواع البدلات والاستقطاعات',
                    'finance.expenses.view_any' => 'عرض المصروفات',
                    'finance.expenses.manage' => 'إنشاء وتعديل المصروفات',
                    'finance.expenses.approve' => 'اعتماد أو رفض المصروفات',
                    'finance.expenses.pay' => 'تسجيل صرف المصروفات المعتمدة',
                    'finance.expense_categories.manage' => 'إدارة تصنيفات المصروفات',
                    'finance.offboarding.view_any' => 'عرض تسويات نهاية الخدمة',
                    'finance.offboarding.manage' => 'إعداد تسويات نهاية الخدمة',
                    'finance.offboarding.approve' => 'اعتماد وصرف تسويات نهاية الخدمة',
                    /*
                     * Granted to the Owner and the Finance Manager only. These
                     * rules decide the largest single payment an employee ever
                     * receives, so they are deliberately NOT part of any HR or
                     * self-service bucket.
                     */
                    'finance.settings.manage' => 'ضبط إعدادات المالية وقواعد نهاية الخدمة',
                ],
            ],
            'roles' => [
                'label' => 'الأدوار والصلاحيات',
                'permissions' => [
                    'tenant.roles.view_any' => 'عرض الأدوار',
                    'tenant.roles.manage' => 'إدارة الأدوار والصلاحيات (المالك فقط)',
                ],
            ],
            'users' => [
                'label' => 'أعضاء الفريق',
                'permissions' => [
                    'tenant.users.manage' => 'إدارة أعضاء الفريق (إنشاء، تعديل، حذف)',
                ],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        $names = [];

        foreach (self::groups() as $group) {
            foreach (array_keys($group['permissions']) as $name) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * Ensure every catalog permission row exists for the web guard.
     *
     * Safe to call on every role sync / tenant seed — uses findOrCreate and
     * clears Spatie's permission cache afterwards.
     */
    public static function syncCatalog(): void
    {
        foreach (self::all() as $permission) {
            Permission::findOrCreate($permission, self::GUARD);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Employee Self-Service bucket — permissions an employee needs to manage
     * their own HR data (My Space hub, own evaluations, own leave requests,
     * own attendance check-in/out). Never grants visibility into other
     * employees' records.
     *
     * @return list<string>
     */
    public static function selfServicePermissions(): array
    {
        return array_keys(self::groups()['my_space']['permissions']);
    }

    /**
     * HR Management bucket — the full set of operational HR permissions that
     * act on other employees' records (departments, employees, contracts,
     * jobs, applications, attendance, leaves, performance, assets). Distinct
     * from {@see self::selfServicePermissions()}, which only ever touches the
     * acting user's own employee record.
     *
     * @return list<string>
     */
    public static function hrManagementPermissions(): array
    {
        $groups = self::groups();
        $managementGroupKeys = [
            'departments', 'employees', 'contracts', 'jobs',
            'applications', 'attendance', 'leaves', 'performance', 'assets', 'tasks',
        ];

        $names = [];
        foreach ($managementGroupKeys as $key) {
            $names = array_merge($names, array_keys($groups[$key]['permissions']));
        }

        return $names;
    }

    /**
     * Default permission grants per seeded tenant role (BR-102 / BR-103).
     *
     * @return array<string, list<string>>
     */
    public static function rolePermissionMap(): array
    {
        $all = self::all();
        $selfService = self::selfServicePermissions();

        return [
            self::ROLE_OWNER => $all,
            self::ROLE_HR_MANAGER => array_values(array_unique(array_merge([
                'tenant.dashboard.view',
                'hr.dashboard.view',
                'tenant.reports.view',
                'tenant.trash.view_any',
                'tenant.trash.restore',
                'tenant.settings.view',
                'tenant.announcements.view_any',
                'tenant.announcements.manage',
                'tenant.holidays.view_any',
                'tenant.holidays.manage',
                'tenant.contact_messages.view_any',
                'tenant.contact_messages.manage',
                'hr.departments.view_any',
                'hr.departments.update',
                'hr.employees.view_any',
                'hr.employees.view',
                'hr.employees.create',
                'hr.employees.update',
                'hr.contracts.view_any',
                'hr.contracts.create',
                'hr.contracts.update',
                'hr.jobs.view_any',
                'hr.jobs.create',
                'hr.jobs.update',
                'hr.applications.view_any',
                'hr.applications.view',
                'hr.applications.update',
                'hr.applications.convert',
                'hr.recruitment.manage',
                'hr.attendance.view_any',
                'hr.attendance.create',
                'hr.attendance.update',
                'hr.leaves.view_any',
                'hr.leaves.create',
                'hr.leaves.approve',
                'hr.leaves.manage_types',
                'hr.evaluations.view_any',
                'hr.evaluations.manage',
                'hr.assets.view_any',
                'hr.assets.manage',
                'hr.tasks.manage',
                'messaging.groups.create',
            ], $selfService))),
            /*
             * BR-615 / ADR-09: the Finance Manager PREPARES payroll and never
             * approves it. `finance.payroll.approve` is deliberately absent
             * here — granting it would collapse maker-checker into one role.
             * Approval reaches the Owner through the Gate::before bypass.
             */
            self::ROLE_FINANCE_MANAGER => array_values(array_unique(array_merge([
                'tenant.dashboard.view',
                'tenant.reports.view',
                'tenant.trash.view_any',
                'tenant.trash.restore',
                'finance.dashboard.view',
                'finance.payroll.view_any',
                'finance.payroll.prepare',
                'finance.payroll.pay',
                'finance.payroll.delete',
                'finance.line_item_types.manage',
                'finance.expenses.view_any',
                'finance.expenses.manage',
                'finance.expenses.approve',
                'finance.expenses.pay',
                'finance.expense_categories.manage',
                'finance.offboarding.view_any',
                'finance.offboarding.manage',
                'finance.settings.manage',
                'hr.employees.view_any',
                'hr.contracts.view_any',
                'messaging.groups.create',
            ], $selfService))),
            /*
             * Both manager roles may open group chats; the Employee role may
             * not. That is the whole of the group-creation restriction — an
             * Employee added to a group participates normally, they simply
             * cannot start one.
             */
            self::ROLE_PROJECT_MANAGER => array_merge(
                ['tenant.dashboard.view', 'messaging.groups.create'],
                $selfService,
            ),
            self::ROLE_EMPLOYEE => array_merge(['tenant.dashboard.view'], $selfService),
        ];
    }
}
