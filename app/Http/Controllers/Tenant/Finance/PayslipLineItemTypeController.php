<?php

namespace App\Http\Controllers\Tenant\Finance;

use App\Domain\Finance\Enums\PayslipLineItemKind;
use App\Domain\Finance\Models\PayslipLineItemType;
use App\Domain\Tenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Finance\StorePayslipLineItemTypeRequest;
use App\Http\Requests\Tenant\Finance\UpdatePayslipLineItemTypeRequest;
use App\Services\Tenancy\TrashManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Tenant-configurable allowance/deduction definitions (BR-601).
 *
 * These are data rather than an application enum so a tenant can add "housing
 * allowance" or "GOSI deduction" without a code change.
 */
class PayslipLineItemTypeController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TrashManager $trash,
    ) {}

    public function index(): View
    {
        return view('tenant.finance.line-item-types.index', [
            'types' => PayslipLineItemType::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->paginate(config('app.paginate_page')),
        ]);
    }

    public function create(): View
    {
        return view('tenant.finance.line-item-types.create', [
            'type' => new PayslipLineItemType(['kind' => PayslipLineItemKind::Allowance]),
            'action' => route('finance.line-item-types.store'),
            'method' => 'POST',
            'kinds' => PayslipLineItemKind::cases(),
        ]);
    }

    public function store(StorePayslipLineItemTypeRequest $request): RedirectResponse
    {
        PayslipLineItemType::query()->create($this->payload($request->validated()));

        flash()->success('تم إضافة البند بنجاح.');

        return redirect()->route('finance.line-item-types.index');
    }

    public function edit(PayslipLineItemType $lineItemType): View
    {
        $this->ensureTenantType($lineItemType);

        return view('tenant.finance.line-item-types.edit', [
            'type' => $lineItemType,
            'action' => route('finance.line-item-types.update', $lineItemType),
            'method' => 'PUT',
            'kinds' => PayslipLineItemKind::cases(),
        ]);
    }

    public function update(UpdatePayslipLineItemTypeRequest $request, PayslipLineItemType $lineItemType): RedirectResponse
    {
        $this->ensureTenantType($lineItemType);

        $lineItemType->update($this->payload($request->validated()));

        flash()->info('تم تحديث البند بنجاح.');

        return redirect()->route('finance.line-item-types.index');
    }

    public function destroy(PayslipLineItemType $lineItemType): RedirectResponse
    {
        $this->ensureTenantType($lineItemType);

        $lineItemType->delete();

        // Line items already written onto payslips snapshot their own label and
        // kind (BR-608), so deleting a type never rewrites historical payslips.
        $this->trash->flashSoftDeleted('تم حذف البند بنجاح.', 'line-item-types', $lineItemType);

        return redirect()->route('finance.line-item-types.index');
    }

    /**
     * Convert the submitted MAJOR-unit magnitude into signed minor units,
     * taking the sign from the kind so a deduction can never add money.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function payload(array $validated): array
    {
        $kind = PayslipLineItemKind::from((string) $validated['kind']);
        $magnitude = (int) round(((float) ($validated['default_amount'] ?? 0)) * 100);

        $validated['default_amount'] = $kind === PayslipLineItemKind::Deduction
            ? -abs($magnitude)
            : abs($magnitude);

        return $validated;
    }

    private function ensureTenantType(PayslipLineItemType $type): void
    {
        abort_unless(
            (int) $type->tenant_id === (int) $this->tenantContext->getTenantId(),
            404
        );
    }
}
