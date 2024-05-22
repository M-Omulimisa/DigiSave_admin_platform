<!-- resources/views/widgets/user_locations_map.blade.php -->
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">User Locations Map</h3>
    </div>
    <div class="box-body">
        <div id="map" style="width: 100%; height: 600px;"></div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var map = L.map('map').setView([1.3733, 32.2903], 7); // Coordinates for Uganda

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        var userLocations = @json($userLocations);

        userLocations.forEach(function(location) {
            if (location.lat && location.lon) {
                var marker = L.marker([location.lat, location.lon]).addTo(map)
                    .bindPopup(location.name);
            }
        });
    });
</script>
