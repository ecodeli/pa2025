@extends('layouts.app')

@section('title', 'Marketplace')

@section('content')
    <x-require-auth :role="['client', 'service_provider', 'courier']" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="{{ secure_asset('css/client/marketplace.css') }}">

    <div class="marketplace-container">
        <h2>Explorer les services et colis disponibles</h2>

        <div class="filters">
            <input type="text" id="cityFilter" placeholder="Ville">

            <div>
                <label><input type="radio" name="listingType" value="" checked> Tous</label>
                <label><input type="radio" name="listingType" value="colis"> Colis</label>
                <label><input type="radio" name="listingType" value="service"> Services</label>
            </div>

            <div>
                <label><input type="radio" name="providerType" value="" checked> Tous</label>
                <label><input type="radio" name="providerType" value="client"> Particuliers</label>
                <label><input type="radio" name="providerType" value="service_provider"> Professionnels</label>
            </div>

            <label>
                <input type="checkbox" id="likedOnlyFilter">
                ❤️ Favoris uniquement
            </label>

            <div class="search-buttons">
                <button id="btnNearby" class="btn-outline">📍 Autour de moi</button>
            </div>

            <button id="applyFilters">Appliquer les filtres</button>
        </div>

        <div class="marketplace-results">
            <div class="map-container">
                <div id="map"></div>
            </div>
            <div class="listings-container" id="listings"></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        let map;
        let likedAdIds = [];
        let markers = [];
        let activeSearchMode = null;
        const listingsContainer = document.getElementById("listings");
        const btnNearby = document.getElementById("btnNearby");

        async function fetchLikedAds() {
            const token = localStorage.getItem("token");
            try {
                const res = await fetch("/api/api/ad/like", {
                    headers: { Authorization: "Bearer " + token }
                });
                const data = await res.json();
                likedAdIds = data.liked || [];
            } catch (err) {
                console.error("Erreur chargement des likes", err);
            }
        }

        function getUserLocation(callback) {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    position => callback(position.coords.latitude, position.coords.longitude),
                    () => callback(48.8566, 2.3522)
                );
            } else {
                callback(48.8566, 2.3522);
            }
        }

        function calcDistance(lat1, lon1, lat2, lon2) {
            const R = 6371;
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat / 2) ** 2 +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLon / 2) ** 2;
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return R * c;
        }

        async function toggleLike(listingId, button) {
            const token = localStorage.getItem("token");
            try {
                const res = await fetch("/api/api/like/ad", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Authorization: "Bearer " + token
                    },
                    body: JSON.stringify({ listing_id: listingId })
                });
                const result = await res.json();
                if (result.liked) {
                    button.classList.add("liked");
                    button.textContent = "❤️";
                } else {
                    button.classList.remove("liked");
                    button.textContent = "♡";
                }
            } catch (err) {
                console.error("Erreur toggle like", err);
            }
        }

        async function loadListings() {
            await fetchLikedAds();
            getUserLocation(async (lat, lng) => {
                const city = document.getElementById("cityFilter").value;
                const likedOnly = document.getElementById("likedOnlyFilter").checked;
                const selectedType = document.querySelector('input[name="listingType"]:checked').value;
                const providerType = document.querySelector('input[name="providerType"]:checked').value;

                const url = new URL("/api/api/marketplace/services");
                url.searchParams.append("lat", lat);
                url.searchParams.append("lng", lng);
                if (city && !activeSearchMode) url.searchParams.append("city", city);
                if (!likedOnly && selectedType) url.searchParams.append("mainType", selectedType);
                if (providerType) url.searchParams.append("providerType", providerType);
                if (activeSearchMode) url.searchParams.append("searchMode", activeSearchMode);
                if (activeSearchMode === "nearby") url.searchParams.append("radius", 40);

                try {
                    const res = await fetch(url);
                    const data = await res.json();
                    let annonces = data || [];
                    if (likedOnly) {
                        annonces = annonces.filter(a => likedAdIds.includes(a.listing_id));
                    }
                    updateUI(annonces, lat, lng);
                } catch (err) {
                    console.error("Erreur chargement services :", err);
                    listingsContainer.innerHTML = `<p style="color:red;">Erreur connexion ou JSON invalide</p>`;
                }
            });
        }

        function updateUI(annonces, userLat, userLng) {
            listingsContainer.innerHTML = "";
            markers.forEach(m => m.remove());
            markers = [];

            annonces.forEach(a => {
                const dist = calcDistance(userLat, userLng, a.departure_lat, a.departure_lng);
                const card = document.createElement("a");
                card.href = `/client/annonce/details/${a.listing_id}`;
                card.classList.add("listing-card");

                const isLiked = likedAdIds.includes(a.listing_id);

                card.innerHTML = `
    <div class="img-wrapper">
        <img src="/api${a.photo_path}" alt="Photo">
        <button class="btn-fav ${isLiked ? 'liked' : ''}" data-id="${a.listing_id}" title="Ajouter aux favoris">
            ${isLiked ? '❤️' : '♡'}
        </button>
        ${a.livraison_directe === 1 ? `<span class="badge-direct">Livraison directe</span>` : ''}
    </div>
    <div class="info">
        <h3>${a.annonce_title}</h3>
        <p>(${a.type})</p>
        <p>📍 à ${dist.toFixed(1)} km</p>
    </div>
`;


                card.querySelector(".btn-fav").addEventListener("click", (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleLike(a.listing_id, e.target);
                });

                listingsContainer.appendChild(card);

                if (a.departure_lat && a.departure_lng) {
                    const marker = L.marker([a.departure_lat, a.departure_lng]).addTo(map)
                        .bindPopup(`<strong>${a.annonce_title}</strong><br>${a.category || 'Annonce'}<br>${dist.toFixed(1)} km`);
                    markers.push(marker);
                }
            });
        }

        document.addEventListener("DOMContentLoaded", () => {
            map = L.map('map').setView([48.8566, 2.3522], 6);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: 'OpenStreetMap'
            }).addTo(map);

            const resetNearbyMode = () => {
                activeSearchMode = null;
                btnNearby.classList.remove("active");
            };

            document.getElementById("applyFilters").addEventListener("click", () => {
                resetNearbyMode();
                loadListings();
            });

            document.getElementById("likedOnlyFilter").addEventListener("change", () => {
                resetNearbyMode();
                loadListings();
            });

            document.querySelectorAll('input[name="listingType"]').forEach(el => {
                el.addEventListener("change", () => {
                    resetNearbyMode();
                    loadListings();
                });
            });

            document.querySelectorAll('input[name="providerType"]').forEach(el => {
                el.addEventListener("change", () => {
                    resetNearbyMode();
                    loadListings();
                });
            });

            document.getElementById("cityFilter").addEventListener("input", () => {
                resetNearbyMode();
            });

            btnNearby.addEventListener("click", () => {
                document.getElementById("cityFilter").value = "";
                activeSearchMode = "nearby";
                btnNearby.classList.add("active");
                loadListings();
            });

            const btnRoute = document.getElementById("btnRoute");
            if (btnRoute) {
                btnRoute.addEventListener("click", () => {
                    activeSearchMode = "route";
                    resetNearbyMode();
                    loadListings();
                });
            }

            loadListings();
        });
    </script>
@endpush
