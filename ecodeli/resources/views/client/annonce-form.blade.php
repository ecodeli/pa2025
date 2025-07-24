@extends('layouts.app')

@section('title', 'Créer une annonce')

@section('content')
    <x-require-auth :role="['client', 'service_provider']" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="{{ secure_asset('css/client/annonce-form.css') }}">

    <div class="form-container">
        <h2>Créer une nouvelle annonce</h2>

        <div class="progress-steps">
            <div class="step active" data-step="1">Infos</div>
            <div class="step" data-step="2">Objets</div>
            <div class="step" data-step="3">Carte</div>
            <div class="step" data-step="4">Finaliser</div>
        </div>

        <div id="error-message" style="color: red; text-align:center;"></div>

        <form id="annonceForm" enctype="multipart/form-data">
            <div class="form-step" data-step="1">
                <h3>Informations générales</h3>

                <label>Titre de l'annonce :</label>
                <input type="text" id="annonce_title" name="annonce_title" required>

                <label>Type :</label>
                <select id="type" name="type" required>
                    <option value="">-- Sélectionner --</option>
                    <option value="colis">Colis</option>
                    <option value="service">Service</option>
                </select>

                <div id="livraison-directe-container" style="display:none; margin-top: 10px;">
                    <label>
                        <div class="checkbox-group">
                            <input type="checkbox" id="livraison_directe" name="livraison_directe">
                            <label for="livraison_directe">Livraison directe uniquement <span class="sub-label">(pas de livraison partielle)</span></label>
                        </div>

                    </label>
                </div>


                <div id="service-radius-container" style="display:none;">
                    <label>Rayon d'intervention (km) :</label>
                    <select id="service_radius" name="service_radius">
                        <option value="1">1 km</option>
                        <option value="5" selected>5 km</option>
                        <option value="10">10 km</option>
                        <option value="20">20 km</option>
                        <option value="50">50 km</option>
                    </select>
                </div>

                <label>Prix (€) :</label>
                <input type="number" id="price" name="price" min="0" step="0.01" required>

                <label>Informations complémentaires :</label>
                <textarea id="details" name="details"></textarea>
            </div>

            <div class="form-step" data-step="2" style="display:none;">
                <h3>Objets à transporter</h3>

                <label>Photos :</label>
                <input type="file" id="photos" accept="image/jpeg, image/png, image/gif" multiple>
                <small>Jusqu'à 7 photos, 7 Mo maximum chacune.</small>

                <div id="preview-photos"></div>

                <div id="objets-container">
                    <div class="objet">
                        <input type="number" name="quantity[]" placeholder="Quantité" required>
                        <input type="text" name="object_name[]" placeholder="Nom de l'objet" required>
                        <div class="checkbox-group">
                            <input type="checkbox" class="dimension-toggle" name="toggle-dimensions">
                            <label>Ajouter des dimensions pour cet objet</label>
                        </div>
                        <div class="dimensions" style="display:none;">
                            <select name="format[]">
                                <option value="">Taille</option>
                                <option value="Petit">Petit</option>
                                <option value="Moyen">Moyen</option>
                                <option value="Grand">Grand</option>
                            </select>
                            <select name="poids[]">
                                <option value="">Poids</option>
                                <option value="0-5kg">0-5 kg</option>
                                <option value="5-10kg">5-10 kg</option>
                                <option value="10kg+">10 kg+</option>
                            </select>
                        </div>
                        <button type="button" class="btn-supprimer-objet">Supprimer</button>
                    </div>
                </div>

                <button type="button" id="addObjet" class="btn-blue">Ajouter un objet</button>
            </div>

            <div class="form-step" data-step="3" style="display:none;">
                <h3>Localisation</h3>

                <div id="location-colis">
                    <label>Ville de départ :</label>
                    <input type="text" id="departure_city" name="departure_city">

                    <label>Ville d'arrivée :</label>
                    <input type="text" id="arrival_city" name="arrival_city">

                    <label>Adresse exacte de départ :</label>
                    <input type="text" id="departure_address" name="departure_address" placeholder="Ex: 10 rue de Paris, 75001" autocomplete="off">
                    <div id="departure-suggestions" class="suggestions-box"></div>

                    <label>Adresse exacte de livraison :</label>
                    <input type="text" id="delivery_address" name="delivery_address" placeholder="Ex: 20 avenue Victor Hugo, 75016" autocomplete="off">
                    <div id="delivery-suggestions" class="suggestions-box"></div>

                </div>

                <div id="location-service" style="display:none;">
                    <label>Lieu du service :</label>
                    <input type="text" id="service_city" name="service_city">
                </div>

                <div id="map" style="height: 400px; margin-bottom: 20px;"></div>

                <input type="hidden" id="departure_lat" name="departure_lat">
                <input type="hidden" id="departure_lng" name="departure_lng">
                <input type="hidden" id="arrival_lat" name="arrival_lat">
                <input type="hidden" id="arrival_lng" name="arrival_lng">
            </div>

            <div class="form-step" data-step="4" style="display:none;">
                <h3>Finalisation</h3>
                <p>Vérifiez vos informations puis publiez votre annonce !</p>
                <button type="submit" class="btn-blue">Publier l'annonce</button>
            </div>

            <div class="navigation-buttons">
                <button type="button" id="prevStep">Précédent</button>
                <button type="button" id="nextStep">Suivant</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script type="module">
        import { requireAuth } from "/js/access-control.js";

        document.addEventListener("DOMContentLoaded", async () => {
            const user = await requireAuth(["client", "service_provider"]);
            if (!user) return;

            function setupAddressAutocomplete(inputId, suggestionsId, linkedCityId) {
                const input = document.getElementById(inputId);
                const box = document.getElementById(suggestionsId);
                const cityInput = document.getElementById(linkedCityId);

                let timeout;
                input.addEventListener("input", () => {
                    clearTimeout(timeout);
                    const query = input.value.trim();
                    if (!query) return box.innerHTML = '';

                    timeout = setTimeout(async () => {
                        const res = await fetch(`https://api-adresse.data.gouv.fr/search/?q=${encodeURIComponent(query)}&limit=5`);
                        const data = await res.json();
                        box.innerHTML = '';
                        data.features.forEach(f => {
                            const div = document.createElement('div');
                            div.textContent = f.properties.label;
                            div.addEventListener('click', () => {
                                input.value = f.properties.label;
                                box.innerHTML = '';
                                // Auto-remplit la ville liée
                                if (f.properties.city && cityInput) {
                                    cityInput.value = f.properties.city;
                                }
                            });
                            box.appendChild(div);
                        });
                    }, 300);
                });
            }


            document.getElementById('livraison-directe-container').style.display = 'none';


            const typeSelect = document.getElementById("type");
            typeSelect.innerHTML = '<option value="">-- Sélectionner --</option>';

            if (user.type === "client") {
                typeSelect.innerHTML += '<option value="colis">Colis</option>';
                document.getElementById('location-colis').style.display = 'block';
                document.getElementById('livraison-directe-container').style.display = 'block';
                document.getElementById('location-service').style.display = 'none';
                document.getElementById('service-radius-container').style.display = 'none';
            } else if (user.type === "service_provider") {
                typeSelect.innerHTML += '<option value="service">Service</option>';
                document.getElementById('location-colis').style.display = 'none';
                document.getElementById('location-service').style.display = 'block';
                document.getElementById('service-radius-container').style.display = 'block';
            }


            document.getElementById('location-colis').style.display = 'none';
            document.getElementById('location-service').style.display = 'none';
            document.getElementById('service-radius-container').style.display = 'none';

            let currentStep = 1;
            const API_KEY = '5b3ce3597851110001cf6248f8a86418769d47b69aa4c4df724cc723';
            let selectedFiles = [];
            let serviceCircle = null;
            let serviceMarker = null;
            let departureMarker = null, arrivalMarker = null, routeLine = null;

            function showStep(step) {
                document.querySelectorAll('.form-step').forEach(div => div.style.display = 'none');
                document.querySelector(`.form-step[data-step="${step}"]`).style.display = 'block';
                document.querySelectorAll('.progress-steps .step').forEach((s, i) => {
                    if (i < step) s.classList.add('active');
                    else s.classList.remove('active');
                });
                if (step === 3 && typeof map !== 'undefined') {
                    setTimeout(() => map.invalidateSize(), 300);
                }
            }

            showStep(currentStep);

            ['nextStep', 'prevStep'].forEach(id => document.getElementById(id).addEventListener('click', () => {
                currentStep += id === 'nextStep' ? 1 : -1;
                currentStep = Math.min(Math.max(currentStep, 1), 4);
                showStep(currentStep);
            }));

            const map = L.map('map').setView([48.8566, 2.3522], 6);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: 'OpenStreetMap contributors' }).addTo(map);

            async function geocodeCity(city) {
                const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(city)}`);
                const data = await res.json();
                return data.length > 0 ? { lat: parseFloat(data[0].lat), lng: parseFloat(data[0].lon) } : null;
            }

            async function updateMap() {
                const type = document.getElementById('type').value;
                const radiusKm = parseInt(document.getElementById('service_radius')?.value || 5);

                if (serviceMarker) map.removeLayer(serviceMarker);
                if (serviceCircle) map.removeLayer(serviceCircle);
                if (departureMarker) map.removeLayer(departureMarker);
                if (arrivalMarker) map.removeLayer(arrivalMarker);
                if (routeLine) map.removeLayer(routeLine);

                if (type === 'service') {
                    const city = document.getElementById('service_city').value;
                    if (!city) return;

                    document.getElementById('departure_city').value = city;

                    const coords = await geocodeCity(city);
                    if (coords) {
                        serviceMarker = L.marker([coords.lat, coords.lng]).addTo(map).bindPopup('Service ici').openPopup();
                        serviceCircle = L.circle([coords.lat, coords.lng], {
                            color: 'blue',
                            fillColor: '#blue',
                            fillOpacity: 0.2,
                            radius: radiusKm * 1000
                        }).addTo(map);
                        map.setView([coords.lat, coords.lng], 12);
                        document.getElementById('departure_lat').value = coords.lat;
                        document.getElementById('departure_lng').value = coords.lng;
                    }
                } else {
                    const departureInput = document.getElementById('departure_address').value.trim() || document.getElementById('departure_city').value.trim();
                    const arrivalInput = document.getElementById('delivery_address').value.trim() || document.getElementById('arrival_city').value.trim();

                    if (!departureInput || !arrivalInput) return;

                    const depCoords = await geocodeCity(departureInput);
                    const arrCoords = await geocodeCity(arrivalInput);

                    if (depCoords && arrCoords) {
                        departureMarker = L.marker([depCoords.lat, depCoords.lng]).addTo(map).bindPopup('Départ').openPopup();
                        arrivalMarker = L.marker([arrCoords.lat, arrCoords.lng]).addTo(map).bindPopup('Arrivée').openPopup();
                        const url = `https://api.openrouteservice.org/v2/directions/driving-car/geojson`;
                        const res = await fetch(url, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Authorization': API_KEY },
                            body: JSON.stringify({ coordinates: [[depCoords.lng, depCoords.lat], [arrCoords.lng, arrCoords.lat]] })
                        });
                        const data = await res.json();
                        const coordsLine = data.features[0].geometry.coordinates.map(c => [c[1], c[0]]);
                        routeLine = L.polyline(coordsLine, { color: 'blue' }).addTo(map);
                        map.fitBounds(routeLine.getBounds(), { padding: [50, 50] });
                        document.getElementById('departure_lat').value = depCoords.lat;
                        document.getElementById('departure_lng').value = depCoords.lng;
                        document.getElementById('arrival_lat').value = arrCoords.lat;
                        document.getElementById('arrival_lng').value = arrCoords.lng;
                    }
                }
            }

            document.getElementById('type').addEventListener('change', (e) => {
                const type = e.target.value;
                document.getElementById('location-colis').style.display = (type === 'colis') ? 'block' : 'none';
                document.getElementById('location-service').style.display = (type === 'service') ? 'block' : 'none';
                document.getElementById('service-radius-container').style.display = (type === 'service') ? 'block' : 'none';
                updateMap();
            });

            document.getElementById('departure_city').addEventListener('blur', updateMap);
            document.getElementById('arrival_city').addEventListener('blur', updateMap);
            document.getElementById('service_city').addEventListener('blur', updateMap);
            document.getElementById('service_radius')?.addEventListener('change', updateMap);

            setupAddressAutocomplete('departure_address', 'departure-suggestions', 'departure_city');
            setupAddressAutocomplete('delivery_address', 'delivery-suggestions', 'arrival_city');

            document.getElementById("departure_address").addEventListener("blur", updateMap);
            document.getElementById("delivery_address").addEventListener("blur", updateMap);

            const photosInput = document.getElementById('photos');
            photosInput.addEventListener('change', (e) => {
                const preview = document.getElementById('preview-photos');
                Array.from(e.target.files).forEach(file => {
                    if (selectedFiles.length >= 7) { alert("Maximum 7 photos."); return; }
                    if (file.size > 7 * 1024 * 1024) { alert("Chaque photo doit faire moins de 7 Mo."); return; }
                    selectedFiles.push(file);
                    const reader = new FileReader();
                    reader.onload = (ev) => {
                        const img = document.createElement('img');
                        img.src = ev.target.result;
                        img.style.width = '100px';
                        img.style.margin = '5px';
                        preview.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                });
                e.target.value = '';
            });

            document.getElementById('addObjet').addEventListener('click', () => {
                const container = document.getElementById('objets-container');
                container.insertAdjacentHTML('beforeend', `<div class="objet"><input type="number" name="quantity[]" placeholder="Quantité" required>
<input type="text" name="object_name[]" placeholder="Nom de l'objet" required><label><input type="checkbox" class="dimension-toggle"> Dimensions</label>
<div class="dimensions" style="display:none;"><select name="format[]"><option value="">Taille</option><option value="Petit">Petit</option><option value="Moyen">Moyen</option><option value="Grand">Grand</option>
</select><select name="poids[]"><option value="">Poids</option><option value="0-5kg">0-5 kg</option><option value="5-10kg">5-10 kg</option><option value="10kg+">10 kg+</option></select></div><button type="button" class="btn-supprimer-objet">Supprimer</button></div>`);
            });

            document.addEventListener('click', (e) => {
                if (e.target.classList.contains('btn-supprimer-objet')) e.target.closest('.objet').remove();
                if (e.target.classList.contains('dimension-toggle')) {
                    const dim = e.target.closest('.objet').querySelector('.dimensions');
                    dim.style.display = e.target.checked ? 'block' : 'none';
                }
            });

            document.getElementById('annonceForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const token = localStorage.getItem("token");
                const formData = new FormData(e.target);
                const type = document.getElementById("type").value;
                if (type === "service") {
                    const radius = document.getElementById("service_radius").value;
                    formData.set("service_radius", radius);
                }

                if (document.getElementById("livraison_directe").checked) {
                    formData.set("livraison_directe", "1");
                } else {
                    formData.set("livraison_directe", "0");
                }

                formData.set("departure_address", document.getElementById("departure_address").value);
                formData.set("delivery_address", document.getElementById("delivery_address").value);


                selectedFiles.forEach(file => formData.append('photos[]', file));

                selectedFiles.forEach(file => formData.append('photos[]', file));
                try {
                    const res = await fetch('/api(/api/annonce', {
                        method: 'POST',
                        headers: { 'Authorization': 'Bearer ' + token },
                        body: formData
                    });
                    const result = await res.json();
                    if (!res.ok) {
                        console.error("Backend error:", result); // log complet console navigateur
                        document.getElementById('error-message').innerText = result.message || JSON.stringify(result);
                        return;
                    }

                    window.location.href = "/client/dashboard";
                } catch (err) {
                    console.error("Fetch failed:", err); // si API ne répond pas
                    document.getElementById('error-message').innerText = "Erreur de connexion à l'API.";
                }
            });
        });
    </script>
@endpush
