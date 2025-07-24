@extends('layouts.app')
@section('title', 'Mes documents')

@section('content')
    <link href="{{ secure_asset('css/layout-documents.css') }}" rel="stylesheet">

    <div class="container py-4">
        <h2>Mes documents</h2>

        <div id="docs-container">Chargement…</div>
        <div id="pagination" class="pagination"></div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const API_URL = '/api';
                const token = localStorage.getItem('token');
                const container = document.getElementById('docs-container');
                const paginationDiv = document.getElementById('pagination');

                let currentPage = 1;

                if (!token) {
                    container.innerHTML = '<p class="text-danger">Vous devez être connecté.</p>';
                    return;
                }

                function loadDocuments(page) {
                    fetch(`${API_URL}/api/user/documents?page=${page}`, {
                        headers: { Authorization: 'Bearer ' + token }
                    })
                        .then(res => res.json())
                        .then(data => {
                            container.innerHTML = '';
                            paginationDiv.innerHTML = '';

                            if (!data.documents || data.documents.length === 0) {
                                container.innerHTML = '<p>Aucun document trouvé.</p>';
                                return;
                            }

                            const table = document.createElement('table');
                            table.className = 'table table-striped';
                            table.innerHTML = `
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Document</th>
                        <th>Date dépôt</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    ${data.documents.map(doc => `
                        <tr>
                            <td>${doc.document_type === 'identite' ? 'Identité' : 'Domicile'}</td>
                            <td><a href="${API_URL}${doc.file_path}" target="_blank">Voir</a></td>
                            <td>${new Date(doc.upload_date).toLocaleDateString('fr-FR')}</td>
                            <td>${parseInt(doc.is_verified) === 1 ? 'Vérifié' : 'En attente'}</td>
                            <td><button class="btn-delete" data-id="${doc.document_id}">Supprimer</button></td>
                        </tr>
                    `).join('')}
                </tbody>
            `;
                            container.appendChild(table);

                            // Pagination
                            const prev = document.createElement('button');
                            prev.innerText = 'Précédent';
                            prev.disabled = data.currentPage === 1;
                            prev.onclick = () => {
                                currentPage--;
                                loadDocuments(currentPage);
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
                                loadDocuments(currentPage);
                            };
                            paginationDiv.appendChild(next);

                            // Delete buttons
                            document.querySelectorAll('.btn-delete').forEach(btn => {
                                btn.addEventListener('click', () => {
                                    const documentId = btn.dataset.id;
                                    if (confirm("Confirmer suppression de ce document ?")) {
                                        fetch(`${API_URL}/api/user/delete-document`, {
                                            method: 'DELETE',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                Authorization: 'Bearer ' + token
                                            },
                                            body: JSON.stringify({ document_id: documentId })
                                        })
                                            .then(res => res.json())
                                            .then(() => loadDocuments(currentPage))
                                            .catch(() => alert("Erreur suppression"));
                                    }
                                });
                            });
                        })
                        .catch(err => {
                            console.error(err);
                            container.innerHTML = '<p class="text-danger">Erreur serveur.</p>';
                        });
                }

                loadDocuments(currentPage);
            });
        </script>
    @endpush
@endsection
