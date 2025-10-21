<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Tracking Livreurs</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body { margin:0; font-family: Arial, sans-serif; }
        #map { height: 90vh; width: 100%; }
        #controls { padding: 8px; background: #f0f0f0; }
        .tracking-marker {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 0 4px rgba(0,0,0,0.5);
        }
        #addressList {
            position: absolute;
            top: 60px;
            right: 10px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            max-height: 80vh;
            overflow-y: auto;
            width: 300px;
            z-index: 1000;
        }
        .address-item {
            padding: 10px;
            margin-bottom: 10px;
            border-left: 4px solid;
            background: #f9f9f9;
            border-radius: 4px;
            font-size: 13px;
        }
        .address-item strong {
            display: block;
            margin-bottom: 5px;
        }
        .address-item small {
            color: #666;
            display: block;
            margin-top: 5px;
        }
        .loading {
            color: #999;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div id="controls">
        <strong>Tracking toutes les 20s — Leaflet + OSM</strong>
        &nbsp;|&nbsp;
        <label><input type="checkbox" id="simulate" checked> Simulate</label>
        &nbsp;|&nbsp;
        <button id="startBtn">Démarrer</button>
        <span id="status"></span>
    </div>
    <div id="map"></div>
    
    <div id="addressList">
        <h3 style="margin-top: 0;">Adresses Tracking</h3>
        <div id="addressContainer"></div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const ORS_API_KEY = "eyJvcmciOiI1YjNjZTM1OTc4NTExMTAwMDFjZjYyNDgiLCJpZCI6ImUzYTAwYmNjMmFjOTRkOTQ5YmU0MmYwYTkzOWUxODBkIiwiaCI6Im11cm11cjY0In0=";
        const UPDATE_INTERVAL = 20000;

        const map = L.map('map').setView([33.5731, -7.5898], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // Reverse Geocoding Function
        async function getAddress(lat, lng) {
            try {
                const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`;
                const response = await fetch(url, {
                    headers: {
                        'User-Agent': 'Laravel Tracking App'
                    }
                });
                const data = await response.json();
                
                if (data.address) {
                    const addr = data.address;
                    return {
                        full: data.display_name,
                        road: addr.road || addr.street || '',
                        city: addr.city || addr.town || addr.village || '',
                        postcode: addr.postcode || '',
                        country: addr.country || ''
                    };
                }
                return null;
            } catch (error) {
                console.error('Error fetching address:', error);
                return null;
            }
        }

        // Save address to Laravel backend
        async function saveAddress(livreurName, lat, lng, address) {
            try {
                const response = await fetch('/api/tracking/save-address', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        livreur_name: livreurName,
                        latitude: lat,
                        longitude: lng,
                        address: address
                    })
                });
                const data = await response.json();
                console.log('Address saved:', data);
            } catch (error) {
                console.error('Error saving address:', error);
            }
        }

        // Display address in sidebar
        function displayAddress(livreurName, address, color, lat, lng) {
            const container = document.getElementById('addressContainer');
            const time = new Date().toLocaleTimeString('fr-FR');
            
            const addressHtml = `
                <div class="address-item" style="border-left-color: ${color}">
                    <strong>${livreurName}</strong>
                    <div>${address.road || 'Route inconnue'}</div>
                    <div>${address.city} ${address.postcode || ''}</div>
                    <small>${time}</small>
                    <small>Lat: ${lat.toFixed(5)}, Lng: ${lng.toFixed(5)}</small>
                </div>
            `;
            
            container.insertAdjacentHTML('afterbegin', addressHtml);
        }

        async function getRoute(start, end) {
            const url = `https://api.openrouteservice.org/v2/directions/driving-car?api_key=${ORS_API_KEY}&start=${start.lng},${start.lat}&end=${end.lng},${end.lat}`;
            const response = await fetch(url);
            const data = await response.json();
            const coords = data.features[0].geometry.coordinates.map(c => [c[1], c[0]]);
            return coords;
        }

        function moveMarker(marker, route, index) {
            if (index < route.length) {
                marker.setLatLng(route[index]);
                map.panTo(route[index]);
            }
        }

        class Livreur {
            constructor(name, start, end, color) {
                this.name = name;
                this.start = start;
                this.end = end;
                this.color = color;
                this.marker = null;
                this.polyline = null;
                this.route = [];
                this.index = 0;
                this.interval = null;
                this.trackingMarkers = [];
            }

            async init() {
                this.route = await getRoute(this.start, this.end);
                this.polyline = L.polyline(this.route, { color: this.color, weight: 4, opacity: 0.6 }).addTo(map);
                this.marker = L.marker(this.route[0]).addTo(map).bindPopup(`${this.name}`);
                this.index = 0;
            }

            async addTrackingMarker(position) {
                const trackMarker = L.circleMarker(position, {
                    radius: 8,
                    fillColor: this.color,
                    color: '#ffffff',
                    weight: 3,
                    opacity: 1,
                    fillOpacity: 1,
                    zIndexOffset: 1000
                }).addTo(map);
                
                // Get address for this position
                const lat = position[0];
                const lng = position[1];
                
                trackMarker.bindPopup(`<div class="loading">Chargement de l'adresse...</div>`);
                
                // Fetch address
                const address = await getAddress(lat, lng);
                
                if (address) {
                    const popupContent = `
                        <strong>${this.name}</strong><br>
                        ${address.road || 'Route inconnue'}<br>
                        ${address.city} ${address.postcode || ''}<br>
                        <small>${lat.toFixed(5)}, ${lng.toFixed(5)}</small>
                    `;
                    trackMarker.bindPopup(popupContent);
                    
                    // Display in sidebar
                    displayAddress(this.name, address, this.color, lat, lng);
                    
                    // Save to database
                    await saveAddress(this.name, lat, lng, address);
                }
                
                this.trackingMarkers.push(trackMarker);
                console.log(`Cercle ajouté à: ${position}, Total: ${this.trackingMarkers.length}`);
            }

            startTracking() {
                if (this.interval) return;
                
                this.addTrackingMarker(this.route[this.index]);
                
                this.interval = setInterval(() => {
                    this.index++;
                    if (this.index < this.route.length) {
                        moveMarker(this.marker, this.route, this.index);
                        this.addTrackingMarker(this.route[this.index]);
                    } else {
                        clearInterval(this.interval);
                        this.marker.bindPopup(`${this.name} (arrivé)`).openPopup();
                    }
                }, UPDATE_INTERVAL);
            }

            stopTracking() {
                clearInterval(this.interval);
                this.interval = null;
            }

            reset() {
                this.trackingMarkers.forEach(m => map.removeLayer(m));
                this.trackingMarkers = [];
                this.index = 0;
                if (this.marker) this.marker.setLatLng(this.route[0]);
            }
        }

        const livreurs = [
            new Livreur("Livreur 1 🛵", { lat: 33.5731, lng: -7.5898 }, { lat: 33.5860, lng: -7.6150 }, "#FF4444"),
            new Livreur("Livreur 2 🚚", { lat: 33.5800, lng: -7.6050 }, { lat: 33.5650, lng: -7.5900 }, "#44FF44"),
            new Livreur("Livreur 3 🚗", { lat: 33.5780, lng: -7.6000 }, { lat: 33.5700, lng: -7.5850 }, "#4444FF")
        ];

        const startBtn = document.getElementById("startBtn");

        async function initAll() {
            for (const livreur of livreurs) await livreur.init();
        }

        function startAll() {
            for (const livreur of livreurs) livreur.startTracking();
            startBtn.textContent = "Arrêter";
        }

        function stopAll() {
            for (const livreur of livreurs) livreur.stopTracking();
            startBtn.textContent = "Démarrer";
        }

        let started = false;
        startBtn.addEventListener("click", async () => {
            if (!started) {
                await initAll();
                startAll();
                started = true;
            } else {
                stopAll();
                started = false;
            }
        });
    </script>
</body>
</html>