@extends('layouts.admin')

@section('title', 'Annonces')

@section('content')
    <div class="container mt-4">
        <h2>Commerçants & Annonces</h2>

        {{-- Sélecteur de commerçant --}}
        <div class="mb-3">
            <label for="merchantSelect" class="form-label">Choisir un commerçant</label>
            <select id="merchantSelect" class="form-select w-50"></select>
        </div>

        {{-- Tableau des annonces --}}
        <table class="table table-bordered" id="listingTable">
            <thead>
            <tr>
                <th><input type="checkbox" id="checkAllListings"></th>
                <th>ID</th>
                <th>Titre</th>
                <th>Départ</th>
                <th>Arrivée</th>
                <th>Prix</th>
                <th>Statut</th>
                <th>Archivé</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody></tbody>
        </table>

        {{-- Pagination --}}
        <nav>
            <ul class="pagination" id="paginationListings"></ul>
        </nav>

        {{-- Boutons en masse --}}
        <div class="mt-3">
            <button id="btnArchive" class="btn btn-warning me-2">Archiver</button>
            <button id="btnRestore" class="btn btn-success me-2">Restaurer</button>
            <button id="btnDelListing" class="btn btn-danger">Supprimer</button>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const API_URL = "/api(";
        const token   = localStorage.getItem('token');
        let currentPage = 1, perPage = 8;
        let currentMerchant = null;

        //Charger la liste des commerçants
        async function fetchMerchants() {
            const res = await fetch(`${API_URL}/api/admin/users?page=1&per_page=100`, {
                headers: { Authorization: "Bearer " + token }
            });
            const { data } = await res.json();
            const merchants = data.filter(u => u.type === 'merchant');
            const sel = document.getElementById('merchantSelect');
            sel.innerHTML = '<option value="">-- Sélectionnez --</option>';
            merchants.forEach(m => {
                sel.innerHTML += `<option value="${m.user_id}">${m.name} (${m.email})</option>`;
            });
        }

        // Charger les annonces du commerçant
        async function fetchListings(page = 1) {
            if (!currentMerchant) return;
            currentPage = page;
            const res = await fetch(`${API_URL}/api/admin/merchants/${currentMerchant}/listings?page=${page}&per_page=${perPage}`, {
                headers: { Authorization: "Bearer " + token }
            });
            const { data, meta } = await res.json();
            renderTable(data);
            renderPagination(meta);
        }

        // 3) Afficher le tableau
        function renderTable(listings) {
            const tbody = document.querySelector('#listingTable tbody');
            tbody.innerHTML = '';
            listings.forEach(l => {
                tbody.innerHTML += `
      <tr>
        <td><input type="checkbox" class="rowChk" value="${l.listing_id}"></td>
        <td>${l.listing_id}</td>
        <td>${l.annonce_title}</td>
        <td>${l.departure_city}</td>
        <td>${l.arrival_city||'–'}</td>
        <td>${l.price}€</td>
        <td>${l.status}</td>
        <td>${l.is_archived? 'Oui' : ''}</td>
        <td>
          <button class="btn btn-sm btn-outline-warning" onclick="toggleArchive(${l.listing_id}, ${l.is_archived})">
            ${l.is_archived? 'Restaurer' : 'Archiver'}
          </button>
          <button class="btn btn-sm btn-outline-danger" onclick="deleteListing(${l.listing_id})">
            Supprimer
          </button>
        </td>
      </tr>
    `;
            });
        }

        // 4) Pagination
        function renderPagination({ total, per_page, current_page, last_page }) {
            const ul = document.getElementById('paginationListings');
            ul.innerHTML = '';
            // Précédent
            let li = `<li class="page-item ${current_page===1?'disabled':''}"><a class="page-link" href="#">Précédent</a></li>`;
            ul.insertAdjacentHTML('beforeend', li);
            // Pages
            for (let p=1; p<=last_page; p++){
                li = `<li class="page-item ${p===current_page?'active':''}"><a class="page-link" href="#">${p}</a></li>`;
                ul.insertAdjacentHTML('beforeend', li);
            }
            // Suivant
            li = `<li class="page-item ${current_page===last_page?'disabled':''}"><a class="page-link" href="#">Suivant</a></li>`;
            ul.insertAdjacentHTML('beforeend', li);

            // Bind clicks
            ul.querySelectorAll('.page-link').forEach((a,i) => {
                a.onclick = e => {
                    e.preventDefault();
                    if (i===0 && current_page>1) fetchListings(current_page-1);
                    else if (i===ul.children.length-1 && current_page<last_page) fetchListings(current_page+1);
                    else if (i>0 && i<= last_page) fetchListings(i);
                };
            });
        }

        // 5) Actions individuelles
        async function toggleArchive(id, currently) {
            await fetch(`${API_URL}/api/admin/listings/${id}/archive`, {
                method:'PUT',
                headers:{
                    'Content-Type':'application/json',
                    Authorization:'Bearer '+token
                },
                body: JSON.stringify({ archive: !currently })
            });
            fetchListings(currentPage);
        }
        async function deleteListing(id) {
            if (!confirm('Supprimer annonce #'+id+' ?')) return;
            await fetch(`${API_URL}/api/admin/listings/${id}`, {
                method:'DELETE',
                headers:{ Authorization:'Bearer '+token }
            });
            fetchListings(currentPage);
        }

        // 6) Actions en masse
        function getSelected() {
            return Array.from(document.querySelectorAll('.rowChk:checked')).map(c=>c.value);
        }
        document.getElementById('checkAllListings').onchange = e => {
            document.querySelectorAll('.rowChk').forEach(c=>c.checked = e.target.checked);
        };
        document.getElementById('btnArchive').onclick = async () => {
            const ids = getSelected(); if(!ids.length)return alert('Aucune sélection');
            await Promise.all(ids.map(id=>fetch(`${API_URL}/api/admin/listings/${id}/archive`,{
                method:'PUT',
                headers:{
                    'Content-Type':'application/json',
                    Authorization:'Bearer '+token
                },
                body: JSON.stringify({ archive: true })
            })));
            fetchListings(currentPage);
        };
        document.getElementById('btnRestore').onclick = async () => {
            const ids = getSelected(); if(!ids.length)return alert('Aucune sélection');
            await Promise.all(ids.map(id=>fetch(`${API_URL}/api/admin/listings/${id}/archive`,{
                method:'PUT',
                headers:{
                    'Content-Type':'application/json',
                    Authorization:'Bearer '+token
                },
                body: JSON.stringify({ archive: false })
            })));
            fetchListings(currentPage);
        };
        document.getElementById('btnDelListing').onclick = async () => {
            const ids = getSelected(); if(!ids.length)return alert('Aucune sélection');
            if(!confirm(`Supprimer ${ids.length} annonce(s) ?`)) return;
            await Promise.all(ids.map(id=>fetch(`${API_URL}/api/admin/listings/${id}`,{
                method:'DELETE',
                headers:{ Authorization:'Bearer '+token }
            })));
            fetchListings(currentPage);
        };

        // 7) Initialisation
        document.addEventListener('DOMContentLoaded', async () => {
            await fetchMerchants();
            document.getElementById('merchantSelect').onchange = e => {
                currentMerchant = e.target.value;
                fetchListings(1);
            };
        });
    </script>
@endpush
