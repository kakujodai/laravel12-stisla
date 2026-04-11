<div class="card-header d-flex align-items-center justify-content-between">
    <!-- Left: Title -->
    <span>{{ $name }}</span>

    <!-- Center: Settings Button -->
    @if ($hasSettings)
        <div class="mx-auto position-relative">
            <!-- Popup menu Depends on widget type -->
            <a href="{{ route('profile.edit-widgets', ['id' => $widgetId, 'dash_id' => $dashboardId]) }}" method="HEAD" style="display:inline-block;"class="btn btn-primary btn-sm">
                <i class="fa fa-bars"></i>
            </a>
        </div>
    @endif

    <!-- Right: Delete button -->
    <form action="{{ route('profile.delete-widget', ['id' => $widgetId, 'dash_id' => $dashboardId]) }}" method="POST" style="display:inline-block;">
        @csrf
        <button type="submit" class="btn btn-secondary btn-sm rounded-sm">
            <i class="fas fa-trash" aria-hidden="true"></i>
        </button>
    </form>
</div>