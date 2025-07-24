@extends('layouts.app')

@section('title', 'Modifier une annonce')

@section('content')
    <x-require-auth :role="['client', 'service_provider']" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="{{ secure_asset('css/client/annonce-form.css') }}">

    <div class="form-container">
        <h2>Modifier votre annonce</h2>

        <div class="progress-steps">
            <div class="step active" data-step="1">Infos</div>
            <div class="step" data-step="2">Objets</div>
            <div class="step" data-step="3">Carte</div>
            <div class="step" data-step="4">Finaliser</div>
        </div>

        <div id="error-message" style="color: red; text-align:center;"></div>

        <form id="edit-annonce-form" enctype="multipart/form-data">
            <input type="hidden" id="annonce-id" value="{{ $id }}">
            <!-- Étape 1 : Infos -->
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
                        <option value="5">5 km</option>
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

            <!-- Étape 2 : Objets / Détails -->
            <div class="form-step" data-step="2" style="display:none;">
                <h3>Objets à transporter</h3>
                <label>Photos :</label>
                <input type="file" id="photos" accept="image/jpeg, image/png, image/gif" multiple>
                <small>Jusqu'à 7 photos, 7 Mo maximum chacune.</small>
                <div id="preview-photos"></div>
                <div id="objets-container"></div>
                <button type="button" id="addObjet" class="btn-blue">Ajouter un objet</button>
            </div>

            <!-- Étape 3 : Carte -->
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

            <!-- Étape 4 : Finalisation -->
            <div class="form-step" data-step="4" style="display:none;">
                <h3>Finalisation</h3>
                <p>Vérifiez vos informations puis enregistrez votre annonce !</p>
                <button type="submit" class="btn-blue">Enregistrer</button>
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
            const API_URL = "/api";
            const id = document.getElementById("annonce-id").value;
            const token = localStorage.getItem("token");
            const user = await requireAuth(["client", "service_provider"]);
            if (!user) return;
            // Pré-remplissage de l'annonce
            const res = await fetch(`${API_URL}/api/annonce/${id}`, { headers: { Authorization: `Bearer ${token}` } });
            const annonce = await res.json();
            document.getElementById("annonce_title").value = annonce.annonce_title;
            document.getElementById("type").value = annonce.type;
            document.getElementById("price").value = annonce.price;
            document.getElementById("details").value = annonce.details || "";
            document.getElementById("departure_city").value = annonce.departure_city || "";
            document.getElementById("arrival_city").value = annonce.arrival_city || "";
            document.getElementById("service_radius").value = annonce.service_radius || "5";
            document.getElementById("departure_lat").value = annonce.departure_lat || "";
            document.getElementById("departure_lng").value = annonce.departure_lng || "";
            document.getElementById("arrival_lat").value = annonce.arrival_lat || "";
            document.getElementById("arrival_lng").value = annonce.arrival_lng || "";
            document.getElementById("service_city").value = annonce.service_city || annonce.departure_city || "";
            document.getElementById("departure_address").value = annonce.departure_address || "";
            document.getElementById("delivery_address").value = annonce.delivery_address || "";
            if (annonce.livraison_directe) document.getElementById("livraison_directe").checked = true;
            // Affichage conditionnel des champs
            const typeSelect = document.getElementById("type");
            function toggleFieldsByType(type) {
                document.getElementById('location-colis').style.display = (type === 'colis') ? 'block' : 'none';
                document.getElementById('location-service').style.display = (type === 'service') ? 'block' : 'none';
                document.getElementById('service-radius-container').style.display = (type === 'service') ? 'block' : 'none';
                document.getElementById('livraison-directe-container').style.display = (type === 'colis') ? 'block' : 'none';
            }
            toggleFieldsByType(annonce.type);
            typeSelect.addEventListener('change', e => toggleFieldsByType(e.target.value));
            // Objets
            const objetsContainer = document.getElementById("objets-container");
            function addObjetRow(obj = {}) {
                const div = document.createElement('div');
                div.classList.add('objet');
                div.innerHTML = `
                    <input type="number" name="quantity[]" placeholder="Quantité" value="${obj.quantity || ''}" required>
                    <input type="text" name="object_name[]" placeholder="Nom de l'objet" value="${obj.object_name || ''}" required>
                    <label><input type="checkbox" class="dimension-toggle" ${obj.format || obj.poids ? 'checked' : ''}> Dimensions</label>
                    <div class="dimensions" style="display:${obj.format || obj.poids ? 'block' : 'none'};">
                        <select name="format[]">
                            <option value="">Taille</option>
                            <option value="Petit" ${obj.format==='Petit'?'selected':''}>Petit</option>
                            <option value="Moyen" ${obj.format==='Moyen'?'selected':''}>Moyen</option>
                            <option value="Grand" ${obj.format==='Grand'?'selected':''}>Grand</option>
                        </select>
                        <select name="poids[]">
                            <option value="">Poids</option>
                            <option value="0-5kg" ${obj.poids==='0-5kg'?'selected':''}>0-5 kg</option>
                            <option value="5-10kg" ${obj.poids==='5-10kg'?'selected':''}>5-10 kg</option>
                            <option value="10kg+" ${obj.poids==='10kg+'?'selected':''}>10 kg+</option>
                        </select>
                    </div>
                    <button type="button" class="btn-supprimer-objet">Supprimer</button>
                `;
                div.querySelector('.btn-supprimer-objet').addEventListener('click', () => div.remove());
                div.querySelector('.dimension-toggle').addEventListener('change', (e) => {
                    div.querySelector('.dimensions').style.display = e.target.checked ? 'block' : 'none';
                });
                objetsContainer.appendChild(div);
            }
            document.getElementById('addObjet').addEventListener('click', () => addObjetRow());
            if (Array.isArray(annonce.objects)) annonce.objects.forEach(obj => addObjetRow(obj));
            // Photos existantes + suppression
            const previewPhotos = document.getElementById("preview-photos");
            let newPhotos = [];
            if (Array.isArray(annonce.photos)) {
                previewPhotos.innerHTML = annonce.photos.map(p => {
                    const filePath = p.photo_path.startsWith('/uploads/') ? p.photo_path : '/uploads/photos/' + p.photo_path;
                    const fullURL = API_URL + filePath;
                    const filename = filePath.split('/').pop();
                    return `
                        <div class="annonce-card" style="display:inline-block;position:relative;margin:5px;">
                            <img src="${fullURL}" style="width:100px;height:100px;object-fit:cover;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                            <button type="button" class="delete-photo" data-filename="${filename}" style="position:absolute;top:5px;right:5px;background:red;color:white;border:none;border-radius:50%;width:24px;height:24px;cursor:pointer;">×</button>
                        </div>
                    `;
                }).join('');
                document.querySelectorAll(".delete-photo").forEach(button => {
                    button.addEventListener("click", async () => {
                        const filename = button.dataset.filename;
                        if (!confirm("Supprimer cette photo ?")) return;
                        const response = await fetch(`${API_URL}/api/photo/${filename}`, {
                            method: 'DELETE',
                            headers: { Authorization: `Bearer ${token}` }
                        });
                        if (response.ok) button.parentElement.remove();
                        else alert("Erreur lors de la suppression");
                    });
                });
            }
            // Ajout photos dynamiques
            const photosInput = document.getElementById("photos");
            photosInput.addEventListener("change", (e) => {
                const files = Array.from(e.target.files);
                files.forEach(file => {
                    if (newPhotos.length >= 7) { alert("Maximum 7 photos."); return; }
                    if (file.size > 7 * 1024 * 1024) { alert("Chaque photo doit faire moins de 7 Mo."); return; }
                    newPhotos.push(file);
                    const reader = new FileReader();
                    reader.onload = (ev) => {
                        const wrapper = document.createElement("div");
                        wrapper.style.cssText = "display:inline-block;position:relative;margin:5px;";
                        const img = document.createElement("img");
                        img.src = ev.target.result;
                        img.style.cssText = "width:100px;height:100px;object-fit:cover;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);";
                        const del = document.createElement("button");
                        del.innerText = "×";
                        del.style.cssText = "position:absolute;top:5px;right:5px;background:red;color:white;border:none;border-radius:50%;width:24px;height:24px;cursor:pointer;";
                        del.onclick = () => {
                            newPhotos = newPhotos.filter(f => f !== file);
                            wrapper.remove();
                        };
                        wrapper.appendChild(img);
                        wrapper.appendChild(del);
                        previewPhotos.appendChild(wrapper);
                    };
                    reader.readAsDataURL(file);
                });
                photosInput.value = '';
            });
            // Navigation étapes
            let currentStep = 1;
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
            document.getElementById('nextStep').addEventListener('click', () => {
                currentStep = Math.min(currentStep + 1, 4);
                showStep(currentStep);
            });
            document.getElementById('prevStep').addEventListener('click', () => {
                currentStep = Math.max(currentStep - 1, 1);
                showStep(currentStep);
            });
            // Carte
            const map = L.map('map').setView([48.8566, 2.3522], 6);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: 'OpenStreetMap contributors' }).addTo(map);
            let serviceCircle = null, serviceMarker = null, departureMarker = null, arrivalMarker = null, routeLine = null;
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
                        // Optionnel : tracer une ligne entre les deux
                        routeLine = L.polyline([[depCoords.lat, depCoords.lng], [arrCoords.lat, arrCoords.lng]], { color: 'blue' }).addTo(map);
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
                toggleFieldsByType(type);
                updateMap();
            });
            document.getElementById('departure_city').addEventListener('blur', updateMap);
            document.getElementById('arrival_city').addEventListener('blur', updateMap);
            document.getElementById('service_city').addEventListener('blur', updateMap);
            document.getElementById('service_radius')?.addEventListener('change', updateMap);
            document.getElementById("departure_address").addEventListener("blur", updateMap);
            document.getElementById("delivery_address").addEventListener("blur", updateMap);
            // Autocomplete adresses
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
                                if (f.properties.city && cityInput) {
                                    cityInput.value = f.properties.city;
                                }
                            });
                            box.appendChild(div);
                        });
                    }, 300);
                });
            }
            setupAddressAutocomplete('departure_address', 'departure-suggestions', 'departure_city');
            setupAddressAutocomplete('delivery_address', 'delivery-suggestions', 'arrival_city');
            // Soumission du formulaire (PUT)
            document.getElementById('edit-annonce-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(e.target);
                if (document.getElementById("livraison_directe").checked) {
                    formData.set("livraison_directe", "1");
                } else {
                    formData.set("livraison_directe", "0");
                }
                formData.set("departure_address", document.getElementById("departure_address").value);
                formData.set("delivery_address", document.getElementById("delivery_address").value);
                newPhotos.forEach(file => formData.append('photos[]', file));
                try {
                    const response = await fetch(`${API_URL}/api/annonce/${id}`, {
                        method: "POST",
                        headers: { Authorization: `Bearer ${token}` },
                        body: formData
                    });
                    const result = await response.json();
                    if (!response.ok) {
                        document.getElementById('error-message').innerText = result.message || JSON.stringify(result);
                        return;
                    }
                    window.location.href = "/client/dashboard";
                } catch (err) {
                    document.getElementById('error-message').innerText = "Erreur de connexion à l'API.";
                }
            });
        });
    </script>
@endpush
