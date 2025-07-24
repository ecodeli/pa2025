<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Ecodeli')</title>
    <link rel="stylesheet" href="{{ secure_asset('css/layout-front.css') }}">
    <link rel="icon" href="{{ secure_asset('EDICONKIKI.ico') }}" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>

<body>
<div class="page-wrapper">
    @include('layouts.navbar')

    <main class="content-area">
        @yield('content')
    </main>
    <footer>
        <p>&copy; 2025 EcoDeli - Tous droits réservés.</p>
    </footer>
</div>
    @stack('scripts')
    <script>

document.addEventListener("DOMContentLoaded", async () => {
            const token = localStorage.getItem("token");
            const menu = document.getElementById("menu-links");
            const auth = document.getElementById("auth-section");

            if (token) {
                try {
                    const res = await fetch("/api(/api/user", {
                        headers: { Authorization: "Bearer " + token }
                    });
                    const data = await res.json();

                    if (res.ok) {
                        const type = data.user.type;
                        const name = data.user.name;

                        if (data.user.avatar_url) {
                            localStorage.setItem("avatar_url", `/api(${data.user.avatar_url}`);
                        }

                        let links = `<a href="/">{{__('Accueil')}}</a>`;

                        if (type === 'client') {
                            links += `<a href="/client/annonce">Mes annonces</a><a href="/client/suivi-colis">Suivi de colis</a><a href="/client/suivis-service">Suivi de service</a><a href="/marketplace">Marketplace</a><a href="/client/dashboard">Dashboard</a>`;
                        } else if (type === 'merchant') {
                            links += `<a href="/merchant/annonces">Annonces</a><a href="/merchant/facturation">Facturation</a>`;
                        } else if (type === 'courier') {
                            links += `<a href="/livreur/mes-trajets">Mes trajets</a><a href="/marketplace">Marketplace</a><a href="/livreur/dashboard">Mon Dashboard</a><a href="/livreur/suivis-colis">Suivi de Livraison</a>`;
                        } else if (type === 'service_provider') {
                            links += `<a href="/service_provider/interventions">Mes interventions</a><a href="/service_provider/planning">Planning</a><a href="/client/annonce">Mes annonces</a>`;
                        } else if (type === 'admin') {
                            links += `<a href="/admin/dashboard">Admin</a><a href="/admin/utilisateurs">Utilisateurs</a>`;
                        }

                        menu.innerHTML = links;

                        auth.innerHTML = `
    <a href="/user_profiles/${data.user.user_id}" class="avatar-link" title="Voir mon profil">
        <img
            src="${data.user.avatar_url ? `/api(${data.user.avatar_url}` : (localStorage.getItem('avatar_url') || `https://api.dicebear.com/7.x/initials/svg?seed=${encodeURIComponent(data.user.name)}`)}"
            alt="avatar"
            class="navbar-avatar"
            id="navbarAvatar"
        />
    </a>
    <a href="/facture" class="icon-link"><i class="bi bi-receipt fs-4"></i></a>
    <a href="/wallet" class="icon-link" title="Mon portefeuille"><i class="bi bi-wallet2 fs-4"></i></a>
    <a href="/messages" class="icon-link"><i class="bi bi-chat-dots fs-4"></i></a>
    <a href="/document" class="icon-link"><i class="bi bi-file-earmark"></i></a>
    <a href="/reserve-box" class="icon-link"><i class="fas fa-warehouse"></i></a>
    <button onclick="logout()" class="btn-login">Se déconnecter</button>
`;

                    } else {
                        showGuestLinks();
                    }
                } catch (err) {
                    showGuestLinks();
                }
            } else {
                showGuestLinks();
            }

            function showGuestLinks() {
                menu.innerHTML = `
            <a href="/">{{__('Accueil')}}</a>
            <a href="/nos-services">{{__('Nos Services')}}</a>
            <a href="/expéditeurs">{{__('Expéditeurs')}}</a>
            <a href="/nos-engagement">{{__('Nos engagements')}}</a>
        `;
                auth.innerHTML = `
            <a href="/login" class="btn-login">Se connecter</a>
            <a href="/register" class="btn-register">S’inscrire</a>
        `;
            }

            // Met à jour dynamiquement l'avatar si l'utilisateur a récemment uploadé une image
            window.logout = function () {
                localStorage.removeItem("token");
                localStorage.removeItem("avatar_url");
                location.reload();
            };
        });

        window.addEventListener("avatarUpdated", () => {
            const avatarImg = document.querySelector('.navbar-avatar');
            const newAvatar = localStorage.getItem("avatar_url");
            if (avatarImg && newAvatar) {
                avatarImg.src = newAvatar;
            }
        });

        $("#selectLocale").on('change',function(){
            var locale = $(this).val();
            window.location.href = "/changeLocale/"+locale;
        })

    </script>
</body>
</html>
