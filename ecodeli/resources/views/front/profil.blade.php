@extends('layouts.app')
@section('title', 'Profil utilisateur')

@section('content')
    <link rel="stylesheet" href="{{ secure_asset('css/client/profil.css') }}">

    <div class="profil-container">
        <div id="profile" class="profil-loading">Chargement du profil...</div>
    </div>
@endsection

@push('scripts')
    <script type="module">
        const API_URL = "/api";
        const urlParams = window.location.pathname.split("/");
        const userId = urlParams[urlParams.length - 1];

        async function loadProfile() {
            const container = document.getElementById("profile");

            try {
                const res = await fetch(`${API_URL}/api/users/${userId}`);
                const user = await res.json();

                // Récupération de la note moyenne
                const ratingRes = await fetch(`${API_URL}/api/users/${userId}/average-rating`, {
                    headers: {
                        Authorization: "Bearer " + localStorage.getItem("token")
                    }
                });
                const ratingData = await ratingRes.json();
                const averageRating = ratingData.average ?? 0;

                let starsHTML = '';
                for (let i = 1; i <= 5; i++) {
                    starsHTML += i <= Math.round(averageRating)
                        ? '<span style="color:gold;font-size:1.2rem;">&#9733;</span>'
                        : '<span style="color:lightgrey;font-size:1.2rem;">&#9733;</span>';
                }

                const avatarUrl = user.avatar_url
                    ? `${API_URL}${user.avatar_url}`
                    : `https://api.dicebear.com/7.x/initials/svg?seed=${user.name}`;

                container.classList.remove("profil-loading");

                container.innerHTML = `
                <div class="profil-header">
                    <div class="profil-avatar">
                        <label for="avatarInput" class="cursor-pointer">
                            <img id="avatarPreview" src="${avatarUrl}" alt="${user.name}" />
                        </label>
                        <form id="avatarForm" style="display:none;" enctype="multipart/form-data">
                            <input type="file" name="avatar" id="avatarInput" accept="image/*" />
                        </form>
                    </div>
                    <div class="profil-info">
                        <h1>Profil de ${user.name}
                            <span>${starsHTML}</span>
                        </h1>
                        <p>${user.email}</p>
                        <span class="profil-type">${user.type === 'service_provider' ? 'Professionnel' : 'Particulier'}</span>
                    </div>
                </div>

                <div class="profil-grid">
                    <a href="/edit-profile" class="profil-card">
                        <h3>👤 Informations personnelles</h3>
                        <p>Modifier nom, email, téléphone, mots de passe</p>
                    </a>
                    <a href="/wallet" class="profil-card">
                        <h3>💸 Paiements & versements</h3>
                        <p>Coordonnées bancaires, historiques</p>
                    </a>
                    <a href="/client/annonce" class="profil-card">
                        <h3>📦 Annonces publiées</h3>
                        <p>Voir les colis & services proposés</p>
                    </a>
                    <a href="/notification" class="profil-card">
                        <h3>🔔 Notifications & alertes</h3>
                        <p>Préférences de communication</p>
                    </a>
                    <a href="/messages" class="profil-card">
                        <h3>🛟 Support & assistance</h3>
                        <p>Contacter l'équipe EcoDeli</p>
                    </a>
                    <a href="/verification-identite" class="profil-card">
                        <h3>🪪 Verification d'identité</h3>
                        <p>Vérifier votre identité à l'aide de votre carte d'identité</p>
                    </a>
                    <a href="/mes-avis" class="profil-card">
                        <h3>💬 Commentaires</h3>
                        <p>Jeter un rapide coup d'œil à vos avis</p>
                    </a>
                    <a href="/abonnement" class="profil-card">
                        <h3>💳 Votre abonnement</h3>
                        <p>Gérer votre abonnement et ses avantages</p>
                    </a>
                    <a href="/mes-box" class="profil-card">
                        <h3>📦 Mes box de stockage</h3>
                        <p>Gestion de mes box de sotckage dans les entrepots français</p>
                    </a>
                </div>
            `;
            } catch (err) {
                console.error("Erreur chargement profil:", err);
                container.innerHTML = `<p class="text-red-500">Erreur de chargement.</p>`;
            }
        }

        document.addEventListener("DOMContentLoaded", () => {
            loadProfile();

            document.body.addEventListener("change", async function (e) {
                if (e.target && e.target.id === "avatarInput") {
                    const file = e.target.files[0];
                    if (!file) return;

                    const reader = new FileReader();
                    reader.onload = () => {
                        document.getElementById("avatarPreview").src = reader.result;
                    };
                    reader.readAsDataURL(file);

                    const formData = new FormData();
                    formData.append("avatar", file);
                    const token = localStorage.getItem("token");

                    const res = await fetch(`${API_URL}/api/user/avatar`, {
                        method: "POST",
                        headers: {
                            Authorization: "Bearer " + token
                        },
                        body: formData
                    });

                    const result = await res.json();
                    if (res.ok) {
                        localStorage.setItem("avatar_url", `${API_URL}${result.avatar_url}`);

                        const navbarAvatar = document.getElementById("navbarAvatar");
                        if (navbarAvatar) {
                            navbarAvatar.src = `${API_URL}${result.avatar_url}`;
                        }

                        // Notifie la navbar du changement
                        window.dispatchEvent(new Event("avatarUpdated"));
                    }
                }
            });
        });
    </script>
@endpush
