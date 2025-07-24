<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'EcoDeli Admin')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ secure_asset('EDICONKIKI.ico') }}" type="image/x-icon">


    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">


    <link href="{{ secure_asset('css/layout-admin.css') }}" rel="stylesheet">
</head>
<body>


<div id="sidebar" class="sidebar d-flex flex-column">

    <div class="d-flex align-items-center justify-content-center py-4 border-bottom gap-2"
         style="cursor: pointer;" onclick="toggleSidebar()">
        <img src="{{ secure_asset('EDICONKIKI.ico') }}" alt="Logo" width="40" height="46">
        <h4 class="m-0">EcoDeli Admin</h4>
    </div>


    <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        <i class="bi bi-speedometer2 me-2"></i> <span>Dashboard</span></a>

    <a href="{{ route('admin.facturation') }}" class="sidebar-link {{ request()->routeIs('admin.facturation') ? 'active' : '' }}">
        <i class="bi bi-people me-2"></i> <span>Facturation</span>
    </a>

    <a href="{{ route('admin.avis') }}" class="sidebar-link {{ request()->routeIs('admin.avis') ? 'active' : '' }}">
        <i class="bi bi-chat-left-text me-2"></i> <span>Avis</span></a>

    <a href="{{ route('admin.annonce') }}" class="sidebar-link {{ request()->routeIs('admin.annonce') ? 'active' : '' }}">
        <i class="bi bi-people me-2"></i> <span>Annonce</span>
    </a>
    <a href="{{ route('admin.verification-identites') }}" class="sidebar-link" {{ request()->routeIs('admin.verification-identites') ? 'active' : '' }}>
        <i class="bi bi-person-check me-2"></i> <span>Verification d'identité</span></a>

    <a href="{{ route('admin.utilisateurs') }}" class="sidebar-link {{ request()->routeIs('admin.utilisateurs') ? 'active' : '' }}">
        <i class="bi bi-people me-2"></i> <span>Utilisateurs</span>
    </a>

    <a href="{{ route('admin.entrepot') }}" class="sidebar-link {{ request()->routeIs('admin.entrepot') ? 'active' : '' }}">
        <i class="fas fa-warehouse me-2"></i></i> <span>Entrepot</span>
    </a>



    <div class="mt-auto border-top pt-3">
        <a href="/">
            <i class="bi bi-house-door me-2"></i> <span> Accueil</span>
        </a>
        <a href="#" onclick="logout()" class="sidebar-link">
            <i class="bi bi-box-arrow-right me-2"></i> <span>Déconnexion</span>
        </a>
    </div>
</div>


<div class="main-content">
    <h1 class="mb-4">@yield('title')</h1>
    @yield('content')
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('collapsed');
    }
    function logout() {
        // Supprime ton token et user_id du localStorage
        localStorage.removeItem('token');
        localStorage.removeItem('user_id');

        // Redirige vers la page login ou home
        window.location.href = '/login';
    }
</script>

@stack('scripts')

</body>
</html>
