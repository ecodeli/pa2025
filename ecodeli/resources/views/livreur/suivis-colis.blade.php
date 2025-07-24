@extends('layouts.app')
@section('title', 'Suivi de livraison avancé')

@section('content')
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="stylesheet" href="{{ secure_asset('css/livreur/suivis-colis.css') }}">
    <style>
        .badge {
            display: inline-block;
            margin-left: 10px;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            color: white;
            font-weight: bold;
        }
        .badge-picked { background-color: #007bff; }
        .badge-transit { background-color: #17a2b8; }
        .badge-arrived { background-color: #ffc107; color: black; }
        .badge-completed { background-color: #28a745; }
    </style>
    <div class="container py-4">
        <h2 class="mb-4">Suivi de vos livraisons en cours</h2>
        <div id="segments-container">Chargement...</div>
    </div>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
@endsection

@push('scripts')
    <script>
        const API_URL = "/api(";
        const token = localStorage.getItem('token');
        let maps = {};
        let segments = [];

        function getStatusBadge(step) {
            const map = {
                picked_up: '<span class="badge badge-picked">Récupéré</span>',
                in_transit: '<span class="badge badge-transit">En transit</span>',
                arrived: '<span class="badge badge-arrived">Arrivé</span>',
                completed: '<span class="badge badge-completed">Livré</span>',
            };
            return map[step] || '';
        }

        async function fetchSegments() {
            const res = await fetch(`${API_URL}/api/delivery/segments/active`, {
                headers: { Authorization: "Bearer " + token }
            });
            segments = await res.json();
            renderSegments(segments);
        }

        function renderSegments(segments) {
            const container = document.getElementById("segments-container");
            container.innerHTML = "";
            if (!segments.length) return container.innerHTML = "<p>Aucune livraison en cours.</p>";

            segments.forEach(s => {
                const segmentId = s.segment_id;

                const card = document.createElement("div");
                card.className = "card-segment";

                card.innerHTML = `
        <div class="card-header" onclick="toggleCard(${segmentId}, this)">
            <span>
                ${s.annonce_title || (s.start_city + " ➔ " + s.end_city)}
                ${getStatusBadge(s.latest_step)}
            </span>
            <span class="arrow">▼</span>
        </div>
        <div class="card-body" id="body-${segmentId}">
            <div id="map-${segmentId}" class="map mb-2"></div>

            <div class="progress-tracker" id="progress-${segmentId}">
                <div class="step" data-step="picked_up">Récupéré</div>
                <div class="step" data-step="in_transit">En transit</div>
                <div class="step" data-step="arrived">Arrivé</div>
                <div class="step" data-step="completed">Livré</div>
            </div>
            <button class="btn btn-outline-info" onclick="updateStatus(${segmentId}, 'picked_up')">J’ai récupéré le colis</button>
            <button class="btn btn-outline-secondary" onclick="updateStatus(${segmentId}, 'arrived')">Je suis arrivé</button>
            <button class="btn btn-success" onclick="completeSegment(${segmentId})">Livraison finale</button>
            <button class="btn btn-warning" onclick="showDropOptions(${segmentId})">Déposer ici</button>
            <div id="drop-${segmentId}" class="hidden mt-2">
                <label>Entrepôt (optionnel) :</label>
                <select id="warehouse-${segmentId}" class="input mb-2">
                    <option value="">Aucun – dépôt libre</option>
                </select>
                <button class="btn btn-outline-primary" onclick="confirmDrop(${segmentId})">Confirmer le dépôt</button>
            </div>
        </div>
                `;

                container.appendChild(card);

                const depLat = parseFloat(s.departure_lat);
                const depLng = parseFloat(s.departure_lng);
                const arrLat = parseFloat(s.arrival_lat);
                const arrLng = parseFloat(s.arrival_lng);

                const coordsValid = [depLat, depLng, arrLat, arrLng].every(coord => Number.isFinite(coord));

                if (!coordsValid) {
                    document.getElementById(`map-${segmentId}`).innerHTML = "<p>Coordonnées invalides.</p>";
                    return;
                }

                const departure = L.latLng(depLat, depLng);
                const arrival = L.latLng(arrLat, arrLng);

                const map = L.map(`map-${segmentId}`);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
                L.marker(departure).addTo(map).bindPopup("Départ : " + s.departure_address);
                L.marker(arrival).addTo(map).bindPopup("Arrivée : " + s.delivery_address);

                const ORS_API_KEY = '5b3ce3597851110001cf6248f8a86418769d47b69aa4c4df724cc723';

                fetch(`https://api.openrouteservice.org/v2/directions/driving-car?api_key=${ORS_API_KEY}&start=${depLng},${depLat}&end=${arrLng},${arrLat}`)
                    .then(res => res.json())
                    .then(data => {
                        const coords = data.features[0].geometry.coordinates.map(coord => [coord[1], coord[0]]);
                        const polyline = L.polyline(coords, { color: "blue", weight: 4 }).addTo(map);
                        const bounds = polyline.getBounds();
                        maps[segmentId] = { map, bounds };
                    })
                    .catch(err => {
                        console.error("Erreur ORS :", err);
                        const fallback = L.polyline([departure, arrival], { color: "red", dashArray: "5, 10" }).addTo(map);
                        const bounds = fallback.getBounds();
                        maps[segmentId] = { map, bounds };
                    });

                highlightProgress(segmentId, s.latest_step || "");
            });
        }

        function highlightProgress(id, status) {
            const steps = ["picked_up", "in_transit", "arrived", "completed"];
            const currentIndex = steps.indexOf(status);

            steps.forEach(step => {
                const el = document.querySelector(`#progress-${id} .step[data-step="${step}"]`);
                if (el) el.classList.remove("active");
            });
            steps.forEach((step, i) => {
                const el = document.querySelector(`#progress-${id} .step[data-step="${step}"]`);
                if (el && i <= currentIndex) el.classList.add("active");
            });
        }

        function updateStatus(segmentId, status) {
            const steps = ["picked_up", "in_transit", "arrived", "completed"];
            const requestedIndex = steps.indexOf(status);

            const segment = segments.find(seg => seg.segment_id === segmentId);
            const currentIndex = steps.indexOf(segment?.latest_step || '');

            if (requestedIndex <= currentIndex) return alert("Étape déjà validée ou invalide.");
            if (requestedIndex !== currentIndex + 1) return alert("Veuillez suivre l'ordre des étapes.");

            const update = (statut) =>
                fetch(`${API_URL}/api/delivery/status`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Authorization: "Bearer " + token
                    },
                    body: JSON.stringify({ segment_id: segmentId, status: statut })
                });

            if (status === "picked_up") {
                update("picked_up")
                    .then(r => r.json())
                    .then(resp => {
                        if (!resp.success) return alert(resp.error || "Erreur lors de la récupération");
                        highlightProgress(segmentId, "picked_up");

                        return update("in_transit")
                            .then(r2 => r2.json())
                            .then(resp2 => {
                                if (!resp2.success) return alert(resp2.error || "Erreur lors du passage en transit");
                                highlightProgress(segmentId, "in_transit");
                            });
                    });
            } else {
                update(status)
                    .then(r => r.json())
                    .then(resp => {
                        if (resp.success) highlightProgress(segmentId, status);
                        else alert(resp.error || "Erreur de mise à jour");
                    });
            }
        }

        function toggleCard(id, header) {
            const body = document.getElementById(`body-${id}`);
            body.classList.toggle("active");
            const arrow = header.querySelector('.arrow');
            arrow.textContent = body.classList.contains("active") ? "▲" : "▼";

            setTimeout(() => {
                const map = maps[id]?.map;
                const bounds = maps[id]?.bounds;
                if (map && bounds) {
                    map.invalidateSize();
                    map.fitBounds(bounds, { padding: [50, 50] });
                }
            }, 300);
        }

        function showDropOptions(id) {
            document.getElementById(`drop-${id}`).classList.remove("hidden");
            const select = document.getElementById(`warehouse-${id}`);
            select.innerHTML = `<option value="">Aucun – dépôt libre</option>`;

            fetch(`${API_URL}/api/warehouses`, {
                headers: { Authorization: "Bearer " + token }
            }).then(res => res.json()).then(data => {
                data.forEach(w => {
                    const opt = document.createElement("option");
                    opt.value = w.warehouse_id;
                    opt.textContent = `${w.name} (${w.city})`;
                    select.appendChild(opt);
                });
            });
        }

        function confirmDrop(id) {
            const warehouseId = document.getElementById(`warehouse-${id}`).value;
            navigator.geolocation.getCurrentPosition(pos => {
                fetch(`${API_URL}/api/delivery/split`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Authorization: "Bearer " + token
                    },
                    body: JSON.stringify({
                        segment_id: id,
                        lat: pos.coords.latitude,
                        lng: pos.coords.longitude,
                        warehouse_id: warehouseId || null
                    })
                }).then(r => r.json()).then(resp => {
                    if (resp.success) {
                        alert("Segment terminé. Un autre livreur peut continuer.");
                        fetchSegments();
                    } else alert(resp.error || "Erreur de dépôt");
                });
            });
        }

        function completeSegment(id) {
            window.location.href = "/livreur/mes-trajets";
        }

        fetchSegments();
    </script>
@endpush
