@extends('layouts.app')

@section('title', 'Tableau de bord client')

@section('content')

    <link rel="stylesheet" href="{{ secure_asset('css/client/dashboard.css') }}">

    <div class="dashboard-container">
        <h1>Bonjour <span id="username"></span> 👋</h1>
        <p>Voici un aperçu de vos activités sur EcoDeli.</p>

        <div id="dashboard-cards" class="cards-grid">
            <!-- Contenu ajouté dynamiquement en JS -->
        </div>
    </div>

    <div id="onboarding-overlay" class="overlay" style="display: none;">
        <div class="onboarding-box">
            <h2>Bienvenue sur EcoDeli 👋</h2>
            <p>Créez votre première annonce en quelques clics.</p>
            <p>🟢 Suivez les étapes : objets → départ → arrivée → validation</p>
            <button id="close-tutorial">J'ai compris</button>
        </div>
    </div>

@endsection

@push('scripts')
    <script type="module">
        import { requireAuth } from "/js/access-control.js";

        document.addEventListener("DOMContentLoaded", async () => {
            const user = await requireAuth("client");
            if (!user) return;

            document.getElementById("username").textContent = user.name;

            const token = localStorage.getItem("token");
            let annoncesCount = 0;
            let enAttente = 0;
            let realises = 0;
            let annulees = 0;
            let abonnements = 0;

            try {
                const res = await fetch("/api(/api/annonce/user", {
                    headers: { Authorization: "Bearer " + token }
                });
                const annonces = await res.json();
                if (Array.isArray(annonces)) {
                    annoncesCount = annonces.length;
                }
            } catch (err) {
                console.error("Erreur lors du chargement des annonces :", err);
            }

            try {
                const res = await fetch("/api(/api/bookings/client", {
                    headers: { Authorization: "Bearer " + token }
                });
                const bookings = await res.json();
                if (Array.isArray(bookings)) {
                    enAttente = bookings.filter(b => b.status === 'pending').length;
                    realises = bookings.filter(b => b.status === 'réalisée').length;
                    annulees = bookings.filter(b => b.status === 'annulée').length;
                }
            } catch (err) {
                console.error("Erreur lors du chargement des réservations :", err);
            }

            try {
                const res = await fetch("/api(/api/abonnement/current", {
                    headers: { Authorization: "Bearer " + token }
                });
                const abonnement = await res.json();
                abonnements = abonnement?.type ?? "Free";
            } catch (err) {
                console.error("Erreur lors du chargement de l’abonnement :", err);
            }


            const cardsContainer = document.getElementById("dashboard-cards");
            cardsContainer.innerHTML = `
                    <div class="card">Annonces créées : ${annoncesCount}</div>
                    <div class="card">Services réservés : ${enAttente}</div>
                    <div class="card">Services réalisés : ${realises}</div>
                    <div class="card">Services annulés : ${annulees}</div>
                    <div class="card">Abonnement : ${abonnements}</div>


            <a href="/client/annonce/nouvelle" class="btn-call">Créer une annonce</a>
        `;
        });

        document.addEventListener("DOMContentLoaded", () => {
            const alreadySeen = localStorage.getItem("tutorialSeen");
            const overlay = document.getElementById("onboarding-overlay");

            if (!alreadySeen && overlay) {
                overlay.style.display = "flex";

                document.getElementById("close-tutorial").addEventListener("click", () => {
                    localStorage.setItem("tutorialSeen", "true");
                    overlay.style.display = "none";
                });
            }
        });
    </script>
@endpush
