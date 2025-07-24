@extends('layouts.app')

@section('title', 'Mes annonces')

@section('content')
    <x-require-auth :role="['client', 'service_provider']" />
    <link rel="stylesheet" href="{{ secure_asset('css/client/annonces.css') }}">

    <div class="annonces-container">
        <h2>Mes annonces</h2>
        <div class="actions">
            <a href="/client/annonce/nouvelle" class="btn-blue">➕ Créer une annonce</a>
        </div>
        <div id="annonces-list" class="list-grid"></div>
    </div>
@endsection

@push('scripts')
    <script type="module">
        import { requireAuth } from "/js/access-control.js";
        const API_URL = "/api";

        document.addEventListener("DOMContentLoaded", async () => {
            const user = await requireAuth(["client", "service_provider"]);
            if (!user) return;

            const token = localStorage.getItem("token");
            const container = document.getElementById("annonces-list");

            try {
                const res = await fetch(`${API_URL}/api/annonce/user`, {
                    headers: { Authorization: "Bearer " + token }
                });
                const annonces = await res.json();

                if (!Array.isArray(annonces) || annonces.length === 0) {
                    container.innerHTML = "<p>Aucune annonce trouvée.</p>";
                    return;
                }

                container.innerHTML = annonces.map(a => `
                <div class="annonce-card">
                    <img src="${API_URL}${a.photo_path}" alt="Photo" class="annonce-photo">
                    <h3>${a.annonce_title}</h3>
                    <p>Status : <strong>${a.status}</strong></p>
                    <p>Code de vérification : <strong>${a.verification_code}</strong></p>
                    <div class="annonce-actions">
                        <a href="/client/annonce/modifier/${a.listing_id}" class="btn-edit">Modifier</a>
                        <button onclick="deleteAnnonce(${a.listing_id})" class="btn-red">Supprimer</button>
                        ${a.status === 'delivered'
                    ? `<a href="/client/annonce/${a.listing_id}/avis" class="btn-avis">Laisser un avis</a>`
                    : ''}
                    </div>
                </div>
            `).join('');
            } catch (err) {
                console.error("Erreur chargement annonces :", err);
                container.innerHTML = "<p>Erreur lors du chargement.</p>";
            }
        });

        window.deleteAnnonce = async id => {
            if (!confirm("Supprimer cette annonce ?")) return;
            const token = localStorage.getItem("token");
            try {
                const res = await fetch(`${API_URL}/api/annonce/${id}`, {
                    method: "DELETE",
                    headers: { Authorization: "Bearer " + token }
                });
                if (res.ok) window.location.reload();
                else alert("Erreur suppression.");
            } catch {
                alert("Erreur réseau.");
            }
        };
    </script>
@endpush
