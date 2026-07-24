<?php

namespace App\Livewire\Dashboard;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The tenant's role-aware home screen (docs/USER_JOURNEYS.md, IA `/app/dashboard`).
 *
 * This Phase 1 slice intentionally ships as an empty-state shell — real
 * widgets (employee count, active projects, leave requests) land as each
 * owning module (HR, Projects) is built, per docs/DEVELOPMENT_ROADMAP.md.
 */
#[Layout('components.layouts.app')]
#[Title('Dashboard — Veyra ERP')]
class Overview extends Component
{
    public function render()
    {
        return view('livewire.dashboard.overview');
    }
}
