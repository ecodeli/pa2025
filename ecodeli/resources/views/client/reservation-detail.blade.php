@extends('layouts.app')
@section('title', 'Détail de la Réservation')

@section('content')
    <link rel="stylesheet" href="{{ secure_asset('css/client/reservation_detail.css') }}">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <div class="reservation-container">
        <div id="booking-details" class="text-center text-gray-500">Chargement des informations...</div>
        <div id="map" style="height: 400px; margin-top: 20px;"></div>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const token = localStorage.getItem("token");
        const bookingId = window.location.pathname.split("/").pop();
        const API_URL = "/api";
        const API_KEY = "5b3ce3597851110001cf6248f8a86418769d47b69aa4c4df724cc723";

        let map, routeLine, serviceMarker, serviceCircle, departureMarker, arrivalMarker;

        function setupMap() {
            map = L.map('map').setView([48.8566, 2.3522], 6);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: 'OpenStreetMap contributors'
            }).addTo(map);
        }

        async function showColisRoute(depLat, depLng, arrLat, arrLng) {
            departureMarker = L.marker([depLat, depLng]).addTo(map).bindPopup('Départ').openPopup();
            arrivalMarker = L.marker([arrLat, arrLng]).addTo(map).bindPopup('Arrivée').openPopup();

            const res = await fetch(`https://api.openrouteservice.org/v2/directions/driving-car/geojson`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': API_KEY
                },
                body: JSON.stringify({
                    coordinates: [[depLng, depLat], [arrLng, arrLat]]
                })
            });
            const data = await res.json();
            const coordsLine = data.features[0].geometry.coordinates.map(c => [c[1], c[0]]);
            routeLine = L.polyline(coordsLine, { color: 'blue' }).addTo(map);
            map.fitBounds(routeLine.getBounds(), { padding: [50, 50] });
        }

        function showServiceMarker(lat, lng, radiusKm) {
            serviceMarker = L.marker([lat, lng]).addTo(map).bindPopup('Lieu du service').openPopup();
            serviceCircle = L.circle([lat, lng], {
                color: 'blue',
                fillColor: '#blue',
                fillOpacity: 0.2,
                radius: radiusKm * 1000
            }).addTo(map);
            map.setView([lat, lng], 13);
        }

        document.addEventListener("DOMContentLoaded", async () => {
            setupMap();
            const box = document.getElementById("booking-details");

            try {
                const res = await fetch(`${API_URL}/api/bookings/${bookingId}`, {
                    headers: { Authorization: "Bearer " + token }
                });

                const data = await res.json();
                if (!res.ok) throw new Error();

                box.innerHTML = `
                    <div class="reservation-card">
                        <h2>${data.annonce_title}</h2>
                        <p class="description">📝 ${data.details || 'Pas de description fournie.'}</p>
                        <div class="meta">
                            <p><strong>Statut :</strong> ${data.status}</p>
                            <p><strong>Jour du service :</strong> ${new Date(data.date).toLocaleDateString()}</p>
                            <p><strong>Horaire :</strong> ${data.start_time} → ${data.end_time}</p>
                            <p><strong>Ville :</strong> ${data.city}</p>
                            <p><strong>Prix :</strong> ${data.price != null ? data.price + ' €' : 'Non précisé'}</p>
                        </div>
                        <div class="provider">
                            <img src="${data.avatar_url || 'https://api.dicebear.com/7.x/initials/svg?seed=' + encodeURIComponent(data.provider_name)}" alt="avatar" />
                            <span><strong>${data.provider_name}</strong></span>
                            <button class="btn-contact" onclick="window.location.href='/messages/'">Contacter le prestataire</button>
                        </div>
                        <a href="/client/suivis-service" class="btn-back">← Retour aux réservations</a>

                        <div class="actions">
    <button class="btn-cancel" onclick="cancelBooking(${data.booking_id})">Annuler la réservation</button>
</div>

                    </div>
                `;

                if (data.type === 'colis' && data.departure_lat && data.arrival_lat) {
                    showColisRoute(data.departure_lat, data.departure_lng, data.arrival_lat, data.arrival_lng);
                } else if (data.type === 'service' && data.departure_lat) {
                    showServiceMarker(data.departure_lat, data.departure_lng, data.service_radius || 5);
                }

            } catch (err) {
                box.innerHTML = `<p class="text-red-500">Impossible de charger la réservation</p>`;
            }
        });

        async function cancelBooking(bookingId) {
            const confirmCancel = confirm("Êtes-vous sûr de vouloir annuler cette réservation ?");
            if (!confirmCancel) return;

            try {
                const res = await fetch(`${API_URL}/api/bookings/${bookingId}/cancel`, {
                    method: 'PATCH',
                    headers: {
                        Authorization: "Bearer " + token
                    }
                });

                const result = await res.json();

                if (!res.ok || !result.success) {
                    throw new Error(result.message || "Erreur lors de l'annulation");
                }

                alert("Réservation annulée avec succès !");
                window.location.href = "/client/suivis-service";
            } catch (err) {
                alert("Erreur lors de l'annulation : " + err.message);
            }
        }

    </script>
@endpush
