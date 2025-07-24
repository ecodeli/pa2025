@extends('layouts.app')

@section('title', 'Mon planning')

@section('content')
    <link rel="stylesheet" href="{{ secure_asset('css/service_provider/planning.css') }}">

    <div class="flex justify-center py-8 px-4">
        <div class="w-full max-w-[700px] mx-auto bg-white rounded-xl shadow-lg p-4">
            <h2 class="text-xl font-bold mb-4 text-center">🗓️ Mes disponibilités</h2>

            <div class="flex justify-center mb-4">
                <button id="addSlotBtn" class="bg-blue-600 text-white text-sm px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    + Ajouter une disponibilité
                </button>
            </div>

            <div id="calendar" class="rounded-xl overflow-hidden shadow border w-full max-w-full"></div>
        </div>
    </div>

    <dialog id="addSlotModal" class="rounded-xl p-6 w-full max-w-md">
        <form method="dialog" class="space-y-4">
            <h3 class="text-lg font-semibold mb-2">Ajouter une disponibilité</h3>

            <div class="flex flex-col">
                <label class="text-sm font-medium mb-1">📅 Date</label>
                <input type="date" id="slotDate" class="input-style" />
            </div>

            <div class="flex flex-col">
                <label class="text-sm font-medium mb-1">🕒 Heure de début</label>
                <input type="time" id="slotStart" class="input-style" />
            </div>

            <div class="flex flex-col">
                <label class="text-sm font-medium mb-1">🕓 Heure de fin</label>
                <input type="time" id="slotEnd" class="input-style" />
            </div>

            <div class="flex flex-col">
                <label class="text-sm font-medium mb-1">📌 Sélectionner votre annonce</label>
                <select id="slotType" class="input-style">
                    <option disabled selected>Chargement...</option>
                </select>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="submit" id="cancelModalBtn" class="text-sm px-4 py-2 rounded-md bg-gray-100 hover:bg-gray-200">Annuler</button>
                <button id="saveSlotBtn" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Enregistrer</button>
            </div>
        </form>
    </dialog>

    <dialog id="infoModal" class="rounded-xl p-6 w-full max-w-sm">
        <h3 class="text-lg font-semibold mb-2">Détails du créneau</h3>

        <div class="space-y-2 text-sm">
            <p><b>Annonce :</b> <span id="infoTitle"></span></p>
            <p><b>Date :</b> <span id="infoDate"></span></p>
            <p><b>Début :</b> <span id="infoStart"></span></p>
            <p><b>Fin :</b> <span id="infoEnd"></span></p>

            <p id="infoClientWrap" class="flex items-center gap-1">
                <b>Réservé par&nbsp;</b><span id="infoClient"></span>
            </p>
        </div>

        <div class="flex justify-end gap-2 pt-4">
            <button id="deleteSlotBtn" class="bg-red-600 text-white px-3 py-1 rounded-lg">Supprimer</button>
            <button id="closeInfoBtn" class="px-3 py-1 rounded-md bg-gray-200">Fermer</button>
        </div>
    </dialog>
@endsection

