<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePlanRequest;
use App\Http\Requests\Admin\UpdatePlanRequest;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Services\Marketing\MarketingCache;
use App\Services\Platform\PlatformAuditor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Plans & Feature Limits backed by `plans` / `plan_features`
 * (docs/ADMIN_CMS_ANALYSIS.md, DATABASE_ROADMAP.md §2.1).
 */
class PlanController extends Controller
{
    public function index(): View
    {
        $plans = Plan::query()
            ->with('features')
            ->orderBy('sort_order')
            ->get()
            ->map(function (Plan $plan): array {
                $featureLabels = $plan->features->pluck('label')->all();

                return [
                    'id' => $plan->id,
                    'key' => $plan->slug,
                    'name' => $plan->name,
                    'tagline' => $plan->tagline,
                    'price_monthly' => $plan->price_monthly,
                    'price_yearly' => $plan->price_yearly,
                    'currency' => $plan->currency,
                    'cta_label' => $plan->cta_label,
                    'cta_url' => $plan->cta_url,
                    'is_highlighted' => $plan->is_highlighted,
                    'is_active' => $plan->is_active,
                    'sort_order' => $plan->sort_order,
                    'tenants' => 0,
                    'popular' => $plan->is_highlighted,
                    'features_text' => implode("\n", $featureLabels),
                    'modules' => collect($featureLabels)->take(4)->map(fn (string $label): array => [
                        'label' => $label,
                        'enabled' => true,
                    ])->values()->all(),
                    'limits' => [
                        ['label' => 'شهري', 'value' => (float) ($plan->price_monthly ?? 0)],
                        ['label' => 'سنوي', 'value' => (float) ($plan->price_yearly ?? 0)],
                    ],
                ];
            })
            ->all();

        return view('admin.plans.index', ['plans' => $plans]);
    }

    public function store(StorePlanRequest $request, PlatformAuditor $auditor): RedirectResponse
    {
        $plan = DB::transaction(function () use ($request): Plan {
            $data = $request->safe()->except('features_text');
            $plan = Plan::query()->create($data);
            $this->syncFeatures($plan, (string) $request->input('features_text', ''));

            return $plan;
        });

        MarketingCache::flush();
        $auditor->log('plan.created', $plan);

        return redirect()
            ->route('admin.plans')
            ->with('status', 'تم إنشاء الخطة.');
    }

    public function update(UpdatePlanRequest $request, Plan $plan, PlatformAuditor $auditor): RedirectResponse
    {
        DB::transaction(function () use ($request, $plan): void {
            $plan->update($request->safe()->except('features_text'));
            $this->syncFeatures($plan, (string) $request->input('features_text', ''));
        });

        MarketingCache::flush();
        $auditor->log('plan.updated', $plan);

        return redirect()
            ->route('admin.plans')
            ->with('status', 'تم حفظ الخطة.');
    }

    public function destroy(Plan $plan, PlatformAuditor $auditor): RedirectResponse
    {
        $plan->update(['is_active' => false]);
        $plan->delete();

        MarketingCache::flush();
        $auditor->log('plan.archived', $plan);

        return redirect()
            ->route('admin.plans')
            ->with('status', 'تم أرشفة الخطة.');
    }

    private function syncFeatures(Plan $plan, string $featuresText): void
    {
        $labels = collect(preg_split('/\r\n|\r|\n/', $featuresText) ?: [])
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->values();

        $plan->features()->delete();

        $labels->each(function (string $label, int $index) use ($plan): void {
            PlanFeature::query()->create([
                'plan_id' => $plan->id,
                'label' => $label,
                'sort_order' => $index,
                'feature_key' => Str::slug($label, '_'),
                'value' => null,
            ]);
        });
    }
}
