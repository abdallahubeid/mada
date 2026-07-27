<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Services\Marketing\MarketingContent;
use Illuminate\Contracts\View\View;

/**
 * Features marketing page — reuses section components with CMS headings.
 */
class FeaturesController extends Controller
{
    public function __construct(private MarketingContent $marketing) {}

    public function __invoke(): View
    {
        return view('marketing.features', [
            'features' => $this->marketing->featuresHeading(),
            'offerings' => $this->marketing->offerings(),
            'modules' => $this->marketing->modules(),
            'productPreviewStats' => $this->marketing->productPreviewStats(),
            'whyUsFeatures' => $this->marketing->whyUsFeatures(),
            'cta' => $this->marketing->cta(),
        ]);
    }
}
