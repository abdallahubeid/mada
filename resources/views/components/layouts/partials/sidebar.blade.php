@php
    /*
     * Tenant app navigation — Arabic RTL.
     *
     * Every permission check below is expressed with a literal @can()/@canany()
     * Blade directive (never a raw ->can() call) so link visibility, group
     * headers, and dropdown wrappers all resolve through Gate the same way.
     * See TenantPermissionCatalog::selfServicePermissions() /
     * ::hrManagementPermissions() for the two permission buckets this nav
     * mirrors (ADR-03).
     *
     * HR management is role-aware, not just permission-gated: the Owner (who
     * also holds every self-service and settings permission) gets the nine
     * HR pages collapsed into a single "قسم الموارد البشرية" dropdown to keep
     * their sidebar scannable, while an HR Manager — whose daily workspace
     * this is — sees them as flat top-level items. Both shapes reuse the
     * exact same render loop below; only the $hrGroup config differs (see
     * children vs flat items), so dropdown-vs-flat is entirely generic and
     * needs no special-cased markup. Only the Access & Permissions group
     * keeps its own permanent collapsible wrapper, since it stays owner-only
     * regardless of viewer.
     */
    /*
     * Ordered by operational frequency, not by entity hierarchy (the previous
     * order was Departments -> Employees -> Contracts -> …, which is how the
     * data model is shaped, not how the day is spent). An HR Manager touches
     * attendance and leave every morning and the department list a few times a
     * year, so the tiers below run:
     *
     *   1. daily transactional work   (attendance, leave, team tasks)
     *   2. the core people registry   (employees, contracts)
     *   3. the recruitment pipeline   (jobs -> applications, in that order)
     *   4. periodic cycles            (evaluations, assets)
     *   5. org structure / config     (departments)
     */
    $hrManagementItems = [
        [
            'label' => 'سجل الحضور والغياب',
            'route' => 'hr.attendance.index',
            'pattern' => 'hr.attendance.*',
            'permission' => 'hr.attendance.view_any',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />',
        ],
        [
            'label' => 'إدارة الإجازات',
            'route' => 'hr.leaves.index',
            'pattern' => 'hr.leaves.*',
            'permission' => 'hr.leaves.view_any',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />',
        ],
        [
            'label' => 'مهام الفريق',
            'route' => 'hr.tasks.index',
            'pattern' => 'hr.tasks.*',
            'permission' => 'hr.tasks.access',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586M4.5 9.75l1.5 1.5 3-3.75" />',
        ],
        [
            'label' => 'الموظفين',
            'route' => 'hr.employees.index',
            'pattern' => 'hr.employees.*',
            'permission' => 'hr.employees.view_any',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.98 5.98 0 00-.34-2.004M18 18.72H6.5m11.5 0a9.07 9.07 0 01-.34 2.004M6.5 18.72a5.98 5.98 0 01-.34-2.004m0 0A5.995 5.995 0 0112 15c1.985 0 3.753.97 4.84 2.476M6.16 16.716A5.995 5.995 0 0112 15m0-9.75a3 3 0 110 6 3 3 0 010-6z" />',
        ],
        [
            'label' => 'موظفون بلا حسابات',
            'route' => 'hr.employees.without-accounts',
            'pattern' => 'hr.employees.without-accounts',
            'permission' => 'hr.employees.create',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />',
        ],
        [
            'label' => 'العقود',
            'route' => 'hr.contracts.index',
            'pattern' => 'hr.contracts.*',
            'permission' => 'hr.contracts.view_any',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />',
        ],
        [
            'label' => 'الوظائف والتوظيف',
            'route' => 'hr.jobs.index',
            'pattern' => 'hr.jobs.*',
            'permission' => 'hr.jobs.view_any',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0" />',
        ],
        [
            'label' => 'طلبات التقديم',
            'route' => 'hr.applications.index',
            'pattern' => 'hr.applications.*',
            'permission' => 'hr.applications.view_any',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661Z" />',
        ],
        [
            'label' => 'تقييمات الأداء',
            'route' => 'hr.evaluations.index',
            'pattern' => 'hr.evaluations.*',
            'permission' => 'hr.evaluations.access',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5m.75-9 3-3 2.148 2.148A12.061 12.061 0 0 1 16.5 7.605" />',
        ],
        [
            'label' => 'إدارة العُهد والأصول',
            'route' => 'tenant.assets.index',
            'pattern' => 'tenant.assets.*',
            'permission' => 'hr.assets.view_any',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />',
        ],
        [
            'label' => 'الأقسام',
            'route' => 'hr.departments.index',
            'pattern' => 'hr.departments.*',
            'permission' => 'hr.departments.view_any',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21" />',
        ],
    ];

    /*
     * Finance mirrors the HR shape exactly (see the note above): an Owner gets
     * these collapsed into one "قسم المالية" dropdown to keep their sidebar
     * scannable, while a Finance Manager — whose daily workspace this is —
     * sees them flat. Same render loop, only the group config differs.
     *
     * Order already followed the frequency tiers when the module was built and
     * is deliberately unchanged: the dashboard a Finance Manager lands on, then
     * the three active workflows (payroll, expenses, settlements), then the two
     * configuration screens. Keep new entries in that shape — a config screen
     * added above the workflows is the regression to watch for.
     */
    $financeItems = [
        [
            'label' => 'لوحة التحكم المالية',
            'route' => 'tenant.finance.dashboard',
            'pattern' => 'tenant.finance.dashboard',
            'permission' => 'finance.dashboard.view',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />',
        ],
        [
            'label' => 'مسيرات الرواتب',
            'route' => 'finance.payroll-runs.index',
            'pattern' => 'finance.payroll-runs.*|finance.payslips.*',
            'permission' => 'finance.payroll.view_any',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
        ],
        [
            'label' => 'المصروفات',
            'route' => 'finance.expenses.index',
            'pattern' => 'finance.expenses.*|finance.expense-categories.*',
            'permission' => 'finance.expenses.view_any',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0c1.1.128 1.907 1.077 1.907 2.185ZM9.75 9h.008v.008H9.75V9Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm4.125 4.5h.008v.008h-.008V13.5Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />',
        ],
        [
            'label' => 'تسويات نهاية الخدمة',
            'route' => 'finance.offboarding.index',
            'pattern' => 'finance.offboarding.*',
            'permission' => 'finance.offboarding.view_any',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />',
        ],
        [
            'label' => 'بنود البدلات والاستقطاعات',
            'route' => 'finance.line-item-types.index',
            'pattern' => 'finance.line-item-types.*',
            'permission' => 'finance.line_item_types.manage',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />',
        ],
        [
            'label' => 'إعدادات نهاية الخدمة',
            'route' => 'finance.settings.edit',
            'pattern' => 'finance.settings.*',
            'permission' => 'finance.settings.manage',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.272-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />',
        ],
    ];

    $isHrManagementOwnerView = false;
