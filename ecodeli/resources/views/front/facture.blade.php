@extends('layouts.app')
@section('title', 'Mes factures')

@section('content')
    <link href="{{ secure_asset('css/layout-facture.css') }}" rel="stylesheet">

    <div class="factures-wrapper">
        <h2>Mes factures</h2>
        <div id="factures-container">Chargement…</div>
        <div id="pagination" class="pagination-container"></div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const API_URL = '/api';
                const token = localStorage.getItem('token');
                const container = document.getElementById('factures-container');
                const paginationContainer = document.getElementById('pagination');

                if (!token) {
                    container.innerHTML = '<p class="text-danger">Utilisateur non connecté.</p>';
                    return;
                }

                let currentPage = 1;

                function loadPage(page) {
                    fetch(`${API_URL}/api/factures/my-invoices?page=${page}`, {
                        headers: { Authorization: 'Bearer ' + token }
                    })
                        .then(res => res.json())
                        .then(data => {
                            container.innerHTML = '';
                            paginationContainer.innerHTML = '';
                            if (!data.invoices || data.invoices.length === 0) {
                                container.innerHTML = '<p>Aucune facture trouvée.</p>';
                                return;
                            }

                            data.invoices.forEach(facture => {
                                const card = document.createElement('div');
                                card.className = 'facture-card';

                                const date = new Date(facture.invoice_date).toLocaleDateString('fr-FR');

                                card.innerHTML = `
                                <div class="facture-numero">#${facture.invoice_id}</div>
                                <div class="facture-nom">${facture.amount} €</div>
                                <div class="facture-etat">${date}</div>
                                <div class="facture-checkbox">
                                    <a href="${API_URL}${facture.invoice_file}" target="_blank">📄</a>
                                </div>
                            `;

                                container.appendChild(card);
                            });

                            currentPage = data.currentPage;
                            renderPagination(currentPage, data.totalPages);
                        })
                        .catch(err => {
                            console.error(err);
                            container.innerHTML = '<p class="text-danger">Erreur lors du chargement.</p>';
                        });
                }

                function renderPagination(current, total) {
                    const prev = document.createElement('button');
                    prev.textContent = 'Précédent';
                    prev.className = 'pagination-btn';
                    prev.disabled = current === 1;
                    prev.onclick = () => loadPage(current - 1);
                    paginationContainer.appendChild(prev);

                    const page = document.createElement('span');
                    page.className = 'pagination-page';
                    page.textContent = current;
                    paginationContainer.appendChild(page);

                    const next = document.createElement('button');
                    next.textContent = 'Suivant';
                    next.className = 'pagination-btn';
                    next.disabled = current === total;
                    next.onclick = () => loadPage(current + 1);
                    paginationContainer.appendChild(next);
                }

                loadPage(currentPage);
            });
        </script>
    @endpush
@endsection