@push('scripts')
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.10/locales/fr.global.min.js"></script>

    <script>
        const API_URL = "/api(";
        const token = localStorage.getItem("token");

        document.addEventListener("DOMContentLoaded", () => {

            function createLocalDate(dateStr, timeStr) {
                if (!dateStr || !timeStr) return null;

                let localDateStr = dateStr;
                if (dateStr.includes('T')) {
                    const local = new Date(dateStr); // cette ligne lit la date en UTC
                    local.setMinutes(local.getMinutes() + local.getTimezoneOffset()); // convertit en local "brut"
                    localDateStr = local.toISOString().split('T')[0]; // extrait correctement le jour
                }


                const [year, month, day] = localDateStr.split('-').map(Number);
                const [hour, minute] = timeStr.split(':').map(Number);

                return new Date(year, month - 1, day, hour, minute);
            }



            const calendarEl = document.getElementById("calendar");
            const calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'fr',
                initialView: 'timeGridWeek',
                allDaySlot: false,
                height: 600,
                slotMinTime: '08:00:00',
                slotMaxTime: '24:00:00',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'timeGridDay,timeGridWeek,dayGridMonth'
                },
                buttonText: {
                    today: "Aujourd'hui",
                    month: 'Mois',
                    week: 'Semaine',
                    day: 'Jour'
                },

                events: async (fetchInfo, success, failure) => {
                    try {
                        const res = await fetch(`${API_URL}/api/availabilities`, {
                            headers: { Authorization: "Bearer " + token }
                        });
                        const data = await res.json();
                        console.log("Données brutes : ", data);

                        const events = data.map(slot => {
                            const isCancelled = slot.booking_id && slot.booking_status === 'annulée';

                            const avatar = slot.client_avatar
                                ? (slot.client_avatar.startsWith('http') ? slot.client_avatar : `${API_URL}${slot.client_avatar}`)
                                : null;

                            const start = createLocalDate(slot.date, slot.start_time);
                            const end = createLocalDate(slot.date, slot.end_time);

                            if (!start || !end || isNaN(start.getTime()) || isNaN(end.getTime())) return null;

                            return {
                                id: slot.id,
                                title: isCancelled
                                    ? 'Réservation annulée'
                                    : slot.booking_id
                                        ? `Réservé par ${slot.client_name}`
                                        : slot.annonce_title,
                                start,
                                end,
                                backgroundColor: isCancelled ? '#e74c3c' : (slot.booking_id ? '#22c55e' : '#3b82f6'),
                                textColor: '#fff',
                                extendedProps: {
                                    client_name: isCancelled ? null : slot.client_name,
                                    client_avatar: isCancelled ? null : avatar,
                                    isCancelled
                                }
                            };
                        }).filter(Boolean);



                        console.log("Événements envoyés à FullCalendar :", events);
                        success(events);
                    } catch (err) {
                        console.error("Erreur chargement créneaux :", err);
                        failure(err);
                    }
                },

                eventContent: ({ event }) => {
                    const avatar = event.extendedProps.client_avatar;
                    const isCancelled = event.extendedProps.isCancelled;
                    const wrap = document.createElement('div');
                    wrap.style.display = 'flex';
                    wrap.style.alignItems = 'center';

                    if (avatar) {
                        wrap.innerHTML = `<img src="${avatar}" style="width:20px;height:20px;border-radius:9999px;margin-right:6px;">`;
                    }

                    wrap.innerHTML += `<span>${event.title}</span>`;

                    if (isCancelled) {
                        wrap.innerHTML += ` <span style="color:white;background:#e74c3c;padding:2px 6px;border-radius:5px;margin-left:8px;font-size:11px;">ANNULÉ</span>`;
                    }

                    return { domNodes: [wrap] };
                },

                eventClick: ({ event }) => openInfoModal(event)
            });

            calendar.render();

            const infoModal = document.getElementById("infoModal");
            const infoTitle = document.getElementById("infoTitle");
            const infoDate = document.getElementById("infoDate");
            const infoStart = document.getElementById("infoStart");
            const infoEnd = document.getElementById("infoEnd");
            const infoClient = document.getElementById("infoClient");
            const infoClientWrap = document.getElementById("infoClientWrap");
            const deleteSlotBtn = document.getElementById("deleteSlotBtn");
            const closeInfoBtn = document.getElementById("closeInfoBtn");

            function openInfoModal(ev) {
                infoTitle.textContent = ev.title;
                infoDate.textContent = ev.start.toLocaleDateString('fr-FR');
                infoStart.textContent = ev.start.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
                infoEnd.textContent = ev.end
                    ? ev.end.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
                    : "(non défini)";

                if (ev.extendedProps.client_name) {
                    infoClient.textContent = ev.extendedProps.client_name;
                    infoClientWrap.style.display = 'flex';
                } else {
                    infoClientWrap.style.display = 'none';
                }

                deleteSlotBtn.dataset.eventId = ev.id;
                infoModal.showModal();
            }

            deleteSlotBtn.onclick = async () => {
                const eventId = deleteSlotBtn.dataset.eventId;
                if (!eventId) return;
                if (!confirm("Confirmer la suppression de ce créneau ?")) return;

                const res = await fetch(`${API_URL}/api/availabilities/${eventId}`, {
                    method: "DELETE",
                    headers: { Authorization: "Bearer " + token }
                });

                if (res.ok) {
                    calendar.getEventById(eventId).remove();
                    infoModal.close();
                } else {
                    alert("Erreur suppression");
                }
            };
            closeInfoBtn.onclick = () => infoModal.close();

            const modal = document.getElementById("addSlotModal");
            const openModalBtn = document.getElementById("addSlotBtn");
            const closeModalBtn = document.getElementById("cancelModalBtn");
            const saveBtn = document.getElementById("saveSlotBtn");
            const slotDate = document.getElementById("slotDate");
            const slotStart = document.getElementById("slotStart");
            const slotEnd = document.getElementById("slotEnd");
            const slotType = document.getElementById("slotType");

            const today = new Date().toISOString().split("T")[0];
            slotDate.min = today;

            openModalBtn.onclick = () => { modal.showModal(); loadUserAnnonces(); };
            closeModalBtn.onclick = () => modal.close();

            async function loadUserAnnonces() {
                try {
                    const res = await fetch(`${API_URL}/api/annonce/user`, {
                        headers: { Authorization: "Bearer " + token }
                    });
                    const annonces = await res.json();
                    slotType.innerHTML = "";

                    if (Array.isArray(annonces) && annonces.length) {
                        annonces.forEach(a => {
                            slotType.insertAdjacentHTML('beforeend',
                                `<option value="${a.listing_id}">${a.annonce_title}</option>`);
                        });
                    } else {
                        slotType.innerHTML = '<option disabled>Aucune annonce disponible</option>';
                    }
                } catch {
                    slotType.innerHTML = '<option disabled>Erreur</option>';
                }
            }

            saveBtn.onclick = async e => {
                e.preventDefault();

                const selDate = new Date(slotDate.value);
                const now = new Date(); now.setHours(0, 0, 0, 0);
                if (selDate < now) return alert("Impossible d’ajouter une disponibilité passée.");

                const payload = {
                    date: slotDate.value,
                    start_time: slotStart.value,
                    end_time: slotEnd.value,
                    listing_id: slotType.value
                };

                const res = await fetch(`${API_URL}/api/availabilities`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Authorization: "Bearer " + token
                    },
                    body: JSON.stringify(payload)
                });

                if (res.ok) {
                    modal.close();
                    calendar.refetchEvents();
                } else alert("Erreur lors de l’ajout");
            };
        });
    </script>
@endpush