@endphp
@hasrole('Owner')
    @php
        $isHrManagementOwnerView = true;

        // @hasrole() calls hasRole() directly with no cleanup, which caches
        // the `roles` relation on this request's user instance scoped to
        // whichever team id is active right now (the tenant's own). Left
        // cached, the next team-switching Gate check below (Gate::before ->
        // isPlatformSuperAdmin() -> isTenantOwner()) would reuse it via a
        // no-op loadMissing() instead of re-querying under its own team —
        // the exact stale-cache bug already fixed at the source in
        // User::isPlatformSuperAdmin()/isTenantOwner(). Unset it so every
        // check below always queries fresh.
        auth()->user()?->unsetRelation('roles');
    @endphp
@endhasrole

@php
    $hrGroup = $isHrManagementOwnerView
        ? [
            'label' => 'قسم الموارد البشرية',
            'items' => [
                [
                    'label' => 'قسم الموارد البشرية',
                    'pattern' => 'hr.attendance.*|hr.leaves.*|hr.tasks.*|hr.employees.*|hr.contracts.*|hr.jobs.*|hr.applications.*|hr.evaluations.*|tenant.assets.*|hr.departments.*',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21" />',
                    'children' => $hrManagementItems,
                ],
            ],
        ]
        : [
            'label' => 'الموارد البشرية',
            'items' => $hrManagementItems,
        ];

    $financeGroup = $isHrManagementOwnerView
        ? [
            'label' => 'قسم المالية',
            'items' => [
                [
                    'label' => 'قسم المالية',
                    'pattern' => 'tenant.finance.dashboard|finance.payroll-runs.*|finance.payslips.*|finance.line-item-types.*|finance.expenses.*|finance.expense-categories.*|finance.offboarding.*|finance.settings.*',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
                    'children' => $financeItems,
                ],
            ],
        ]
        : [
            'label' => 'المالية والرواتب',
            'items' => $financeItems,
        ];

    /*
     * "الرئيسية" points at the dashboard this viewer actually lands on, so the
     * active-state highlight matches the page they are looking at. The /app/dashboard
     * dispatcher (DashboardController::index) performs the same resolution
     * server-side — this only keeps the link and its highlight honest, and
     * mirrors the dispatcher's Owner-first ordering for the same reason: an
     * Owner passes every @can() through the Gate::before bypass.
     */
    $homeRoute = 'dashboard';
    $homePattern = 'dashboard';

    if (! $isHrManagementOwnerView) {
        // Same Finance -> HR -> Employee ordering as DashboardController: a
        // Finance Manager also holds hr.my_dashboard.view via the self-service
        // bucket, so the narrower check has to come first.
        if (auth()->user()?->can('finance.dashboard.view')) {
            $homeRoute = 'tenant.finance.dashboard';
            $homePattern = 'tenant.finance.dashboard';
        } elseif (auth()->user()?->can('hr.dashboard.view')) {
            $homeRoute = 'tenant.hr.dashboard';
            $homePattern = 'tenant.hr.dashboard';
        } elseif (auth()->user()?->can('hr.my_dashboard.view')) {
            $homeRoute = 'tenant.hr.employee.dashboard';
            $homePattern = 'tenant.hr.employee.dashboard';
        }
    }

    /*
     * ─────────────────────────────────────────────────────────────────────
     * GROUP ORDER IS A UX CONTRACT — four tiers, top to bottom:
     *
     *   L1  where the day starts        نظرة عامة، الخدمة الذاتية
     *   L2  active management work      الموارد البشرية، المالية، التواصل
     *   L3  reports, history, recovery  التقارير والسجلات
     *   L4  administration & config     إدارة الوصول، الإعدادات
     *
     * What this fixed: "نظرة عامة" used to carry الرئيسية *plus* reports, the
     * audit log and the trash — three L3 items pinned above every daily
     * workflow, so the first thing anyone saw was retrospective tooling. And
     * "الإعدادات" carried التعميمات and رسائل التواصل, which are recurring
     * operational work, not configuration.
     *
     * Every route, pattern, permission and icon below is carried over
     * unchanged; only position and the group each item sits in have moved.
     * ─────────────────────────────────────────────────────────────────────
     */
    $navGroups = [
        [
            'label' => 'نظرة عامة',
            'items' => [
                [
                    'label' => 'الرئيسية',
                    'route' => $homeRoute,
                    'pattern' => $homePattern,
                    'permission' => 'tenant.dashboard.view',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.955-8.955a1.125 1.125 0 011.59 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75" />',
                ],
            ],
        ],
        [
            'label' => 'الخدمة الذاتية',
            'items' => [
                /*
                 * Each self-service feature is its own route. The former
                 * "مساحتي الخاصة" hub — which nested these behind ?tab= query
                 * params on a single page — has been retired, so every item
                 * below now has a distinct pattern and highlights independently.
                 *
                 * Ordered by how often one person opens them: check-in/out is
                 * twice a day, tasks daily, leave occasionally, payslips
                 * monthly, evaluations once a cycle. This group sits second
                 * because for the majority of users — every Employee — it is
                 * the entire application.
                 */
                [
                    'label' => 'تسجيل الحضور والانصراف',
                    'route' => 'tenant.hr.my-attendance',
                    'pattern' => 'tenant.hr.my-attendance*',
                    'permission' => 'hr.attendance.check_in_out',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7.864 4.243A7.5 7.5 0 0 1 19.5 10.5c0 2.92-.556 5.709-1.568 8.268M5.742 6.364A7.465 7.465 0 0 0 4.5 10.5a7.464 7.464 0 0 1-1.15 3.993m1.989 3.559A11.209 11.209 0 0 0 8.25 10.5a3.75 3.75 0 1 1 7.5 0c0 .527-.021 1.049-.064 1.565M12 10.5a14.94 14.94 0 0 1-3.6 9.75m6.633-4.596a18.666 18.666 0 0 1-2.485 5.33" />',
                ],
                [
                    'label' => 'مهامي',
                    'route' => 'tenant.hr.my-tasks',
                    'pattern' => 'tenant.hr.my-tasks*',
                    'permission' => 'hr.my_tasks.view',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
                ],
                [
                    'label' => 'طلبات الإجازة',
                    'route' => 'tenant.hr.my-leaves',
                    'pattern' => 'tenant.hr.my-leaves*',
                    'permission' => 'hr.my_leaves.view',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />',
                ],
                [
                    'label' => 'قسائم رواتبي',
                    'route' => 'tenant.finance.my-payslips',
                    'pattern' => 'tenant.finance.my-payslips*',
                    'permission' => 'hr.my_payslips.view',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />',
                ],
                [
                    'label' => 'تقييماتي',
                    'route' => 'tenant.hr.my-evaluations',
                    'pattern' => 'tenant.hr.my-evaluations',
                    'permission' => 'hr.my_evaluations.view',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.562.562 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />',
                ],
            ],
        ],
        $hrGroup,
        $financeGroup,
        [
            /*
             * L2, not L4. Both of these are recurring inbound work — an
             * announcement gets posted, a website enquiry needs answering —
             * and they sat under "الإعدادات" only because that group had
             * become the default home for anything not HR or Finance.
             */
            'label' => 'التواصل والإعلانات',
            'items' => [
                [
                    'label' => 'التعميمات والإعلانات',
                    'route' => 'tenant.announcements.index',
                    'pattern' => 'tenant.announcements.*',
                    'permission' => 'tenant.announcements.view_any',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.932 20.932 0 01-1.793-6.036M10.34 6.66a20.941 20.941 0 00-1.793-6.036c-.267-.578.976-.78 1.527-.461l.657.38c.523.302.71.961.463 1.511a20.902 20.902 0 01-.985 2.783m0 0A20.918 20.918 0 0113.58 12a20.918 20.918 0 01-3.24 5.34m6.31-13.16a20.932 20.932 0 011.793 6.036c.267.578-.976.78-1.527.461l-.657-.38c-.523-.302-.71-.961-.463-1.511.4-.891.732-1.821.985-2.783m0 9.18c.253-.962.584-1.892.985-2.783.247-.55.06-1.21-.463-1.511l-.657-.38c-.551-.318-1.26-.117-1.527.461a20.932 20.932 0 01-1.793 6.036" />',
                ],
                [
                    'label' => 'رسائل التواصل',
                    'route' => 'tenant.contact-messages.index',
                    'pattern' => 'tenant.contact-messages.*',
                    'permission' => 'tenant.contact_messages.view_any',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />',
                ],
            ],
        ],
        [
            /*
             * L3 — looking backwards rather than doing today's work. The trash
             * is paired with the audit log rather than filed under settings,
             * matching how the Platform Console already groups the same two
             * (see layouts/partials/admin-sidebar.blade.php, "المنصّة").
             */
            'label' => 'التقارير والسجلات',
            'items' => [
                [
                    'label' => 'التقارير والتصدير',
                    'route' => 'tenant.reports.index',
                    'pattern' => 'tenant.reports.*',
                    'permission' => 'tenant.reports.view',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 12L12 18.75m0 0 1.5-1.5M12 18.75V13.5m2.25-11.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />',
                ],
                [
                    'label' => 'سجل النشاط',
                    'route' => 'tenant.audit-logs.index',
                    'pattern' => 'tenant.audit-logs.*',
                    'permission' => 'tenant.audit_logs.view',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />',
                ],
                [
                    'label' => 'سلة المحذوفات',
                    'route' => 'tenant.trash.index',
                    'pattern' => 'tenant.trash.*',
                    'permission' => 'tenant.trash.view_any',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0V4.306c0-.846-.694-1.528-1.54-1.486a48.64 48.64 0 00-3.92 0c-.846-.042-1.54.64-1.54 1.486V5.79m7.5 0a48.667 48.667 0 00-7.5 0" />',
                ],
            ],
        ],
        [
            'label' => 'إدارة الوصول والصلاحيات',
            'items' => [
                [
                    'label' => 'إدارة الوصول والصلاحيات',
                    'pattern' => 'team.*|roles.*',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.98 5.98 0 00-.34-2.004m.94 3.198A3.001 3.001 0 0115.75 21H8.25a3 3 0 01-2.69-1.677M15.75 9.75a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />',
                    'children' => [
                        [
                            'label' => 'أعضاء الفريق',
                            'route' => 'team.index',
                            'pattern' => 'team.*',
                            'permission' => 'tenant.users.manage',
                        ],
                        [
                            'label' => 'الأدوار والصلاحيات',
                            'route' => 'roles.index',
                            'pattern' => 'roles.*',
                            'permission' => 'tenant.roles.manage',
                        ],
                    ],
                ],
            ],
        ],
        [
            /*
             * L4 — configuration only. التعميمات and رسائل التواصل used to sit
             * here and have moved up to "التواصل والإعلانات"; what remains is
             * genuinely set-once-and-revisit-rarely. جدول العمل and العطلات
             * الرسمية stay adjacent because they configure the same work
             * calendar the Work Ledger reconciles against.
             */
            'label' => 'الإعدادات',
            'items' => [
                [
                    'label' => 'إعدادات المؤسسة',
                    'route' => 'settings.company',
                    'pattern' => 'settings.company*',
                    'permission' => 'tenant.settings.view',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.174.1.347.223.52.337.294.191.66.237.983.094l1.22-.547a1.125 1.125 0 011.45.53l1.298 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.43.992a6.759 6.759 0 010 .255c-.008.378.137.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.45.533l-1.22-.547a1.125 1.125 0 00-.983.094 6.57 6.57 0 01-.52.337c-.332.183-.582.495-.645.87l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.52-.337 1.125 1.125 0 00-.983-.094l-1.22.547a1.125 1.125 0 01-1.45-.53l-1.298-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.298-2.247a1.125 1.125 0 011.45-.533l1.22.547c.323.144.69.098.983-.094.173-.114.346-.237.52-.337.332-.183.582-.495.644-.87l.214-1.281z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />',
                ],
                [
                    'label' => 'جدول العمل والورديات',
                    'route' => 'settings.work-schedule',
                    'pattern' => 'settings.work-schedule*',
                    'permission' => 'tenant.settings.view',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />',
                ],
                [
                    'label' => 'العطلات الرسمية',
                    'route' => 'tenant.holidays.index',
                    'pattern' => 'tenant.holidays.*',
                    'permission' => 'tenant.holidays.view_any',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm6.75-2.25h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm2.25-2.25h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" />',
                ],
                [
                    'label' => 'الموقع العام',
                    'route' => 'settings.portal',
                    'pattern' => 'settings.portal*',
                    'permission' => 'tenant.settings.view',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />',
                ],
                [
                    'label' => 'إدارة الاشتراك والخطط',
                    'route' => 'tenant.subscription.index',
                    'pattern' => 'tenant.subscription.*',
                    'permission' => 'tenant.subscription.view',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />',
                ],
            ],
        ],
    ];

    $tenantLogoUrl = auth()->user()?->tenant?->images()
        ->where('collection', 'logo')
        ->first()
        ?->url();
