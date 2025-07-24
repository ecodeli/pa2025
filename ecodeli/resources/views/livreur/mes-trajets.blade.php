@extends('layouts.app')
@section('title', 'Mes trajets')

@section('content')
    <link href="{{ secure_asset('css/layout-mes-trajets.css') }}" rel="stylesheet">

    <div class="container py-4">
        <h2 class="mb-4">Mes trajets</h2>
        <div id="trajets-container">Chargement…</div>
        <div id="pagination-controls" class="mt-3 d-flex justify-content-center gap-2"></div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const API_URL = '/api';
                const token = localStorage.getItem('token');
                const container = document.getElementById('trajets-container');
                const pagination = document.getElementById('pagination-controls');
                let currentPage = 1;

                if (!token) {
                    container.innerHTML = '<p class="text-danger">Utilisateur non connecté.</p>';
                    return;
                }

                function formatDateFr(dateStr) {
                    if (!dateStr) return 'Date inconnue';
                    const date = new Date(dateStr);
                    return date.toLocaleDateString('fr-FR', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                }

                function adresseLink(texte) {
                    return `<a href="https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(texte)}" target="_blank">${texte}</a>`;
                }

                function loadTrips(page = 1) {
                    fetch(`${API_URL}/api/trajets/my-trips?page=${page}`, {
                        headers: { Authorization: 'Bearer ' + token }
                    })
                        .then(res => res.json())
                        .then(data => {
                            currentPage = data.currentPage;
                            renderTrips(data.trips);
                            renderPagination(data.currentPage, data.totalPages);
                        })
                        .catch(err => {
                            console.error(err);
                            container.innerHTML = '<p class="text-danger">Erreur lors du chargement des trajets.</p>';
                        });
                }

                function renderTrips(trips) {
                    container.innerHTML = '';
                    if (!trips || trips.length === 0) {
                        container.innerHTML = '<p>Aucun trajet trouvé.</p>';
                        return;
                    }

                    trips.forEach(t => {
                        const card = document.createElement('div');
                        card.className = 'trajet-card';

                        const departAdresse = t.departure_address ? adresseLink(t.departure_address) : 'Adresse indisponible';
                        const arriveeAdresse = t.delivery_address ? adresseLink(t.delivery_address) : 'Adresse indisponible';

                        card.innerHTML = `
                            <div class="trajet-details">
                                <h5>${t.annonce_title}</h5>
                                <p><strong>Description :</strong> ${t.description ?? ''}</p>
                                <p><strong>Prix :</strong> ${t.price} €</p>
                                <p><strong>Départ :</strong> ${t.departure_city} (${departAdresse})</p>
                                <p><strong>Arrivée :</strong> ${t.arrival_city} (${arriveeAdresse})</p>
                                <p><strong>Début :</strong> ${formatDateFr(t.departure_date)}</p>
                                <p><strong>Fin prévue :</strong> ${formatDateFr(t.arrival_date)}</p>
                                <p><strong>Statut :</strong> ${t.delivery_status}</p>

                                ${t.delivery_status !== 'delivered' ? `
                                    <div class="code-verification">
                                        <input type="text" id="code-${t.delivery_id}" placeholder="Code de livraison" class="input-code">
                                        <button class="btn-livrer" data-id="${t.delivery_id}">Livré</button>
                                    </div>
                                ` : `
                                    <p class="text-success">Facture générée</p>
                                    <a href="/livreur/annonce/${t.listing_id}/avis-client" class="btn-avis">Laisser un avis client</a>
                                `}
                            </div>
                        `;

                        container.appendChild(card);
                    });

                    // Bouton Livré
                    document.querySelectorAll('.btn-livrer').forEach(button => {
                        button.addEventListener('click', () => {
                            const deliveryId = button.dataset.id;
                            const codeInput = document.getElementById(`code-${deliveryId}`);
                            const deliveryCode = codeInput.value.trim();

                            if (!deliveryCode) {
                                alert("Veuillez entrer le code de livraison.");
                                return;
                            }

                            fetch(`${API_URL}/api/livraison/mark-delivered`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    Authorization: 'Bearer ' + token
                                },
                                body: JSON.stringify({
                                    delivery_id: deliveryId,
                                    verification_code: deliveryCode
                                })
                            })
                                .then(res => res.json())
                                .then(resp => {
                                    if (resp.success) {
                                        alert("Livraison validée. Facture générée.");
                                        loadTrips(currentPage);
                                    } else {
                                        alert(resp.error || "Erreur lors de la validation.");
                                    }
                                })
                                .catch(err => {
                                    console.error(err);
                                    alert("Erreur serveur.");
                                });
                        });
                    });
                }

                function renderPagination(current, total) {
                    pagination.innerHTML = '';
                    if (total <= 1) return;

                    const wrapper = document.createElement('div');
                    wrapper.className = 'pagination-wrapper';

                    const prevBtn = document.createElement('button');
                    prevBtn.textContent = '← Précédent';
                    prevBtn.className = 'pagination-button';
                    prevBtn.disabled = current === 1;
                    prevBtn.onclick = () => loadTrips(current - 1);
                    wrapper.appendChild(prevBtn);

                    const maxVisible = 5;
                    let start = Math.max(1, current - Math.floor(maxVisible / 2));
                    let end = Math.min(total, start + maxVisible - 1);
                    if (end - start < maxVisible - 1) {
                        start = Math.max(1, end - maxVisible + 1);
                    }

                    for (let i = start; i <= end; i++) {
                        const pageBtn = document.createElement('button');
                        pageBtn.textContent = i;
                        pageBtn.className = i === current ? 'pagination-current' : 'pagination-button';
                        pageBtn.disabled = i === current;
                        pageBtn.onclick = () => loadTrips(i);
                        wrapper.appendChild(pageBtn);
                    }

                    const nextBtn = document.createElement('button');
                    nextBtn.textContent = 'Suivant →';
                    nextBtn.className = 'pagination-button';
                    nextBtn.disabled = current === total;
                    nextBtn.onclick = () => loadTrips(current + 1);
                    wrapper.appendChild(nextBtn);

                    pagination.appendChild(wrapper);
                }

                loadTrips();
            });
        </script>
    @endpush
@endsection
