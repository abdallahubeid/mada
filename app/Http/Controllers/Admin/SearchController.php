<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\GlobalSearch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Global platform search — autocomplete JSON + full results page.
 */
class SearchController extends Controller
{
    public function __construct(private GlobalSearch $search) {}

    public function index(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));
        $context = $request->query('context');
        $activeTab = (string) $request->query('tab', 'all');
        $results = $this->search->search(
            $query,
            context: is_string($context) ? $context : null,
        );

        $allowedTabs = ['all', ...array_column($results['groups'], 'key')];

        if (! in_array($activeTab, $allowedTabs, true)) {
            $activeTab = 'all';
        }

        $visibleGroups = $activeTab === 'all'
            ? $results['groups']
            : array_values(array_filter(
                $results['groups'],
                fn (array $group): bool => $group['key'] === $activeTab
            ));

        return view('admin.search.index', [
            'query' => $query,
            'results' => $results,
            'visibleGroups' => $visibleGroups,
            'activeTab' => $activeTab,
            'minLength' => GlobalSearch::MIN_QUERY_LENGTH,
        ]);
    }

    public function suggest(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        $context = $request->query('context');

        return response()->json($this->search->suggest(
            $query,
            is_string($context) ? $context : null,
        ));
    }
}
