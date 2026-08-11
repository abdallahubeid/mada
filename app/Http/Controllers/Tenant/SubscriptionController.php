<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Tenancy\Models\TenantInvoice;
use App\Domain\Tenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Services\Tenancy\SubscriptionOverview;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly SubscriptionOverview $overview,
    ) {}

    public function index(): View
    {
        $tenant = $this->tenantContext->getTenant();

        abort_if($tenant === null, 404);

        return view('tenant.subscription.index', $this->overview->for($tenant));
    }

    public function downloadInvoice(TenantInvoice $invoice): StreamedResponse|RedirectResponse
    {
        abort_unless(
            (int) $invoice->tenant_id === (int) $this->tenantContext->getTenantId(),
            404
        );

        if (! filled($invoice->pdf_path) || ! Storage::disk('custom')->exists($invoice->pdf_path)) {
            flash()->error('ملف الفاتورة غير متوفر حالياً.');

            return redirect()->route('tenant.subscription.index');
        }

        return Storage::disk('custom')->download(
            $invoice->pdf_path,
            $invoice->number.'.pdf',
        );
    }
}
