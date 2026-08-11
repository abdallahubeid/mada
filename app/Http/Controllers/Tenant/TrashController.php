<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Tenancy\TrashableResourceCatalog;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\TrashBulkActionRequest;
use App\Services\Tenancy\TrashManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TrashController extends Controller
{
    public function __construct(private TrashManager $trash) {}

    public function index(Request $request): View
    {
        $type = $request->string('type')->toString() ?: null;

        if ($type !== null && $type !== '' && ! in_array($type, TrashableResourceCatalog::keys(), true)) {
            $type = null;
        }

        $items = $this->trash->items($type === '' ? null : $type);

        return view('tenant.trash.index', [
            'items' => $items,
            'types' => TrashableResourceCatalog::all(),
            'activeType' => $type,
            'totalCount' => $this->trash->count(),
        ]);
    }

    public function restore(string $type, string $id): RedirectResponse
    {
        try {
            $this->trash->restore($type, $id);
        } catch (InvalidArgumentException) {
            throw new NotFoundHttpException;
        }

        flash()->success('تم استعادة العنصر بنجاح.');

        return redirect()->back(fallback: route('tenant.trash.index'));
    }

    public function forceDelete(string $type, string $id): RedirectResponse
    {
        try {
            $this->trash->forceDestroy($type, $id);
        } catch (InvalidArgumentException) {
            throw new NotFoundHttpException;
        }

        flash()->warning('تم الحذف النهائي للعنصر.');

        return redirect()->route('tenant.trash.index', array_filter([
            'type' => request()->request->get('type') ?: request()->query('type'),
        ]));
    }

    public function restoreSelected(TrashBulkActionRequest $request): RedirectResponse
    {
        $count = $this->trash->restoreMany($request->normalizedItems());

        flash()->success("تم استعادة {$count} عنصر/عناصر.");

        return redirect()->route('tenant.trash.index', array_filter([
            'type' => $request->request->get('type') ?: $request->query('type'),
        ]));
    }

    public function forceSelected(TrashBulkActionRequest $request): RedirectResponse
    {
        $count = $this->trash->forceDestroyMany($request->normalizedItems());

        flash()->warning("تم الحذف النهائي لـ {$count} عنصر/عناصر.");

        return redirect()->route('tenant.trash.index', array_filter([
            'type' => $request->request->get('type') ?: $request->query('type'),
        ]));
    }

    public function empty(Request $request): RedirectResponse
    {
        $type = $request->string('type')->toString() ?: null;

        if ($type !== null && $type !== '' && ! in_array($type, TrashableResourceCatalog::keys(), true)) {
            $type = null;
        }

        $count = $this->trash->empty($type === '' ? null : $type);

        flash()->warning($count > 0
            ? "تم تفريغ السلة ({$count} عنصر)."
            : 'السلة فارغة بالفعل.');

        return redirect()->route('tenant.trash.index');
    }
}
