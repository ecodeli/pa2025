@extends('layouts.admin')

@section('title', 'Gestion des avis')

@section('content')
    <link href="{{ secure_asset('css/layout-admin-avis.css') }}" rel="stylesheet">
    <div class="container py-4">
        <h2 class="mb-4">Gestion des avis</h2>

        <div id="avis-list">Chargement…</div>
        <div id="pagination-controls" class="mt-3 d-flex justify-content-center gap-2"></div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const API_URL = '/api(';
                const token = localStorage.getItem('token');
                const avisList = document.getElementById('avis-list');
                const pagination = document.getElementById('pagination-controls');
                let currentPage = 1;

                if (!token) {
                    avisList.innerHTML = '<p class="text-danger">Vous devez être connecté.</p>';
                    return;
                }

                function loadReviews(page = 1) {
                    fetch(`${API_URL}/api/reviews?page=${page}`, {
                        headers: { Authorization: 'Bearer ' + token }
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (!data || data.error) {
                                avisList.innerHTML = '<p class="text-danger">Erreur lors du chargement des avis.</p>';
                                return;
                            }

                            renderReviews(data.reviews);
                            renderPagination(data.currentPage, data.totalPages);
                        })
                        .catch(err => {
                            console.error(err);
                            avisList.innerHTML = '<p class="text-danger">Erreur serveur.</p>';
                        });
                }

                function renderReviews(reviews) {
                    avisList.innerHTML = '';

                    if (!reviews || reviews.length === 0) {
                        avisList.innerHTML = '<p>Aucun avis trouvé.</p>';
                        return;
                    }

                    avisList.innerHTML = reviews.map(avis => {
                        let stars = '';
                        for (let i = 1; i <= 5; i++) {
                            stars += i <= avis.note ? '<span class="star">&#9733;</span>' : '<span class="star" style="color:lightgrey;">&#9733;</span>';
                        }

                        return `
                            <div class="avis-card">
                                <p><strong>Émetteur :</strong> ${avis.emetteur_name}</p>
                                <p><strong>Receveur :</strong> ${avis.receveur_name}</p>
                                <p><strong>Date :</strong> ${new Date(avis.created_at).toLocaleDateString()}</p>
                                <p><strong>Note :</strong> ${stars}</p>
                                <p><strong>Commentaire :</strong> ${avis.commentaire}</p>
                                <button class="btn-delete" onclick="deleteReview(${avis.review_id})">Supprimer</button>
                            </div>
                        `;
                    }).join('');
                }

                function renderPagination(current, total) {
                    pagination.innerHTML = '';
                    if (total <= 1) return;

                    const prevBtn = document.createElement('button');
                    prevBtn.textContent = '← Précédent';
                    prevBtn.className = 'pagination-button';
                    prevBtn.disabled = current === 1;
                    prevBtn.onclick = () => loadReviews(current - 1);
                    pagination.appendChild(prevBtn);

                    for (let i = 1; i <= total; i++) {
                        const pageBtn = document.createElement('button');
                        pageBtn.textContent = i;
                        pageBtn.className = i === current ? 'pagination-current' : 'pagination-button';
                        pageBtn.disabled = i === current;
                        pageBtn.onclick = () => loadReviews(i);
                        pagination.appendChild(pageBtn);
                    }

                    const nextBtn = document.createElement('button');
                    nextBtn.textContent = 'Suivant →';
                    nextBtn.className = 'pagination-button';
                    nextBtn.disabled = current === total;
                    nextBtn.onclick = () => loadReviews(current + 1);
                    pagination.appendChild(nextBtn);
                }

                window.deleteReview = function(reviewId) {
                    if (!confirm("Confirmer la suppression de cet avis ?")) return;

                    fetch(`${API_URL}/api/review/${reviewId}`, {
                        method: 'DELETE',
                        headers: { Authorization: 'Bearer ' + token }
                    })
                        .then(res => res.json())
                        .then(resp => {
                            if (resp.success) {
                                loadReviews(currentPage);
                            } else {
                                alert("Erreur lors de la suppression.");
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            alert("Erreur serveur.");
                        });
                };

                loadReviews();
            });
        </script>
    @endpush
@endsection
