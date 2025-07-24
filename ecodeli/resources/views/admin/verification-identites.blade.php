@extends('layouts.admin')
@section('title', 'Vérification des identités')

@section('content')
    <link href="{{ secure_asset('css/layout-admin.css') }}" rel="stylesheet">

    <div class="container py-4">
        <h2 class="mb-4">Vérification des identités</h2>

        <div class="search-bar mb-3">
            <input type="text" id="search-input" placeholder="Rechercher un utilisateur..." />
            <button id="search-btn">Rechercher</button>
        </div>

        <table class="table table-striped">
            <thead>
            <tr>
                <th>Utilisateur</th>
                <th>Type</th>
                <th>Document</th>
                <th>Date dépôt</th>
                <th>Statut</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody id="docs-container">
            <tr><td colspan="6">Chargement…</td></tr>
            </tbody>
        </table>

        <div id="pagination" class="pagination"></div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const API_URL = '/api(';
                const token = localStorage.getItem('token');
                const container = document.getElementById('docs-container');
                const paginationDiv = document.getElementById('pagination');
                const searchInput = document.getElementById('search-input');
                const searchBtn = document.getElementById('search-btn');

                let currentPage = 1;
                let currentSearch = '';

                if (!token) {
                    container.innerHTML = '<tr><td colspan="6">Non connecté.</td></tr>';
                    return;
                }

                function loadPage(page, search='') {
                    fetch(`${API_URL}/api/admin/documents?page=${page}&search=${search}`, {
                        headers: { Authorization: 'Bearer ' + token }
                    })
                        .then(res => res.json())
                        .then(data => {
                            container.innerHTML = '';
                            paginationDiv.innerHTML = '';

                            if (!data.documents || data.documents.length === 0) {
                                container.innerHTML = '<tr><td colspan="6">Aucun document trouvé.</td></tr>';
                                return;
                            }

                            data.documents.forEach(doc => {
                                const tr = document.createElement('tr');
                                tr.innerHTML = `
                    <td>${doc.name}</td>
                    <td>${
                                    doc.document_type === 'identite' ? 'Identité' :
                                        doc.document_type === 'domicile' ? 'Justificatif de domicile' :
                                            doc.document_type === 'permis' ? 'Permis de conduire' :
                                                'Autre'}</td>
                    <td><a href="${API_URL}${doc.file_path}" target="_blank">Voir</a></td>
                    <td>${new Date(doc.upload_date).toLocaleDateString('fr-FR')}</td>
                    <td>
    ${
                                    parseInt(doc.is_verified) === 1
                                        ? 'Vérifié'
                                        : parseInt(doc.is_verified) === -1
                                            ? 'Refusé'
                                            : 'En attente'
                                }
</td>

                    <td>
    ${parseInt(doc.is_verified) === 1
                                    ? `<button class="btn-revoke" data-id="${doc.document_id}">Révoquer</button>`
                                    : `
        <button class="btn-validate" data-id="${doc.document_id}">Valider</button>
        <button class="btn-refuse" data-id="${doc.document_id}">Refuser</button>
        `}
</td>

                `;
                                container.appendChild(tr);
                            });

                            const prev = document.createElement('button');
                            prev.innerText = 'Précédent';
                            prev.disabled = data.currentPage === 1;
                            prev.onclick = () => {
                                currentPage--;
                                loadPage(currentPage, currentSearch);
                            };
                            paginationDiv.appendChild(prev);

                            const pageNum = document.createElement('span');
                            pageNum.innerText = `Page ${data.currentPage}/${data.totalPages}`;
                            paginationDiv.appendChild(pageNum);

                            const next = document.createElement('button');
                            next.innerText = 'Suivant';
                            next.disabled = data.currentPage === data.totalPages;
                            next.onclick = () => {
                                currentPage++;
                                loadPage(currentPage, currentSearch);
                            };
                            paginationDiv.appendChild(next);

                            document.querySelectorAll('.btn-validate').forEach(btn => {
                                btn.addEventListener('click', () => {
                                    const documentId = btn.dataset.id;
                                    if (confirm("Confirmer validation de ce document ?")) {
                                        fetch(`${API_URL}/api/admin/validate-document`, {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                Authorization: 'Bearer ' + token
                                            },
                                            body: JSON.stringify({ document_id: documentId })
                                        })
                                            .then(res => res.json())
                                            .then(() => loadPage(currentPage, currentSearch))
                                            .catch(() => alert("Erreur validation"));
                                    }
                                });
                            });

                            document.querySelectorAll('.btn-refuse').forEach(btn => {
                                btn.addEventListener('click', () => {
                                    const documentId = btn.dataset.id;
                                    if (confirm("Refuser ce document ?")) {
                                        fetch(`${API_URL}/api/admin/refuse-document`, {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                Authorization: 'Bearer ' + token
                                            },
                                            body: JSON.stringify({ document_id: documentId })
                                        })
                                            .then(res => res.json())
                                            .then(() => loadPage(currentPage, currentSearch))
                                            .catch(() => alert("Erreur refus"));
                                    }
                                });
                            });

                            document.querySelectorAll('.btn-revoke').forEach(btn => {
                                btn.addEventListener('click', () => {
                                    const documentId = btn.dataset.id;
                                    if (confirm("Révoquer la validation de ce document ?")) {
                                        fetch(`${API_URL}/api/admin/revoke-document`, {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                Authorization: 'Bearer ' + token
                                            },
                                            body: JSON.stringify({ document_id: documentId })
                                        })
                                            .then(res => res.json())
                                            .then(() => loadPage(currentPage, currentSearch))
                                            .catch(() => alert("Erreur révocation"));
                                    }
                                });
                            });

                        })
                        .catch(err => {
                            console.error(err);
                            container.innerHTML = '<tr><td colspan="6">Erreur serveur.</td></tr>';
                        });
                }

                searchBtn.addEventListener('click', () => {
                    currentSearch = searchInput.value.trim();
                    currentPage = 1;
                    loadPage(currentPage, currentSearch);
                });

                loadPage(currentPage);
            });
        </script>
    @endpush
@endsection
