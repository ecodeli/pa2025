@extends('layouts.app')

@section('title', 'Mes Réservations de Services')

@section('content')
    <link rel="stylesheet" href="{{ secure_asset('css/client/suivis-service.css') }}">

    <div class="reservations-container">
        <h1>📋 Mes Réservations</h1>
        <p>Retrouvez ici l’ensemble des services que vous avez réservés.</p>

        <div id="loader" class="loader">Chargement des réservations...</div>

        <div id="reservations-list" class="reservations-grid" style="display: none;">
            <!-- Cartes générées dynamiquement -->
        </div>

        <div id="no-reservations" class="empty-state" style="display: none;">
            <p>Vous n’avez encore réservé aucun service.</p>
            <a href="/marketplace" class="btn-primary">Explorer les services</a>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="module">
        import { requireAuth } from "/js/access-control.js";

        document.addEventListener("DOMContentLoaded", async () => {
            const user = await requireAuth("client");
            if (!user) return;

            const token = localStorage.getItem("token");
            const list = document.getElementById("reservations-list");
            const emptyState = document.getElementById("no-reservations");
            const loader = document.getElementById("loader");

            try {
                const res = await fetch("/api(/api/bookings/client", {
                    headers: { Authorization: "Bearer " + token }
                });

                const reservations = await res.json();
                loader.style.display = "none";

                if (!Array.isArray(reservations) || reservations.filter(r => r.status !== 'annulée').length === 0) {
                    emptyState.style.display = "block";
                    return;
                }


                list.style.display = "grid";
                list.innerHTML = reservations
                    .filter(r => r.status !== 'annulée') // <-- filtre ici
                    .map(r => `
                <div class="reservation-card ${r.status}">
                    <div class="card-header">
                        <h3>${r.annonce_title || 'Service réservé'}</h3>
                        <span class="status">${r.status}</span>
                    </div>
                    <p><strong>Date de réservation :</strong> ${new Date(r.booked_at).toLocaleDateString()}</p>
                    <p><strong>ID Réservation :</strong> #${r.booking_id}</p>
                    <a href="/client/reservations/${r.booking_id}" class="btn-secondary">Voir le détail</a>

                </div>
            `).join('');
            } catch (err) {
                loader.textContent = "❌ Impossible de charger les réservations.";
                console.error("Erreur chargement réservations:", err);
            }
        });

    </script>
@endpush
