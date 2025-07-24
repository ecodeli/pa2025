@extends('layouts.app')

@section('title', 'Réservation confirmée')

@section('content')
    <div class="max-w-3xl mx-auto p-6">
        <div class="bg-white rounded-xl shadow-lg p-6 text-center">
            <h1 class="text-2xl font-bold text-green-600 mb-4">✅ Service réservé avec succès !</h1>

            <p class="text-gray-700 mb-6">
                Votre réservation a bien été prise en compte. Le prestataire vous contactera prochainement si nécessaire.
            </p>

            <div id="booking-details" class="text-left space-y-3 text-sm text-gray-800">
                <p><strong>Annonce :</strong> <span id="title">Chargement...</span></p>
                <p><strong>Prestataire :</strong> <span id="provider">—</span></p>
                <p><strong>Ville :</strong> <span id="city">—</span></p>
                <p><strong>Date :</strong> <span id="date">—</span></p>
                <p><strong>Heure :</strong> <span id="time">—</span></p>
                <p><strong>Statut :</strong> <span id="status">—</span></p>
            </div>

            <div class="mt-6">
                <a href="/" class="btn-blue">Retour à l'accueil</a>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const API_URL = "/api(";
        const token = localStorage.getItem('token');
        const bookingId = window.location.pathname.split("/").pop();

        document.addEventListener("DOMContentLoaded", async () => {
            try {
                const res = await fetch(`${API_URL}/api/bookings/${bookingId}`, {
                    headers: { Authorization: "Bearer " + token }
                });

                if (!res.ok) throw new Error("Erreur lors du chargement");

                const booking = await res.json();

                document.getElementById("title").textContent = booking.annonce_title || "—";
                document.getElementById("provider").textContent = booking.provider_name || "—";
                document.getElementById("city").textContent = booking.city || "—";
                document.getElementById("date").textContent = new Date(booking.date).toLocaleDateString("fr-FR") || "—";
                document.getElementById("time").textContent = `${booking.start_time} - ${booking.end_time}` || "—";
                document.getElementById("status").textContent = booking.status || "—";

            } catch (err) {
                console.error(err);
                document.getElementById("booking-details").innerHTML = `<p class="text-red-600">Impossible de charger les détails de la réservation.</p>`;
            }
        });
    </script>
@endpush
