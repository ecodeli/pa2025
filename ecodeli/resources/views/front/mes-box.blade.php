@extends('layouts.app')

@section('content')
    <link href="{{ secure_asset('css/layout-mes-box.css') }}" rel="stylesheet">

    <div class="container boxes-container">
        <h2>Mes boxes de stockage</h2>
        <table class="table-boxes" id="boxes-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Ville</th>
                <th>Adresse</th>
                <th>Début</th>
                <th>Fin</th>
                <th>Statut</th>
            </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <script>
        const token = localStorage.getItem('token');

        fetch("/api(/api/warehouse-boxes", {
            headers: {
                "Authorization": `Bearer ${token}`
            }
        })
            .then(res => res.json())
            .then(data => {
                const tbody = document.querySelector('#boxes-table tbody');
                const formatDate = dateStr => new Date(dateStr).toLocaleDateString('fr-FR');

                data.forEach(box => {
                    const row = `
                <tr>
                    <td>${box.box_id}</td>
                    <td>${box.city}</td>
                    <td>${box.address}</td>
                    <td>${formatDate(box.start_date)}</td>
                    <td>${formatDate(box.end_date)}</td>
                    <td><span class="badge reserved">Réservé</span></td>
                </tr>
            `;
                    tbody.innerHTML += row;
                });
            });
    </script>
@endsection
