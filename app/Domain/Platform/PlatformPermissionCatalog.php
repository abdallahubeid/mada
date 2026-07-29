<?php

namespace App\Domain\Platform;

use Spatie\Permission\PermissionRegistrar;

/**
 * Canonical Spatie permission catalog for the Platform Console (/admin/*).
 *
 * Platform roles are global (`roles.tenant_id = null`). Assignments use
 * {@see self::TEAM_ID} because Spatie teams pivots require a non-null team key.
 *
 * @phpstan-type PermissionGroup array{label: string, permissions: array<string, string>}
 */
final class PlatformPermissionCatalog
{
    public const GUARD = 'web';

    /**
     * Sentinel Spatie team id for platform-console role/permission pivots.
     * Spatie does not allow null on model_has_roles / model_has_permissions.
     */
    public const TEAM_ID = 0;

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_CONTENT_MANAGER = 'content_manager';

    public const ROLE_SUPPORT_AGENT = 'support_agent';

    public const ROLE_BILLING_MANAGER = 'billing_manager';

    /**
     * @return list<string>
     */
    public static function roleNames(): array
    {
        return [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
            self::ROLE_CONTENT_MANAGER,
            self::ROLE_SUPPORT_AGENT,
            self::ROLE_BILLING_MANAGER,
        ];
    }

