<?php

namespace App\Services\Tenancy;

use App\Domain\Tenancy\Models\AuditLog;
use App\Domain\Tenancy\TenantPermissionCatalog;

/**
 * Turns technical audit payloads into executive-friendly Arabic summaries.
 */
class AuditLogPresenter
{
    /**
     * @return array{
     *     summary: string,
     *     action_label: string,
     *     module_label: string,
     *     badges: list<string>,
     *     rows: list<array{field: string, before: string, after: string}>
     * }
     */
    public function present(AuditLog $log): array
    {
        $changes = is_array($log->changes) ? $log->changes : [];
        $rows = $this->changeRows($changes);

        return [
            'summary' => $this->summary($log->action, $changes),
            'action_label' => $this->actionLabel($log->action),
            'module_label' => $this->moduleLabel($log->module),
            'badges' => array_values(array_filter(array_map(
                fn (array $row): string => $row['after'] !== '—'
                    ? $row['field'].': '.$row['after']
                    : '',
                array_slice($rows, 0, 2),
            ))),
            'rows' => $rows,
        ];
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    public function summary(string $action, array $changes = []): string
    {
        $role = isset($changes['role']) ? $this->roleLabel((string) $changes['role']) : null;
        $name = isset($changes['full_name']) ? (string) $changes['full_name'] : null;

        return match ($action) {
            'employee.created' => $name
                ? "إضافة موظف جديد: {$name}"
                : 'إضافة موظف جديد',
            'employee.deleted' => $name
                ? "حذف موظف: {$name}"
                : 'حذف موظف',
            'leave.created' => 'إنشاء طلب إجازة',
            'leave.escalated' => 'تصعيد اعتماد طلب إجازة للمستوى التالي',
            'leave.approved' => 'اعتماد طلب إجازة',
            'leave.rejected' => 'رفض طلب إجازة',
            'settings.updated' => 'تحديث إعدادات المؤسسة',
            'settings.work_schedule_updated' => 'تحديث جدول العمل',
            'role.created' => $role
                ? "إنشاء دور: {$role}"
                : 'إنشاء دور',
            'role.updated' => $role
                ? "تعديل أذونات دور: {$role}"
                : 'تعديل أذونات دور',
            'role.deleted' => $role
                ? "حذف دور: {$role}"
                : 'حذف دور',
            'asset.created' => isset($changes['asset_code'])
                ? 'إضافة أصل: '.(string) $changes['asset_code']
                : 'إضافة أصل',
            'asset.updated' => isset($changes['asset_code'])
                ? 'تحديث أصل: '.(string) $changes['asset_code']
                : 'تحديث أصل',
            'asset.assigned' => isset($changes['asset_code'])
                ? 'إسناد أصل: '.(string) $changes['asset_code']
                : 'إسناد أصل لموظف',
            'asset.returned' => isset($changes['asset_code'])
                ? 'إعادة أصل: '.(string) $changes['asset_code']
                : 'إعادة أصل من موظف',
            default => $this->actionLabel($action),
        };
    }

    public function actionLabel(string $action): string
    {
        return match ($action) {
            'employee.created' => 'إضافة موظف جديد',
            'employee.deleted' => 'حذف موظف',
            'leave.created' => 'إنشاء طلب إجازة',
            'leave.escalated' => 'تصعيد اعتماد إجازة',
            'leave.approved' => 'اعتماد إجازة',
            'leave.rejected' => 'رفض إجازة',
            'settings.updated' => 'تحديث الإعدادات',
            'settings.work_schedule_updated' => 'تحديث جدول العمل',
            'role.created' => 'إنشاء دور',
            'role.updated' => 'تعديل أذونات دور',
            'role.deleted' => 'حذف دور',
            'asset.created' => 'إضافة أصل',
            'asset.updated' => 'تحديث أصل',
            'asset.assigned' => 'إسناد أصل',
            'asset.returned' => 'إعادة أصل',
            default => str_replace(['.', '_'], ' ', $action),
        };
    }

    public function moduleLabel(string $module): string
    {
        return match ($module) {
            'hr' => 'الموارد البشرية',
            'settings' => 'الإعدادات',
            'rbac' => 'الأدوار والصلاحيات',
            'system' => 'النظام',
            default => $module,
        };
    }

    public function fieldLabel(string $field): string
    {
        return match ($field) {
            'full_name' => 'الاسم الكامل',
            'job_title' => 'المسمى الوظيفي',
            'days_count' => 'عدد الأيام',
            'requires_manager_escalation' => 'يتطلب تصعيد المدير',
            'approval_level' => 'مستويات الاعتماد',
            'current_approval_level', 'level' => 'مستوى الاعتماد الحالي',
            'required' => 'المستويات المطلوبة',
            'employee_id' => 'الموظف',
            'asset_code' => 'رمز الأصل',
            'name' => 'الاسم',
            'status' => 'الحالة',
            'rejection_reason' => 'سبب الرفض',
            'currency' => 'العملة',
            'timezone' => 'المنطقة الزمنية',
            'role' => 'الدور',
            'permissions' => 'الصلاحيات',
            'old', 'before', 'from' => 'القيمة السابقة',
            'new', 'after', 'to' => 'القيمة الجديدة',
            default => str_replace('_', ' ', $field),
        };
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return list<array{field: string, before: string, after: string}>
     */
    public function changeRows(array $changes): array
    {
        if ($changes === []) {
            return [];
        }

        if ($this->looksLikeDiffList($changes)) {
            return $this->rowsFromDiffList($changes);
        }

        if (isset($changes['old'], $changes['new']) && is_array($changes['old']) && is_array($changes['new'])) {
            return $this->rowsFromOldNew($changes['old'], $changes['new']);
        }

        if (isset($changes['before'], $changes['after']) && is_array($changes['before']) && is_array($changes['after'])) {
            return $this->rowsFromOldNew($changes['before'], $changes['after']);
        }

        $rows = [];

        foreach ($changes as $key => $value) {
            if (in_array($key, ['old', 'new', 'before', 'after', 'from', 'to'], true) && is_array($value)) {
                continue;
            }

            $rows[] = [
                'field' => $this->fieldLabel((string) $key),
                'before' => '—',
                'after' => $this->humanValue((string) $key, $value),
            ];
        }

        return $rows;
    }

    public function roleLabel(string $role): string
    {
        $arabic = TenantPermissionCatalog::roleLabels()[$role] ?? null;

        return $arabic ? "{$arabic} ({$role})" : $role;
    }

    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     * @return list<array{field: string, before: string, after: string}>
     */
    private function rowsFromOldNew(array $old, array $new): array
    {
        $keys = array_values(array_unique([...array_keys($old), ...array_keys($new)]));
        $rows = [];

        foreach ($keys as $key) {
            $before = array_key_exists($key, $old) ? $this->humanValue((string) $key, $old[$key]) : '—';
            $after = array_key_exists($key, $new) ? $this->humanValue((string) $key, $new[$key]) : '—';

            if ($before === $after) {
                continue;
            }

            $rows[] = [
                'field' => $this->fieldLabel((string) $key),
                'before' => $before,
                'after' => $after,
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int|string, mixed>  $changes
     */
    private function looksLikeDiffList(array $changes): bool
    {
        if ($changes === [] || ! array_is_list($changes)) {
            return false;
        }

        $first = $changes[0] ?? null;

        return is_array($first)
            && (isset($first['field']) || isset($first['key']))
            && (array_key_exists('from', $first) || array_key_exists('before', $first) || array_key_exists('old', $first));
    }

    /**
     * @param  list<array<string, mixed>>  $changes
     * @return list<array{field: string, before: string, after: string}>
     */
    private function rowsFromDiffList(array $changes): array
    {
        $rows = [];

        foreach ($changes as $item) {
            if (! is_array($item)) {
                continue;
            }

            $field = (string) ($item['field'] ?? $item['key'] ?? 'حقل');
            $before = $item['from'] ?? $item['before'] ?? $item['old'] ?? '—';
            $after = $item['to'] ?? $item['after'] ?? $item['new'] ?? '—';

            $rows[] = [
                'field' => $this->fieldLabel($field),
                'before' => $this->humanValue($field, $before),
                'after' => $this->humanValue($field, $after),
            ];
        }

        return $rows;
    }

    private function humanValue(string $field, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'نعم' : 'لا';
        }

        if ($field === 'role' && is_string($value)) {
            return $this->roleLabel($value);
        }

        if ($field === 'permissions' && is_array($value)) {
            $count = count($value);

            return $count === 0
                ? 'بدون صلاحيات'
                : "{$count} صلاحية";
        }

        if (is_array($value)) {
            if ($value === []) {
                return '—';
            }

            if (array_is_list($value) && collect($value)->every(fn ($item) => is_scalar($item) || $item === null)) {
                $labels = array_map(
                    fn ($item) => $this->permissionLabel((string) $item),
                    array_slice($value, 0, 8),
                );
                $text = implode('، ', $labels);

                if (count($value) > 8) {
                    $text .= '، +'.(count($value) - 8);
                }

                return $text;
            }

            return count($value).' عنصر';
        }

        if (is_scalar($value)) {
            $string = (string) $value;

            if (str_contains($field, 'permission') || str_starts_with($string, 'hr.') || str_starts_with($string, 'tenant.')) {
                return $this->permissionLabel($string);
            }

            return $string;
        }

        return '—';
    }

    private function permissionLabel(string $permission): string
    {
        foreach (TenantPermissionCatalog::groups() as $group) {
            if (isset($group['permissions'][$permission])) {
                return $group['permissions'][$permission];
            }
        }

        return $permission;
    }
}
