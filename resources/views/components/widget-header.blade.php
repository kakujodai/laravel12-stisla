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
        <button type="submit" class="btn btn-secondary btn-sm rounded-sm fas fa-trash"></button>
    </form>
</div>

<!-- Toggle script -->
@if ($hasSettings)
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('settingsBtn{{ $randomId }}');
    const menu = document.getElementById('settingsMenu{{ $randomId }}');
    if (!btn || !menu) return;
    
    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        menu.style.display = (menu.style.display === 'none' || menu.style.display === '') 
            ? 'block' 
            : 'none';
    });
    
    // Hide the menu if clicking elsewhere
    document.addEventListener('click', (e) => {
        if (!menu.contains(e.target) && e.target !== btn) {
            menu.style.display = 'none';
        }
    });
});
</script>
@endif