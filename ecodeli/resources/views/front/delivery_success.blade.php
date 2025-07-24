@extends('layouts.app')

@section('title', 'Livraison confirmée')

@section('content')
    <div class="max-w-2xl mx-auto p-6">
        <div class="bg-white shadow-lg rounded-lg p-6 text-center animate-fade-in">
            <h1 class="text-2xl font-bold text-green-600 mb-4">Livraison confirmée !</h1>
            <p class="text-gray-700 mb-6" id="delivery-type-text">
                Chargement...
            </p>

            <div class="text-left text-sm text-gray-800 bg-gray-100 rounded p-4 mb-6" id="delivery-summary">
                <p><strong>Départ :</strong> <span id="start-city"></span></p>
                <p><strong>Arrivée :</strong> <span id="end-city"></span></p>
                <p><strong>Adresse personnalisée :</strong> <span id="custom-address">—</span></p>
                <p><strong>Entrepôt relais :</strong> <span id="warehouse">—</span></p>
            </div>

            <a href="/client/dashboard" class="inline-block bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition">
                Retour au tableau de bord
            </a>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const API_URL = "/api";
        const token = localStorage.getItem('token');
        const routeId = window.location.pathname.split("/").pop();

        document.addEventListener("DOMContentLoaded", async () => {
            try {
                const res = await fetch(`${API_URL}/api/delivery/success/${routeId}`, {
                    headers: { Authorization: "Bearer " + token }
                });
                const data = await res.json();

                document.getElementById("delivery-type-text").textContent =
                    data.type === "full"
                        ? "Vous avez créé une livraison complète depuis votre trajet."
                        : "Vous avez ajouté un colis à un trajet existant.";

                document.getElementById("start-city").textContent = data.route.start_city;
                document.getElementById("end-city").textContent = data.route.end_city;
                document.getElementById("custom-address").textContent = data.custom_start_address || "—";
                document.getElementById("warehouse").textContent = data.warehouse ? data.warehouse.name + " (" + data.warehouse.city + ")" : "—";

            } catch (err) {
                console.error("Erreur chargement livraison :", err);
                document.getElementById("delivery-type-text").textContent = "Erreur de chargement.";
            }
        });
    </script>
@endpush
