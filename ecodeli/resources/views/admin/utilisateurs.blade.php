@extends('layouts.admin')

@section('title', 'Utilisateurs')

@section('content')
    <div class="container mt-4">

        <!-- Actions en masse -->
        <div class="d-flex mb-3">
            <select id="bulkActionType" class="form-select w-auto me-2">
                <option value="" selected>Changer le type…</option>
                <option value="client">Client</option>
                <option value="merchant">Marchand</option>
                <option value="courier">Livreur</option>
                <option value="service_provider">Prestataire</option>
            </select>
            <button id="btnChangeType" class="btn btn-primary me-2">Appliquer</button>
            <button id="btnBan"    class="btn btn-warning me-2">Bannir</button>
            <button id="btnUnban"  class="btn btn-success me-2">Débannir</button>
            <button id="btnDelete" class="btn btn-danger">Supprimer</button>
        </div>

        <!-- Tableau des utilisateurs -->
        <table class="table table-bordered" id="userTable">
            <thead>
            <tr>
                <th><input type="checkbox" id="checkAll"></th>
                <th>Nom</th>
                <th>Email</th>
                <th>Type</th>
                <th>Annonces</th>
                <th>Documents</th>
                <th>Banni</th>
            </tr>
            </thead>
            <tbody>
            <!-- Lignes injectées par JS -->
            </tbody>
        </table>

        <!-- Pagination -->
        <nav>
            <ul class="pagination" id="paginationControls"></ul>
        </nav>
    </div>
@endsection

@push('scripts')
    <script>
        const API_URL = "/api";
        const token   = localStorage.getItem('token');
        let currentPage = 1, perPage = 8;

        // Récupère et affiche la page demandée
        async function fetchUsers(page = 1) {
            currentPage = page;
            const res = await fetch(`${API_URL}/api/admin/users?page=${page}&per_page=${perPage}`, {
                headers: { Authorization: "Bearer " + token }
            });
            if (!res.ok) return console.error('Erreur HTTP', res.status);
            const { data, meta } = await res.json();
            renderTable(data);
            renderPagination(meta);
        }

        // Affiche les lignes du tableau
        function renderTable(users) {
            const tbody = document.querySelector("#userTable tbody");
            tbody.innerHTML = '';
            users.forEach(u => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
      <td><input type="checkbox" class="rowCheck" value="${u.user_id}"></td>
      <td>${u.name}</td>
      <td>${u.email}</td>
      <td>${u.type}</td>
      <td>${u.annonces_count}</td>
      <td>${u.documents_count}</td>
      <td>${u.is_banned ? '✅' : ''}</td>
    `;
                tbody.appendChild(tr);
            });
        }

        // Génère les contrôles de pagination
        function renderPagination({ total, per_page, current_page, last_page }) {
            const ul = document.getElementById("paginationControls");
            ul.innerHTML = '';

            // Précédent
            const prevLi = document.createElement("li");
            prevLi.className = `page-item ${current_page === 1 ? 'disabled' : ''}`;
            prevLi.innerHTML = `<a class="page-link" href="#">Précédent</a>`;
            prevLi.onclick = () => current_page > 1 && fetchUsers(current_page - 1);
            ul.appendChild(prevLi);

            // Numéros
            for (let p = 1; p <= last_page; p++) {
                const li = document.createElement("li");
                li.className = `page-item ${p === current_page ? 'active' : ''}`;
                li.innerHTML = `<a class="page-link" href="#">${p}</a>`;
                li.onclick = () => fetchUsers(p);
                ul.appendChild(li);
            }

            // Suivant
            const nextLi = document.createElement("li");
            nextLi.className = `page-item ${current_page === last_page ? 'disabled' : ''}`;
            nextLi.innerHTML = `<a class="page-link" href="#">Suivant</a>`;
            nextLi.onclick = () => current_page < last_page && fetchUsers(current_page + 1);
            ul.appendChild(nextLi);
        }

        // Récupère les IDs cochés
        function getSelectedIds() {
            return Array.from(document.querySelectorAll('.rowCheck:checked'))
                .map(cb => cb.value);
        }

        // Sélection / désélection global
        document.getElementById('checkAll').addEventListener('change', e => {
            document.querySelectorAll('.rowCheck').forEach(cb => cb.checked = e.target.checked);
        });

        // Action : Changer le type
        document.getElementById('btnChangeType').addEventListener('click', async () => {
            const ids = getSelectedIds();
            const newType = document.getElementById('bulkActionType').value;
            if (!newType || !ids.length) return alert('Sélection + type requis');
            await Promise.all(ids.map(id =>
                fetch(`${API_URL}/api/admin/users/${id}/type`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        Authorization: 'Bearer ' + token
                    },
                    body: JSON.stringify({ newType })
                })
            ));
            fetchUsers(currentPage);
        });

        // Action : Bannir
        document.getElementById('btnBan').addEventListener('click', async () => {
            const ids = getSelectedIds();
            if (!ids.length) return alert('Sélection requise');
            await Promise.all(ids.map(id =>
                fetch(`${API_URL}/api/admin/users/${id}/ban`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        Authorization: 'Bearer ' + token
                    },
                    body: JSON.stringify({ ban: true })
                })
            ));
            fetchUsers(currentPage);
        });

        // Action : Débannir
        document.getElementById('btnUnban').addEventListener('click', async () => {
            const ids = getSelectedIds();
            if (!ids.length) return alert('Sélection requise');
            await Promise.all(ids.map(id =>
                fetch(`${API_URL}/api/admin/users/${id}/ban`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        Authorization: 'Bearer ' + token
                    },
                    body: JSON.stringify({ ban: false })
                })
            ));
            fetchUsers(currentPage);
        });

        // Action : Supprimer
        document.getElementById('btnDelete').addEventListener('click', async () => {
            const ids = getSelectedIds();
            if (!ids.length) return alert('Sélection requise');
            if (!confirm('Confirmer la suppression de ' + ids.length + ' utilisateur(s) ?')) return;
            await Promise.all(ids.map(id =>
                fetch(`${API_URL}/api/admin/users/${id}`, {
                    method: 'DELETE',
                    headers: { Authorization: 'Bearer ' + token }
                })
            ));
            fetchUsers(currentPage);
        });
        document.addEventListener('DOMContentLoaded', () => {
            fetchUsers(1);
        });
    </script>
@endpush
