<div class="card-header d-flex align-items-center justify-content-between">
    <!-- Left: Title -->
    <span>{{ $name }}</span>

    <!-- Center: Settings Button -->
    @if ($hasSettings && $randomId)
        <div class="mx-auto position-relative">
            <button id="mapSettingsBtn{{ $randomId }}" class="btn btn-primary btn-sm">
                <i class="fa fa-bars"></i>
            </button>

            <!-- Popup menu Depends on widget type -->
            <div id="mapSettingsMenu{{ $randomId }}" 
                class="card shadow-sm"
                style="display:none; position:absolute; top:100%; left:50%; transform:translateX(-50%); z-index:3000; min-width:180px; max-width:300px;">
                <div class="card-body p-2">

                    <!-- if widget is map -->
                    @if($widgetTypeId == 1)
                        <!-- Checkbox: Render Data Based on Map View toggle -->
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="toggleDataRenderinView{{ $randomId }}">
                            <label class="form-check-label" for="toggleDataRenderinView{{ $randomId }}" title="Render data in charts based on map view">
                                Render Data in Map View
                            </label>
                        </div>

                        <!-- Marker color selector -->
                        <div class="form-group mt-2">
                            <label for="colorSelect{{ $randomId }}" title="Changes color of markers/points">
                                Marker Color Picker
                            </label>
                            <select class="form-select" id="colorSelect" name="colors">
                                <option value="">-- Select a color --</option>
                                <option value="blue">Blue</option>
                                <option value="red">Red</option>
                                <option value="green">Green</option>
                            </select>
                            <!-- <input type="color" id="colorSelect{{ $randomId }}" value="#3388ff"> -->
                        </div>

                    
                    <!-- if widget is chart -->
                    @elseif ($widgetTypeId == 2 || $widgetTypeId == 3 || $widgetTypeId == 4)
                        <!-- Checkbox: Render Data Based on Map View toggle -->
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="toggleDataRenderinView{{ $randomId }}">
                            <label class="form-check-label" for="toggleDataRenderinView{{ $randomId }}" title="Render data in charts based on map view">
                                Render Data in Map View
                            </label>
                        </div>

                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Right: Delete button -->
    <form action="{{ route('profile.delete-widget', ['id' => $widgetId, 'dash_id' => $dashboardId]) }}" method="POST" style="display:inline-block;">
        @csrf
        <button type="submit" class="btn btn-secondary btn-sm rounded-sm fas fa-trash"></button>
    </form>
</div>

<!-- Toggle script -->>
@if ($hasSettings && $randomId)
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('mapSettingsBtn{{ $randomId }}');
    const menu = document.getElementById('mapSettingsMenu{{ $randomId }}');
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