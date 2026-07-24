<div>
    <h1>Welcome back, {{ auth()->user()->name }}</h1>
    <p>{{ auth()->user()->tenant?->name }} &middot; {{ auth()->user()->tenant?->status->label() }}</p>

    <div>
        <div>
            <p>Employees</p>
            <p>0</p>
        </div>
        <div>
            <p>Active Projects</p>
            <p>0</p>
        </div>
        <div>
            <p>Open Leave Requests</p>
            <p>0</p>
        </div>
    </div>

    <div>
        <p>Your workspace is ready</p>
        <p>Org Settings, Departments, and Employees are the next Phase 1 slices — this dashboard will fill in as each module ships.</p>
    </div>
</div>
