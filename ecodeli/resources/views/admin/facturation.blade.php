@extends('layouts.admin')
@section('title', 'Facturation - Admin')

@section('content')
    <link href="{{ secure_asset('css/layout-facture.css') }}" rel="stylesheet">

    <div class="factures-wrapper">
        <h2>Gestion des factures</h2>
        <div id="factures-container">Chargement…</div>
        <button id="delete-selected" class="btn-delete-global">
            🗑Supprimer la sélection
        </button>
        <div id="pagination" class="pagination-buttons"></div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const API_URL = '/api';
                const token = localStorage.getItem('token');
                const container = document.getElementById('factures-container');
                const deleteBtn = document.getElementById('delete-selected');
                const paginationDiv = document.getElementById('pagination');
                const perPage = 5;
                let currentPage = 1;
                let allFactures = [];

                if (!token) {
                    container.innerHTML = '<p class="text-danger">Non connecté.</p>';
                    return;
                }

                fetch(`${API_URL}/api/adminFacture`, {
                    headers: {
                        Authorization: 'Bearer ' + token
                    }
                })
                    .then(res => res.json())
                    .then(data => {
                        if (!data.invoices || data.invoices.length === 0) {
                            container.innerHTML = '<p>Aucune facture trouvée.</p>';
                            return;
                        }

                        allFactures = data.invoices;
                        renderPage(currentPage);

                        deleteBtn.addEventListener('click', () => {
                            const selectedIds = Array.from(container.querySelectorAll('input[type="checkbox"]:checked'))
                                .map(cb => cb.value);

                            if (selectedIds.length === 0) return alert("Aucune facture sélectionnée.");
                            if (!confirm("Confirmer la suppression ?")) return;

                            fetch(`${API_URL}/api/adminFacture`, {
                                method: 'DELETE',
                                headers: {
                                    'Content-Type': 'application/json',
                                    Authorization: 'Bearer ' + token
                                },
                                body: JSON.stringify({ ids: selectedIds })
                            })
                                .then(res => res.json())
                                .then(() => location.reload())
                                .catch(() => alert("Erreur suppression"));
                        });
                    })
                    .catch(err => {
                        console.error(err);
                        container.innerHTML = '<p class="text-danger">Erreur serveur.</p>';
                    });

                function renderPage(page) {
                    container.innerHTML = '';
                    const start = (page - 1) * perPage;
                    const end = start + perPage;
                    const factures = allFactures.slice(start, end);

                    factures.forEach(facture => {
                        const card = document.createElement('div');
                        card.className = 'facture-card';

                        const date = new Date(facture.invoice_date).toLocaleDateString('fr-FR');

                        card.innerHTML = `
                            <div class="facture-numero">
                                <input type="checkbox" value="${facture.invoice_id}" />
                                #${facture.invoice_id}
                            </div>
                            <div class="facture-nom">${parseFloat(facture.amount).toFixed(2)} €</div>
                            <div class="facture-etat">${date}</div>
                            <div class="facture-users">
                                <strong>Émetteur :</strong> ${facture.emetteur_name ?? ''}<br>
                                <strong>Livreur :</strong> ${facture.livreur_name ?? ''}
                            </div>
                            <div class="facture-checkbox">
                                <a href="${API_URL}${facture.invoice_file}" target="_blank">📄</a>
                            </div>
                        `;

                        container.appendChild(card);
                    });

                    renderPagination();
                }

                function renderPagination() {
                    paginationDiv.innerHTML = '';
                    const totalPages = Math.ceil(allFactures.length / perPage);

                    const prev = document.createElement('button');
                    prev.textContent = 'Précédent';
                    prev.className = 'btn-paginate';
                    prev.disabled = currentPage === 1;
                    prev.onclick = () => {
                        currentPage--;
                        renderPage(currentPage);
                    };
                    paginationDiv.appendChild(prev);

                    const pageBtn = document.createElement('span');
                    pageBtn.className = 'page-active';
                    pageBtn.textContent = currentPage;
                    paginationDiv.appendChild(pageBtn);

                    const next = document.createElement('button');
                    next.textContent = 'Suivant';
                    next.className = 'btn-paginate';
                    next.disabled = currentPage === totalPages;
                    next.onclick = () => {
                        currentPage++;
                        renderPage(currentPage);
                    };
                    paginationDiv.appendChild(next);
                }
            });
        </script>
    @endpush
@endsection
