@extends('layouts.admin')

@section('content')
    <div class="container">
        <h2>Gestion des boxes de stockage</h2>

        <label for="warehouseSelect">Sélectionner un entrepôt :</label>
        <select id="warehouseSelect" class="form-select mb-4">
            <option value="">-- Choisir --</option>
        </select>

        <table class="table table-bordered" id="boxTable" style="display:none;">
            <thead>
            <tr>
                <th>ID</th>
                <th>Date de début</th>
                <th>Date de fin</th>
                <th>Utilisateur</th>
                <th>Statut</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <script>
        const token = localStorage.getItem('token');
        let allBoxes = [];
        let currentPage = 1;
        const perPage = 10;

        const tbody = document.querySelector('#boxTable tbody');
        const table = document.getElementById('boxTable');

        // Charger entrepôts
        fetch('/api(/api/admin/warehouses', {
            headers: { "Authorization": `Bearer ${token}` }
        })
            .then(res => res.json())
            .then(data => {
                const select = document.getElementById('warehouseSelect');
                data.forEach(w => {
                    const opt = document.createElement('option');
                    opt.value = w.warehouse_id;
                    opt.textContent = `${w.city} - ${w.address}`;
                    select.appendChild(opt);
                });
            });

        // Écouteur de changement d'entrepôt
        document.getElementById('warehouseSelect').addEventListener('change', function () {
            const warehouseId = this.value;
            if (!warehouseId) return table.style.display = 'none';

            fetch(`/api(/api/admin/boxes/${warehouseId}`, {
                headers: { "Authorization": `Bearer ${token}` }
            })
                .then(res => res.json())
                .then(data => {
                    allBoxes = data;
                    currentPage = 1;
                    renderTable();
                    renderPagination();
                    table.style.display = '';
                });
        });

        function renderTable() {
            tbody.innerHTML = '';
            const start = (currentPage - 1) * perPage;
            const boxes = allBoxes.slice(start, start + perPage);

            boxes.forEach(box => {
                const row = `
                <tr>
                    <td>${box.box_id}</td>
                    <td>${box.start_date ? new Date(box.start_date).toLocaleDateString('fr-FR') : ''}</td>
                    <td>${box.end_date ? new Date(box.end_date).toLocaleDateString('fr-FR') : ''}</td>
                    <td>${box.user_name ?? '—'}</td>
                    <td>${box.status}</td>
                    <td>
                        ${box.status === 'reserved' ? `
                            <button class="btn btn-sm btn-warning" onclick="libererBox(${box.box_id}, this)">Libérer</button>
                        ` : ''}
                    </td>
                </tr>
            `;
                tbody.innerHTML += row;
            });
        }

        function renderPagination() {
            let paginationContainer = document.getElementById('pagination');
            if (!paginationContainer) {
                paginationContainer = document.createElement('div');
                paginationContainer.id = 'pagination';
                paginationContainer.style.marginTop = '20px';
                table.parentNode.appendChild(paginationContainer);
            }

            const totalPages = Math.ceil(allBoxes.length / perPage);
            paginationContainer.innerHTML = '';

            for (let i = 1; i <= totalPages; i++) {
                const btn = document.createElement('button');
                btn.className = 'btn btn-sm ' + (i === currentPage ? 'btn-primary' : 'btn-outline-secondary');
                btn.style.margin = '0 4px';
                btn.textContent = i;
                btn.onclick = () => {
                    currentPage = i;
                    renderTable();
                    renderPagination();
                };
                paginationContainer.appendChild(btn);
            }
        }

        function libererBox(boxId) {
            fetch('/api(/api/admin/boxes/free', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({ box_id: boxId })
            })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    document.getElementById('warehouseSelect').dispatchEvent(new Event('change'));
                });
        }
    </script>
@endsection
