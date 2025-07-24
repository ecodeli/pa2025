@extends('layouts.app')

@section('title', 'Mes avis reçus')

@section('content')
    <link href="{{ secure_asset('css/client/layout-avis.css') }}" rel="stylesheet">
    <link href="{{ secure_asset('css/layout-mes-avis.css') }}" rel="stylesheet">

    <div class="container py-4">
        <h2 class="mb-4">Mes avis reçus</h2>

        <div id="avis-list">Chargement…</div>

        <nav>
            <ul class="pagination justify-content-center mt-4" id="pagination"></ul>
        </nav>
    </div>

    <style>
        .star {
            color: gold;
            font-size: 1.5rem;
        }
        .avis-card {
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .pagination li {
            cursor: pointer;
        }
        .pagination .disabled span {
            cursor: not-allowed;
        }
    </style>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const API_URL = '/api';
                const token = localStorage.getItem('token');
                const avisList = document.getElementById('avis-list');
                const pagination = document.getElementById('pagination');
                let currentPage = 1;
                const avisPerPage = 5;
                let allAvis = [];

                if (!token) {
                    avisList.innerHTML = '<p class="text-danger">Vous devez être connecté.</p>';
                    return;
                }

                function renderPagination(totalPages) {
                    pagination.innerHTML = '';

                    // Bouton Précédent
                    const prevLi = document.createElement('li');
                    prevLi.className = 'page-item ' + (currentPage === 1 ? 'disabled' : '');
                    prevLi.innerHTML = `<span class="page-link">Précédent</span>`;
                    prevLi.addEventListener('click', () => {
                        if (currentPage > 1) {
                            currentPage--;
                            displayAvis();
                        }
                    });
                    pagination.appendChild(prevLi);

                    // Numéros de page
                    for (let i = 1; i <= totalPages; i++) {
                        const li = document.createElement('li');
                        li.className = 'page-item' + (i === currentPage ? ' active' : '');
                        li.innerHTML = `<span class="page-link">${i}</span>`;
                        li.addEventListener('click', () => {
                            currentPage = i;
                            displayAvis();
                        });
                        pagination.appendChild(li);
                    }

                    // Bouton Suivant
                    const nextLi = document.createElement('li');
                    nextLi.className = 'page-item ' + (currentPage === totalPages ? 'disabled' : '');
                    nextLi.innerHTML = `<span class="page-link">Suivant</span>`;
                    nextLi.addEventListener('click', () => {
                        if (currentPage < totalPages) {
                            currentPage++;
                            displayAvis();
                        }
                    });
                    pagination.appendChild(nextLi);
                }

                function displayAvis() {
                    const start = (currentPage - 1) * avisPerPage;
                    const end = start + avisPerPage;
                    const avisToShow = allAvis.slice(start, end);

                    if (avisToShow.length === 0) {
                        avisList.innerHTML = '<p>Aucun avis pour le moment.</p>';
                        pagination.innerHTML = '';
                        return;
                    }

                    avisList.innerHTML = avisToShow.map(avis => {
                        let stars = '';
                        for (let i = 1; i <= 5; i++) {
                            stars += i <= avis.note ? '<span class="star">&#9733;</span>' : '<span class="star" style="color:lightgrey;">&#9733;</span>';
                        }

                        return `
        <div class="avis-card">
            <p><strong>Annonce :</strong> ${avis.annonce_title ?? 'N/A'}</p>
            <p><strong>De :</strong> ${avis.auteur_name ?? 'Inconnu'}</p>
            <p><strong>Note :</strong> ${stars}</p>
            <p><strong>Commentaire :</strong> ${avis.commentaire}</p>
            <p><small>Posté le ${new Date(avis.created_at).toLocaleDateString()}</small></p>
        </div>
    `;
                    }).join('');


                    const totalPages = Math.ceil(allAvis.length / avisPerPage);
                    renderPagination(totalPages);
                }

                fetch(`${API_URL}/api/user/reviews-received`, {
                    headers: { Authorization: 'Bearer ' + token }
                })
                    .then(res => res.json())
                    .then(data => {
                        if (!data || data.error) {
                            avisList.innerHTML = '<p class="text-danger">Erreur lors du chargement des avis.</p>';
                            return;
                        }

                        allAvis = data;
                        displayAvis();
                    })
                    .catch(err => {
                        console.error(err);
                        avisList.innerHTML = '<p class="text-danger">Erreur serveur.</p>';
                    });
            });
        </script>
    @endpush
@endsection
