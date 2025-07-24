@extends('layouts.app')

@section('title', 'Détails de l’annonce')

@section('content')
    <div class="max-w-6xl mx-auto p-6">
        <div id="ad-details" class="grid animate-pulse text-center text-gray-500">Chargement...</div>
    </div>

    <!-- Modale Réservation -->
    <div id="reservationModal" class="modal" style="display: none;">
        <div class="modal-content" style="position: relative;">
            <span class="close-btn" id="closeModal">&times;</span>

            <!-- Titre dynamique selon type -->
            <h2 id="reservationTitle">🚚 Réserver ce transport</h2>
            <p class="text-sm text-gray-600" id="reservationSubtitle">Choisissez le mode de livraison souhaité :</p>

            <!-- Choix pour les colis -->
            <div id="deliveryOptions" class="modal-buttons" style="display: flex; gap: 10px; margin: 15px 0;">
                <button id="createFullDelivery" class="btn-blue" style="flex:1">🚛 Livraison complète</button>
                <button id="addToExistingRoute" class="btn-outline" style="flex:1">➕ Ajouter à un trajet</button>
            </div>

            <!-- Choix d’un trajet existant -->
            <div id="routeSelection" style="display:none; margin-top: 20px;">

                <div id="createRouteInline" class="mt-4 p-3 rounded bg-gray-100 border border-gray-300">
                    <h4 class="text-sm font-semibold mb-2">🚀 Vous n'avez pas de trajet ? Déclarez-en un :</h4>

                    <div class="grid grid-cols-1 gap-3">
                        <input type="text" id="newStartCity" placeholder="Ville de départ" class="input input-sm w-full">
                        <input type="text" id="newEndCity" placeholder="Ville d’arrivée" class="input input-sm w-full">
                        <input type="date" id="newDepartureDate" class="input input-sm w-full">
                        <button type="button" id="addRouteBtn" class="btn-blue w-full">➕ Ajouter le trajet</button>
                    </div>

                    <div id="routeCreateMsg" class="text-sm mt-2 text-red-600"></div>
                </div>q

                <hr style="margin-bottom: 15px;">
                <p class="text-sm text-gray-700 mb-2">🔁 Vous avez choisi d'ajouter cette annonce à un trajet existant :</p>

                <label for="routeSelect" class="block mb-1 font-medium">🛣️ Trajet à utiliser :</label>
                <select id="routeSelect" class="w-full mb-3"></select>

                <label for="customStartAddress" class="block mb-1 font-medium">📍 Adresse de départ personnalisée :</label>
                <input type="text" id="customStartAddress" class="w-full mb-1" placeholder="Ex : Gare de Lyon">
                <ul id="autocompleteResults" class="autocomplete-results"></ul>

                <label for="warehouseSelect" class="block mb-1 font-medium">🏢 Entrepôt relais (optionnel) :</label>
                <select id="warehouseSelect" class="w-full mb-4">
                    <option value="">Aucun entrepôt sélectionné</option>
                </select>

                <button id="confirmAddToRoute" class="btn-blue w-full">✅ Valider et réserver</button>
            </div>

            <!-- Choix pour les services à la personne -->
            <div id="serviceReservation" style="display: none; margin-top: 20px;">
                <hr style="margin-bottom: 15px;">

                <label for="availabilitySelect" class="block mb-1 font-medium">📅 Créneau souhaité :</label>
                <select id="availabilitySelect" class="w-full mb-4">
                    <option disabled selected>Chargement des disponibilités...</option>
                </select>

                <button id="confirmServiceBooking" class="btn-blue w-full">✅ Valider la réservation</button>
            </div>
        </div>
    </div>


    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
    <!-- Fancybox CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5/dist/fancybox/fancybox.css" />
    <link rel="stylesheet" href="{{ secure_asset('css/client/annonce-details.css') }}"/>

    <style>
        .autocomplete-results {
            list-style: none;
            padding: 0;
            margin-top: -5px;
            border: 1px solid #ccc;
            max-height: 200px;
            overflow-y: auto;
            background: white;
            position: absolute;
            z-index: 10;
            width: 100%;
        }

        .autocomplete-results li {
            padding: 10px;
            cursor: pointer;
        }

        .autocomplete-results li:hover {
            background-color: #f0f0f0;
        }
    </style>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5/dist/fancybox/fancybox.umd.js"></script>

    <script>
        const API_URL = "/api";
        const token = localStorage.getItem('token');
        const id = window.location.pathname.split("/").pop();
        let ad = null; // Global pour accès dans tous les événements
        let currentUser = null;

        document.addEventListener("DOMContentLoaded", async () => {
            const container = document.getElementById("ad-details");
            const reservationModal = document.getElementById("reservationModal");
            const closeModal = document.getElementById("closeModal");
            const routeSelect = document.getElementById("routeSelect");
            const routeSelection = document.getElementById("routeSelection");
            const warehouseSelect = document.getElementById("warehouseSelect");
            const customStartAddressInput = document.getElementById("customStartAddress");
            const autocompleteResults = document.getElementById("autocompleteResults");

            const reservationTitle = document.getElementById("reservationTitle");
            const reservationSubtitle = document.getElementById("reservationSubtitle");
            const deliveryOptions = document.getElementById("deliveryOptions");
            const serviceReservation = document.getElementById("serviceReservation");
            const availabilitySelect = document.getElementById("availabilitySelect");

            // Autocomplétion adresse
            customStartAddressInput.addEventListener("input", async () => {
                const query = customStartAddressInput.value.trim();
                if (query.length < 3) return autocompleteResults.innerHTML = "";
                try {
                    const res = await fetch(`https://api-adresse.data.gouv.fr/search/?q=${encodeURIComponent(query)}&limit=5`);
                    const data = await res.json();
                    if (data && data.features) {
                        autocompleteResults.innerHTML = data.features.map(f => `<li data-label="${f.properties.label}">${f.properties.label}</li>`).join("");
                    }
                } catch (err) {
                    console.error("Erreur autocomplétion :", err);
                }
            });

            autocompleteResults.addEventListener("click", (e) => {
                if (e.target.tagName === "LI") {
                    customStartAddressInput.value = e.target.dataset.label;
                    autocompleteResults.innerHTML = "";
                }
            });

            document.addEventListener("click", (e) => {
                if (!customStartAddressInput.contains(e.target) && !autocompleteResults.contains(e.target)) {
                    autocompleteResults.innerHTML = "";
                }
            });

            try {
                const res = await fetch(`${API_URL}/api/warehouses`, {
                    headers: { Authorization: "Bearer " + token }
                });
                const data = await res.json();
                if (Array.isArray(data)) {
                    warehouseSelect.innerHTML += data.map(w => `<option value="${w.warehouse_id}">${w.name} - ${w.city}</option>`).join('');
                }
            } catch (err) {
                console.error("Erreur chargement entrepôts :", err);
            }

            try {
                const userRes = await fetch(`${API_URL}/api/user`, {
                    headers: { Authorization: "Bearer " + token }
                });
                if (userRes.ok) {
                    currentUser = (await userRes.json()).user;
                }
            } catch (err) {
                console.error("Erreur user :", err);
            }

            try {
                const res = await fetch(`${API_URL}/api/annonce/details/${id}`, {
                    headers: { Authorization: "Bearer " + token }
                });

                ad = await res.json();
                let isLiked = false;

                try {
                    const likeCheck = await fetch(`${API_URL}/api/like/check/${id}`, {
                        headers: { Authorization: "Bearer " + token }
                    });
                    const likeStatus = await likeCheck.json();
                    isLiked = likeStatus.liked === true;
                } catch {}

                const photos = ad.photos || [];
                const photoSlides = photos.map((p, i) => `
                <div class="swiper-slide">
                    <a href="${API_URL}${p.photo_path}" data-fancybox="gallery" data-caption="Photo ${i + 1}">
                        <img src="${API_URL}${p.photo_path}" class="w-full h-64 object-cover rounded-lg cursor-zoom-in" />
                    </a>
                </div>
            `).join("");

                const expediteur = {
                    name: ad.creator_name?.trim() || "Nom prestataire",
                    note: ad.average_note ?? "N/A",
                    avatar: ad.avatar_url ? `${API_URL}${ad.avatar_url}` : `https://api.dicebear.com/7.x/initials/svg?seed=${ad.creator_name}`
                };

                const galleryContent = photos.length ? `
                <div class="swiper mySwiper">
                    <div class="swiper-wrapper">${photoSlides}</div>
                    <div class="swiper-pagination"></div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                </div>` : `<div class="text-gray-500 p-4">Aucune photo disponible</div>`;

                container.innerHTML = `
                <div>
                    <div class="photo-box">
                        <div class="ad-actions-top"><button id="like-btn">${isLiked ? "❤️" : "♡"}</button></div>
                        ${galleryContent}
                    </div>
                    <div class="annonce-info">
                        <h1>${ad.annonce_title}</h1>
                        <p class="meta-line">📍 ${ad.type === 'colis' ? `${ad.departure_city} → ${ad.arrival_city}` : ad.departure_city}</p>
                        <p class="price-line">${ad.price ? ad.price + ' €' : 'Non précisé'}</p>
                        <p>${ad.details || 'Aucune description'}</p>
                        ${(ad.objects && ad.objects.length)
                    ? `
    <div class="mt-4 text-left">
        <h3 class="text-md font-semibold mb-2">Objets à transporter :</h3>
        <ul class="list-disc pl-5 space-y-1 text-sm text-gray-700">
            ${ad.objects.map(obj => `
                <li>
                    ${obj.quantity} × ${obj.object_name}
                    ${obj.format ? ` — ${obj.format}` : ""}
                    ${obj.poids ? ` — ${obj.poids}` : ""}
                </li>
            `).join('')}
        </ul>
    </div>
  `
                    : `<p class="text-sm text-gray-400 mt-2">Aucun objet précisé pour cette annonce.</p>`
                }

                    </div>
                </div>
                <div class="seller-box">
                    <a href="/user_profiles/${ad.user_id}">
                        ${ad.creator_photo ? `<div class="user-initial"><img src="${API_URL}${ad.creator_photo}" /></div>` : `<div class="user-initial">${expediteur.name.charAt(0)}</div>`}
                    </a>
                    <div class="name">${expediteur.name}</div>
                    <div id="action-buttons" class="mt-4 text-center"></div>
                </div>`;

                if (photos.length) {
                    const swiperEl = document.querySelector(".mySwiper");
                    const observer = new MutationObserver(() => {
                        const allLoaded = [...swiperEl.querySelectorAll("img")].every(img => img.complete);
                        if (allLoaded) {
                            new Swiper(".mySwiper", {
                                pagination: { el: ".swiper-pagination", clickable: true },
                                navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" },
                                grabCursor: true,
                                loop: photos.length > 2,
                                rewind: photos.length <= 2
                            });
                            observer.disconnect();
                        }
                    });
                    observer.observe(swiperEl, { childList: true, subtree: true });

                    Fancybox.bind("[data-fancybox='gallery']", {
                        animated: true,
                        showClass: "fancybox-zoomIn",
                        hideClass: "fancybox-zoomOut"
                    });
                }

                document.getElementById("like-btn").addEventListener("click", async (e) => {
                    const btn = e.target;
                    const res = await fetch(`${API_URL}/api/like/ad`, {
                        method: "POST",
                        headers: { "Content-Type": "application/json", Authorization: "Bearer " + token },
                        body: JSON.stringify({ listing_id: id })
                    });
                    const r = await res.json();
                    btn.textContent = r.liked ? "❤️" : "♡";
                });

                if (currentUser) {
                    const isOwner = currentUser.user_id === ad.user_id;
                    const isColis = ad.type === 'colis';
                    const isService = ad.type === 'service';
                    const role = currentUser.type;
                    let actionHTML = "";

                    let alreadyReserved = false;
                    if (isColis && role === 'courier' && !isOwner) {
                        if (!currentUser.is_verified) {
                            actionHTML = "<p class='info-text text-red-600'>❌ Votre compte n’est pas encore vérifié. Vous ne pouvez pas réserver de trajets.</p>";
                        } else {
                            try {
                                const resCheck = await fetch(`${API_URL}/api/delivery/check_reserved/${id}`, {
                                    headers: { Authorization: "Bearer " + token }
                                });
                                const checkData = await resCheck.json();
                                alreadyReserved = checkData.reserved === true;
                            } catch {}
                        }
                    }

                    if (isOwner) {
                        actionHTML = "<p class='info-text'>Vous êtes l'auteur de cette annonce</p>";
                    } else if (isColis && role === 'courier') {
                        if (!currentUser.is_verified) {
                            actionHTML = `
            <div class="flex flex-col items-center gap-2">
                <p class='info-text text-red-600'>❌ Votre compte n’est pas encore vérifié. Vous ne pouvez pas réserver de trajets.</p>
                <a href="/verification-identite" class="btn-blue">Vérifier mon identité</a>
            </div>
        `;
                        } else {
                            actionHTML = alreadyReserved
                                ? "<p class='info-text'>✅ Vous avez déjà réservé ce transport</p>"
                                : '<button class="reserve-btn btn-blue mt-2">Réserver le transport</button>';
                        }
                    } else if (isColis && role === 'client') {
                        actionHTML = `<a href="/livreur/onboarding" class="btn-outline">🚚 Devenir livreur</a>`;
                    } else if (isService && role === 'client') {
                        actionHTML = '<button class="reserve-service-btn btn-blue mt-2">🛎️ Réserver ce service</button>';
                    } else if (isService && role === 'service_provider') {
                        actionHTML = "<p class='info-text'>Vous ne pouvez pas réserver vos propres services</p>";
                    }


                    document.getElementById("action-buttons").innerHTML = actionHTML;

                    document.querySelectorAll(".reserve-btn").forEach(btn => {
                        btn.addEventListener("click", () => {
                            reservationModal.style.display = "flex";
                            reservationTitle.textContent = "🚚 Réserver ce transport";
                            reservationSubtitle.textContent = "Choisissez le mode de livraison souhaité :";

                            const isLivraisonDirecte = ad.livraison_directe === 1;

                            // Montrer ou cacher les boutons selon le mode
                            if (isLivraisonDirecte) {
                                deliveryOptions.style.display = "flex";
                                document.getElementById("createFullDelivery").style.display = "block";
                                document.getElementById("addToExistingRoute").style.display = "none";
                            } else {
                                deliveryOptions.style.display = "flex";
                                document.getElementById("createFullDelivery").style.display = "block";
                                document.getElementById("addToExistingRoute").style.display = "block";
                            }

                            serviceReservation.style.display = "none";
                            routeSelection.style.display = "none";
                        });
                    });


                    document.querySelectorAll(".reserve-service-btn").forEach(btn => {
                        btn.addEventListener("click", async () => {
                            reservationModal.style.display = "flex";
                            reservationTitle.textContent = "🛎️ Réserver ce service";
                            reservationSubtitle.textContent = "Choisissez un créneau disponible avec le prestataire :";
                            deliveryOptions.style.display = "none";
                            routeSelection.style.display = "none";
                            serviceReservation.style.display = "block";

                            availabilitySelect.innerHTML = '<option disabled selected>Chargement...</option>';

                            try {
                                const res = await fetch(`${API_URL}/api/availabilities/listing/${id}`);
                                const slots = await res.json();

                                if (Array.isArray(slots) && slots.length > 0) {
                                    availabilitySelect.innerHTML = slots.map(slot => {
                                        const date = new Date(slot.date);
                                        const dateFormatted = date.toLocaleDateString('fr-FR', {
                                            weekday: 'long',
                                            day: '2-digit',
                                            month: 'long'
                                        });
                                        const heureDebut = slot.start_time?.slice(0, 5) || "";
                                        const heureFin   = slot.end_time?.slice(0, 5) || "";

                                        return `<option value="${slot.id}">
            ${dateFormatted} — ${heureDebut} → ${heureFin}
        </option>`;
                                    }).join('');
                                }
                                else {
                                    availabilitySelect.innerHTML = '<option disabled>Aucune disponibilité</option>';
                                }
                            } catch {
                                availabilitySelect.innerHTML = '<option disabled>Erreur de chargement</option>';
                            }
                        });
                    });

                }

                closeModal.addEventListener("click", () => reservationModal.style.display = "none");

                document.getElementById("createFullDelivery").addEventListener("click", async () => {
                    try {
                        const res = await fetch(`${API_URL}/api/delivery/create`, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                Authorization: "Bearer " + token
                            },
                            body: JSON.stringify({
                                listing_id: id,
                                departure_address: ad?.departure_address || ad?.departure_city || "",
                                delivery_address: ad?.delivery_address || ad?.arrival_city || "",
                                livraison_directe: ad?.livraison_directe ?? 0
                            })
                        });

                        const data = await res.json();
                        if (res.ok && data.delivery_id) {
                            window.location.href = `/delivery_success/${data.delivery_id}`;
                        } else {
                            alert("Erreur : " + (data.message || "Création impossible"));
                            console.log("Détail erreur :", data);
                        }
                    } catch (err) {
                        alert("Erreur lors de la création");
                        console.error("Erreur fetch :", err);
                    }
                });


                document.getElementById("addToExistingRoute").addEventListener("click", async () => {
                    routeSelection.style.display = "block";
                    routeSelect.innerHTML = "<option>Chargement...</option>";
                    try {
                        const res = await fetch(`${API_URL}/api/delivery/byUser`, {
                            headers: { Authorization: "Bearer " + token }
                        });
                        const data = await res.json();
                        routeSelect.innerHTML = data.routes && data.routes.length > 0
                            ? data.routes.map(r => `<option value="${r.route_id}">${r.start_city} → ${r.end_city} (${r.departure_date})</option>`).join('')
                            : '<option disabled>Aucun trajet disponible</option>';
                    } catch {
                        routeSelect.innerHTML = '<option disabled>Erreur</option>';
                    }
                });

                document.getElementById("confirmAddToRoute").addEventListener("click", async () => {
                    const routeId = routeSelect.value;
                    const customStartAddress = document.getElementById("customStartAddress").value;
                    const warehouseId = warehouseSelect.value;
                    try {
                        const res = await fetch(`${API_URL}/api/delivery/lines`, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                Authorization: "Bearer " + token
                            },
                            body: JSON.stringify({
                                route_id: routeId,
                                listing_id: id,
                                custom_start_address: customStartAddress,
                                warehouse_id: warehouseId || null
                            })
                        });
                        const data = await res.json();
                        if (res.ok) window.location.href = `/delivery_success/${routeId}`;
                        else alert("Erreur : " + (data.message || "Ajout impossible"));
                    } catch {
                        alert("Erreur serveur");
                    }
                });

                document.getElementById("confirmServiceBooking").addEventListener("click", async () => {
                    const availabilityId = availabilitySelect.value;
                    try {
                        const res = await fetch(`${API_URL}/api/service/book`, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                Authorization: "Bearer " + token
                            },
                            body: JSON.stringify({
                                listing_id: id,
                                availability_id: availabilityId
                            })
                        });
                        const data = await res.json();
                        if (res.ok) {
                            window.location.href = `/booking_success/${data.booking_id}`;
                        } else {
                            alert("Erreur : " + (data.message || "Réservation impossible"));
                        }
                    } catch {
                        alert("Erreur serveur");
                    }
                });

            } catch (err) {
                container.innerHTML = `<p class="text-red-500">Erreur lors du chargement de l’annonce.</p>`;
            }

            // Création d'un trajet simple sans colis
            const addRouteBtn = document.getElementById("addRouteBtn");
            addRouteBtn.addEventListener("click", async () => {
                const start = document.getElementById("newStartCity").value.trim();
                const end = document.getElementById("newEndCity").value.trim();
                const date = document.getElementById("newDepartureDate").value;

                const msgBox = document.getElementById("routeCreateMsg");
                msgBox.textContent = "";

                if (!start || !end || !date) {
                    msgBox.textContent = "Veuillez remplir tous les champs.";
                    return;
                }

                try {
                    const res = await fetch(`${API_URL}/api/delivery/declare-route`, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            Authorization: "Bearer " + token
                        },
                        body: JSON.stringify({
                            start_city: start,
                            end_city: end,
                            departure_date: date
                        })
                    });

                    const result = await res.json();

                    if (res.ok) {
                        msgBox.textContent = "Trajet créé avec succès.";
                        msgBox.className = "text-sm mt-2 text-green-600";
                        // recharge la liste des trajets pour l’utilisateur
                        const refresh = await fetch(`${API_URL}/api/delivery/byUser`, {
                            headers: { Authorization: "Bearer " + token }
                        });
                        const data = await refresh.json();
                        routeSelect.innerHTML = data.routes && data.routes.length > 0
                            ? data.routes.map(r => `<option value="${r.route_id}">${r.start_city} → ${r.end_city} (${r.departure_date})</option>`).join('')
                            : '<option disabled>Aucun trajet disponible</option>';
                    } else {
                        msgBox.textContent = result.error || "Erreur lors de la création.";
                    }
                } catch (err) {
                    msgBox.textContent = "Erreur serveur.";
                    console.error(err);
                }
            });

        });
    </script>
@endpush
