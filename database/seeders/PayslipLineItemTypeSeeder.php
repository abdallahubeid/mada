<?php

namespace Database\Seeders;

use App\Domain\Finance\Enums\PayslipLineItemKind;
use App\Domain\Finance\Models\PayslipLineItemType;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantContext;
use Illuminate\Database\Seeder;

/**
 * Seeds a starter set of allowance/deduction types per tenant (BR-601).
 *
 * Amounts are MINOR units and already signed by kind. Everything ships
 * INACTIVE with a zero default: a seeder that silently added money to every
 * payslip on the next run would be a very expensive convenience. A tenant
 * enables and prices what applies to them.
 */
class PayslipLineItemTypeSeeder extends Seeder
{
    /**
     * @var list<array{name: string, code: string, kind: PayslipLineItemKind, sort_order: int}>
     */
    private const TYPES = [
        ['name' => 'بدل سكن', 'code' => 'HOUSING', 'kind' => PayslipLineItemKind::Allowance, 'sort_order' => 10],
        ['name' => 'بدل مواصلات', 'code' => 'TRANSPORT', 'kind' => PayslipLineItemKind::Allowance, 'sort_order' => 20],
        ['name' => 'بدل طبيعة عمل', 'code' => 'NATURE', 'kind' => PayslipLineItemKind::Allowance, 'sort_order' => 30],
        ['name' => 'التأمينات الاجتماعية', 'code' => 'GOSI', 'kind' => PayslipLineItemKind::Deduction, 'sort_order' => 40],
        ['name' => 'سلفة على الراتب', 'code' => 'ADVANCE', 'kind' => PayslipLineItemKind::Deduction, 'sort_order' => 50],
    ];

    public function run(): void
    {
        Tenant::query()->each(function (Tenant $tenant): void {
            app(TenantContext::class)->setTenant($tenant);

            foreach (self::TYPES as $type) {
                PayslipLineItemType::query()->updateOrCreate(
                    ['tenant_id' => $tenant->id, 'code' => $type['code']],
                    [
                        'name' => $type['name'],
                        'kind' => $type['kind'],
                        'default_amount' => 0,
                        'is_active' => false,
                        'is_taxable' => false,
                        'sort_order' => $type['sort_order'],
                    ],
                );
            }
        });

        app(TenantContext::class)->setTenant(null);
    }
}
