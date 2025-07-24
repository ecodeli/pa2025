@extends('layouts.admin')

@section('title', 'Dashboard Admin')

@section('content')
    <link href="{{ secure_asset('css/layout-dashboard.css') }}" rel="stylesheet">
    <div class="container py-4">
        <h2>Dashboard Administrateur</h2>

        <!-- Onglets -->
        <ul class="nav nav-tabs" id="dashboardTabs">
            <li class="nav-item">
                <a class="nav-link active" href="#" data-target="annoncesVille">Annonces par ville</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" data-target="usersRole">Users par rôle</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" data-target="prixMoyenVille">Prix moyen par ville</a>
            </li>
        </ul>

        <!-- Contenus -->
        <div class="tab-content mt-4">
            <div id="annoncesVille" class="tab-pane active">
                <canvas id="annoncesVilleChart"></canvas>
            </div>
            <div id="usersRole" class="tab-pane" style="display:none;">
                <canvas id="usersRoleChart"></canvas>
            </div>
            <div id="prixMoyenVille" class="tab-pane" style="display:none;">
                <canvas id="prixMoyenVilleChart"></canvas>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const API_URL = '/api';
            const token = localStorage.getItem('token');

            // Gestion des onglets
            document.querySelectorAll('#dashboardTabs .nav-link').forEach(tab => {
                tab.addEventListener('click', (e) => {
                    e.preventDefault();
                    // Switch active tab
                    document.querySelectorAll('#dashboardTabs .nav-link').forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');

                    // Afficher le bon contenu
                    const target = tab.getAttribute('data-target');
                    document.querySelectorAll('.tab-pane').forEach(pane => pane.style.display = 'none');
                    document.getElementById(target).style.display = 'block';
                });
            });

            // Fetch data API
            fetch(`${API_URL}/api/dashboard`, {
                headers: { Authorization: 'Bearer ' + token }
            })
                .then(res => res.json())
                .then(data => {
                    // 1. Annonces par ville
                    const annoncesVilleCtx = document.getElementById('annoncesVilleChart').getContext('2d');
                    new Chart(annoncesVilleCtx, {
                        type: 'bar',
                        data: {
                            labels: data.annoncesVille.map(a => a.departure_city),
                            datasets: [{
                                label: 'Nombre d\'annonces',
                                data: data.annoncesVille.map(a => a.nb_annonces),
                                backgroundColor: '#3B220F'
                            }]
                        },
                        options: { responsive: true, plugins: { legend: { display: false } } }
                    });

                    // 2. Users par rôle
                    const usersRoleCtx = document.getElementById('usersRoleChart').getContext('2d');
                    new Chart(usersRoleCtx, {
                        type: 'pie',
                        data: {
                            labels: data.usersRole.map(u => u.type),
                            datasets: [{
                                data: data.usersRole.map(u => u.nb_users),
                                backgroundColor: ['#3B220F', '#d4a373', '#f4a261', '#2a9d8f', '#e76f51']

                            }]
                        },
                        options: { responsive: true,
                        plugins:{
                            legend:{
                                labels:{
                                    font:{
                                        size:16
                                    }
                                }
                            }
                        }}
                    });

                    // 3. Prix moyen par ville
                    const prixMoyenVilleCtx = document.getElementById('prixMoyenVilleChart').getContext('2d');
                    new Chart(prixMoyenVilleCtx, {
                        type: 'bar',
                        data: {
                            labels: data.prixMoyenVille.map(p => p.departure_city),
                            datasets: [{
                                label: 'Prix moyen (€)',
                                data: data.prixMoyenVille.map(p => p.prix_moyen),
                                backgroundColor: 'green'
                            }]
                        },
                        options: { responsive: true, plugins: { legend: { display: false } } }
                    });
                })
                .catch(err => console.error(err));
        });
    </script>
@endpush