    /**
     * Domain → [permission => Arabic label].
     *
     * @return array<string, PermissionGroup>
     */
    public static function groups(): array
    {
        return [
            'dashboard' => [
                'label' => 'لوحة التحكم',
                'permissions' => [
                    'dashboard.view' => 'عرض لوحة التحكم والبحث',
                ],
            ],
            'tenants' => [
                'label' => 'المستأجرون',
                'permissions' => [
                    'tenants.view_any' => 'عرض قائمة المستأجرين',
                    'tenants.view' => 'عرض تفاصيل مستأجر',
                    'tenants.update' => 'تحديث بيانات المستأجر التسويقية',
                ],
            ],
            'plans' => [
                'label' => 'الخطط',
                'permissions' => [
                    'plans.view_any' => 'عرض الخطط',
                    'plans.create' => 'إنشاء خطة',
                    'plans.update' => 'تحديث خطة',
                    'plans.delete' => 'حذف / أرشفة خطة',
                ],
            ],
            'faqs' => [
                'label' => 'الأسئلة الشائعة',
                'permissions' => [
                    'faqs.view_any' => 'عرض الأسئلة',
                    'faqs.create' => 'إنشاء سؤال',
                    'faqs.update' => 'تحديث سؤال',
                    'faqs.delete' => 'حذف سؤال',
                ],
            ],
            'cms' => [
                'label' => 'محتوى الصفحة الرئيسية',
                'permissions' => [
                    'cms.view_any' => 'عرض بطاقات المحتوى',
                    'cms.create' => 'إنشاء بطاقة محتوى',
                    'cms.update' => 'تحديث بطاقة محتوى',
                    'cms.delete' => 'حذف بطاقة محتوى',
                ],
            ],
            'settings' => [
                'label' => 'إعدادات الهبوط',
                'permissions' => [
                    'settings.view' => 'عرض الإعدادات',
                    'settings.update' => 'تحديث الإعدادات والوسائط',
                ],
            ],
            'support' => [
                'label' => 'الرسائل والدعم',
                'permissions' => [
                    'support.view_any' => 'عرض المحادثات',
                    'support.reply' => 'الرد على المحادثات',
                    'support.update' => 'تغيير الحالة / الأرشفة',
                    'support.delete' => 'حذف محادثة',
                ],
            ],
            'notifications' => [
                'label' => 'الإشعارات',
                'permissions' => [
                    'notifications.view_any' => 'عرض الإشعارات',
                    'notifications.update' => 'تعليم حالة القراءة',
                    'notifications.delete' => 'حذف الإشعارات',
                ],
            ],
            'newsletters' => [
                'label' => 'النشرة البريدية',
                'permissions' => [
                    'newsletters.view_any' => 'عرض المشتركين والحملات',
                    'newsletters.update' => 'تبديل حالة المشترك',
                    'newsletters.delete' => 'حذف مشترك',
                    'newsletters.export' => 'تصدير المشتركين',
                    'newsletters.campaigns.send' => 'إرسال حملة',
                ],
            ],
            'audit_logs' => [
                'label' => 'سجل النشاط',
                'permissions' => [
                    'audit_logs.view_any' => 'عرض سجل النشاط',
                ],
            ],
            'trash' => [
                'label' => 'سلة المحذوفات',
                'permissions' => [
                    'trash.view_any' => 'عرض سلة المحذوفات',
                    'trash.restore' => 'استعادة عنصر محذوف',
                    'trash.force_delete' => 'حذف نهائي من السلة',
                ],
            ],
            'admins' => [
                'label' => 'مديرو المنصّة',
                'permissions' => [
                    'admins.view_any' => 'عرض المشرفين',
                    'admins.create' => 'إنشاء مشرف',
                    'admins.update' => 'تحديث مشرف',
                    'admins.delete' => 'حذف مشرف',
                ],
            ],
            'roles' => [
                'label' => 'الأدوار والصلاحيات',
                'permissions' => [
                    'roles.view_any' => 'عرض الأدوار',
                    'roles.create' => 'إنشاء دور',
                    'roles.update' => 'تعديل صلاحيات الأدوار',
                    'roles.delete' => 'حذف دور',
                ],
            ],
            'account' => [
                'label' => 'الحساب',
                'permissions' => [
                    'account.security.view' => 'عرض أمان الحساب',
                    'account.profile.update' => 'تحديث الملف الشخصي',
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
     * Default permission names granted to each non–super_admin role.
     *
     * @return array<string, list<string>>
     */
    public static function rolePermissionMap(): array
    {
        $all = self::all();

        return [
            self::ROLE_SUPER_ADMIN => $all,
            self::ROLE_ADMIN => array_values(array_filter(
                $all,
                fn (string $p): bool => ! in_array($p, [
                    'admins.delete',
                    'roles.create',
                    'roles.update',
                    'roles.delete',
                ], true),
            )),
            self::ROLE_CONTENT_MANAGER => [
                'dashboard.view',
                'cms.view_any',
                'cms.create',
                'cms.update',
                'cms.delete',
                'faqs.view_any',
                'faqs.create',
                'faqs.update',
                'faqs.delete',
                'settings.view',
                'settings.update',
                'trash.view_any',
                'trash.restore',
                'account.security.view',
                'account.profile.update',
            ],
            self::ROLE_SUPPORT_AGENT => [
                'dashboard.view',
                'tenants.view_any',
                'tenants.view',
                'support.view_any',
                'support.reply',
                'support.update',
                'support.delete',
                'notifications.view_any',
                'notifications.update',
                'notifications.delete',
                'trash.view_any',
                'trash.restore',
                'account.security.view',
                'account.profile.update',
            ],
            self::ROLE_BILLING_MANAGER => [
                'dashboard.view',
                'tenants.view_any',
                'tenants.view',
                'plans.view_any',
                'plans.create',
                'plans.update',
                'plans.delete',
                'newsletters.view_any',
                'audit_logs.view_any',
                'trash.view_any',
                'trash.restore',
                'account.security.view',
                'account.profile.update',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function roleLabels(): array
    {
        return [
            self::ROLE_SUPER_ADMIN => 'مشرف عام',
            self::ROLE_ADMIN => 'مشرف',
            self::ROLE_CONTENT_MANAGER => 'مدير محتوى',
            self::ROLE_SUPPORT_AGENT => 'وكيل دعم',
            self::ROLE_BILLING_MANAGER => 'مدير الفوترة',
        ];
    }

    /**
     * Permission → named admin route candidates for post-login home selection.
     *
     * @return array<string, string>
     */
    public static function adminHomeCandidates(): array
    {
        return [
            'dashboard.view' => 'admin.dashboard',
            'cms.view_any' => 'admin.problems.index',
            'faqs.view_any' => 'admin.faqs.index',
            'tenants.view_any' => 'admin.tenants',
            'plans.view_any' => 'admin.plans',
            'support.view_any' => 'admin.messages',
            'notifications.view_any' => 'admin.notifications',
            'newsletters.view_any' => 'admin.newsletter.index',
            'settings.view' => 'admin.landing.settings.edit',
            'audit_logs.view_any' => 'admin.audit-log',
            'trash.view_any' => 'admin.trash.index',
            'admins.view_any' => 'admin.admins',
            'roles.view_any' => 'admin.roles.index',
            'account.profile.update' => 'admin.profile',
            'account.security.view' => 'admin.account.security',
        ];
    }

    /**
     * Bind Spatie team context to the platform sentinel and return the previous id.
     */
    public static function bindTeam(?PermissionRegistrar $registrar = null): int|string|null
    {
        $registrar ??= app(PermissionRegistrar::class);
        $previous = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId(self::TEAM_ID);

        return $previous;
    }

    /**
     * Clear Spatie's permission cache after role/permission mutations.
     */
    public static function forgetCachedPermissions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Run a callback while Spatie team context is unbound (global platform roles).
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function withGlobalTeam(callable $callback): mixed
    {
        $registrar = app(PermissionRegistrar::class);
        $previous = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId(null);

        try {
            return $callback();
        } finally {
            $registrar->setPermissionsTeamId($previous);
        }
    }

    /**
     * Run a callback under the platform Spatie team context, then restore the prior team.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function withTeam(callable $callback): mixed
    {
        $registrar = app(PermissionRegistrar::class);
        $previous = self::bindTeam($registrar);

        try {
            return $callback();
        } finally {
            $registrar->setPermissionsTeamId($previous);
        }
    }
}