@endphp

<div
    x-show="sidebarOpen"
    x-cloak
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @click="closeSidebarDrawer()"
    class="fixed inset-0 z-40 bg-ink-950/60 lg:hidden"
    aria-hidden="true"
></div>

<aside
    id="tenant-sidebar"
    :class="{
        'translate-x-0 shadow-2xl pointer-events-auto': sidebarOpen,
        '-translate-x-full rtl:translate-x-full max-lg:pointer-events-none': ! sidebarOpen,
        'lg:w-20': sidebarCollapsed,
        'lg:w-64': ! sidebarCollapsed,
    }"
    :aria-hidden="(!sidebarOpen).toString()"
    class="fixed inset-y-0 start-0 z-50 flex w-64 max-w-[min(16rem,85vw)] shrink-0 -translate-x-full flex-col border-e border-mist-200 bg-white transition-transform duration-300 ease-out rtl:translate-x-full lg:static lg:z-auto lg:max-w-none lg:translate-x-0 lg:pointer-events-auto lg:shadow-none lg:rtl:translate-x-0 dark:border-ink-700 dark:bg-ink-900"
    role="navigation"
    aria-label="قائمة مساحة العمل"
>
    <div class="flex h-16 shrink-0 items-center gap-2 border-b border-mist-200 px-6 dark:border-ink-700/70" :class="sidebarCollapsed && 'lg:justify-center lg:px-0'">
        <a
            href="{{ route('dashboard') }}"
            class="flex min-w-0 items-center gap-2"
            :class="sidebarCollapsed && 'lg:justify-center'"
            title="الرئيسية"
            aria-label="الرئيسية — مدى"
            @click="closeSidebarDrawer()"
        >
            @if ($tenantLogoUrl)
                <img
                    src="{{ $tenantLogoUrl }}"
                    alt="{{ auth()->user()?->tenant?->name ?? 'مدى' }}"
                    class="h-8 max-h-8 w-auto max-w-[140px] shrink-0 object-contain object-start"
                    :class="sidebarCollapsed && 'lg:max-w-8'"
                >
            @else
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-500 font-display text-sm font-medium text-white shadow-glow">م</span>
                <div class="leading-tight" x-show="! sidebarCollapsed">
                    <span class="block font-display text-sm font-medium text-ink-900 dark:text-ink-50">مدى</span>
                    <span class="block text-xs font-medium text-mist-500">مساحة العمل</span>
                </div>
            @endif
        </a>
        <button
            type="button"
            @click="closeSidebarDrawer()"
            class="ms-auto rounded-lg p-1.5 text-mist-500 transition hover:bg-mist-100 lg:hidden dark:text-mist-400 dark:hover:bg-ink-800"
            aria-label="إغلاق القائمة"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-6">
        @foreach ($navGroups as $group)
            @php
                $groupPermissions = collect($group['items'])
                    ->flatMap(fn (array $item): array => $item['children'] ?? [$item])
                    ->pluck('permission')
                    ->filter()
                    ->values()
                    ->all();
            @endphp
            @canany($groupPermissions)
                <div>
                    <p class="px-3 text-xs font-semibold tracking-wide text-mist-500 dark:text-mist-400" x-show="! sidebarCollapsed">
                        {{ $group['label'] }}
                    </p>
                    <div class="mx-auto my-2 h-px w-6 bg-mist-200 dark:bg-ink-700" x-show="sidebarCollapsed" x-cloak></div>
                    <ul class="mt-2 space-y-1">
                        @foreach ($group['items'] as $item)
                            @if (! empty($item['children']))
                                @php
                                    $childPermissions = collect($item['children'])->pluck('permission')->filter()->values()->all();
                                    $childActive = collect($item['children'])->contains(
                                        fn (array $child): bool => request()->routeIs($child['pattern'] ?? $child['route'])
                                    );
                                @endphp
                                @canany($childPermissions)
                                    <li>
                                        <div x-data="{ open: {{ $childActive ? 'true' : 'false' }} }" class="space-y-1">
                                            <button
                                                type="button"
                                                @click="open = ! open"
                                                x-bind:title="sidebarCollapsed ? @js($item['label']) : null"
                                                :class="sidebarCollapsed && 'lg:justify-center'"
                                                @class([
                                                    'group flex w-full items-center gap-3 rounded-lg border-s-2 px-3 py-2 text-sm font-medium transition-all duration-300 ease-out active:scale-[0.98]',
                                                    'border-brand-500 bg-brand-500/10 text-brand-600 dark:text-brand-300' => $childActive,
                                                    'border-transparent text-ink-600 hover:border-mist-300 hover:bg-mist-100 dark:text-mist-300 dark:hover:border-ink-600 dark:hover:bg-ink-800' => ! $childActive,
                                                ])
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 {{ $childActive ? 'text-brand-500 dark:text-brand-300' : 'text-mist-400 group-hover:text-mist-500 dark:text-mist-500' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                    {!! $item['icon'] !!}
                                                </svg>
                                                <span x-show="! sidebarCollapsed" class="flex-1 text-start">{{ $item['label'] }}</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 transition-transform duration-200" :class="open && 'rotate-180'" x-show="! sidebarCollapsed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </button>
                                            <ul
                                                x-show="open && ! sidebarCollapsed"
                                                x-cloak
                                                x-transition
                                                class="ms-4 space-y-1 border-s border-mist-200 ps-3 dark:border-ink-700"
                                            >
                                                @foreach ($item['children'] as $child)
                                                    @can($child['permission'])
                                                        @if (Route::has($child['route']))
                                                            @php
                                                                $childIsActive = request()->routeIs($child['pattern'] ?? $child['route']);
                                                            @endphp
                                                            <li>
                                                                <a
                                                                    href="{{ route($child['route']) }}"
                                                                    wire:navigate
                                                                    @click="closeSidebarDrawer()"
                                                                    @class([
                                                                        'block rounded-lg px-3 py-1.5 text-sm transition duration-200',
                                                                        'bg-brand-500/10 font-semibold text-brand-600 dark:text-brand-300' => $childIsActive,
                                                                        'text-mist-500 hover:bg-mist-100 hover:text-ink-700 dark:text-mist-400 dark:hover:bg-ink-800 dark:hover:text-mist-100' => ! $childIsActive,
                                                                    ])
                                                                >{{ $child['label'] }}</a>
                                                            </li>
                                                        @endif
                                                    @endcan
                                                @endforeach
                                            </ul>
                                        </div>
                                    </li>
                                @endcanany
                            @else
                                @can($item['permission'])
                                    @if (Route::has($item['route']))
                                        @php
                                            $isActive = request()->routeIs($item['pattern'] ?? $item['route']);
                                        @endphp
                                        <li>
                                            <a
                                                href="{{ route($item['route'], $item['route_params'] ?? []) }}"
                                                wire:navigate
                                                @click="closeSidebarDrawer()"
                                                x-bind:title="sidebarCollapsed ? @js($item['label']) : null"
                                                :class="sidebarCollapsed && 'lg:justify-center'"
                                                @class([
                                                    'group flex items-center gap-3 rounded-lg border-s-2 px-3 py-2 text-sm font-medium transition-all duration-300 ease-out active:scale-[0.98]',
                                                    'border-brand-500 bg-brand-500/10 text-brand-600 dark:text-brand-300' => $isActive,
                                                    'border-transparent text-ink-600 hover:border-mist-300 hover:bg-mist-100 dark:text-mist-300 dark:hover:border-ink-600 dark:hover:bg-ink-800' => ! $isActive,
                                                ])
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 {{ $isActive ? 'text-brand-500 dark:text-brand-300' : 'text-mist-400 group-hover:text-mist-500 dark:text-mist-500' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                    {!! $item['icon'] !!}
                                                </svg>
                                                <span x-show="! sidebarCollapsed" class="{{ $isActive ? 'underline decoration-brand-500 decoration-2 underline-offset-4' : 'underline-offset-2' }}">{{ $item['label'] }}</span>
                                            </a>
                                        </li>
                                    @endif
                                @endcan
                            @endif
                        @endforeach
                    </ul>
                </div>
            @endcanany
        @endforeach
    </nav>

    <div class="hidden shrink-0 border-t border-mist-200 p-3 lg:block dark:border-ink-700/70">
        <button
            type="button"
            @click="toggleSidebar()"
            x-bind:title="sidebarCollapsed ? 'توسيع القائمة' : 'طيّ القائمة'"
            :class="sidebarCollapsed && 'lg:justify-center'"
            class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-mist-500 transition duration-200 hover:bg-mist-100 hover:text-ink-700 active:scale-[0.98] dark:text-mist-400 dark:hover:bg-ink-800 dark:hover:text-mist-100"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 transition-transform duration-300 rtl:-scale-x-100" :class="sidebarCollapsed && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
            <span x-show="! sidebarCollapsed">طيّ القائمة</span>
        </button>
    </div>
</aside>
