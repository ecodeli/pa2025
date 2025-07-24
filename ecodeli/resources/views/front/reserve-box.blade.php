@extends('layouts.app')
@section('title', 'Réserver un box')

@section('content')
    <link href="{{ secure_asset('css/layout-reserve-box.css') }}" rel="stylesheet">

    <div class="container py-5">
        <h2 class="text-center mb-4">Entrepôts disponibles</h2>

        <div id="warehouses" class="warehouse-grid">
            <!-- Cartes entrepôts dynamiques -->
        </div>

        <div id="reservationSection" class="mt-5 d-none">
            <h4 class="text-center mb-3">Réserver un box dans <span id="selected-city"></span></h4>

            <div class="card p-4 mx-auto shadow" style="max-width: 500px;">
                <form id="reservation-form">
                    <input type="hidden" id="warehouse_id">
                    <div class="form-group mb-3">
                        <label for="start_date">Date de début</label>
                        <input type="date" class="form-control" id="start_date" required>
                    </div>

                    <div class="form-group mb-3">
                        <label for="end_date">Date de fin</label>
                        <input type="date" class="form-control" id="end_date" required>
                    </div>

                    <div class="form-group mb-3 text-center">
                        <strong>30 € / mois</strong>
                    </div>


                    <button type="submit" class="btn btn-dark w-100">Réserver ce box</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const token = localStorage.getItem('token');
            const container = document.getElementById('warehouses');
            const section = document.getElementById('reservationSection');
            const selectedCity = document.getElementById('selected-city');

            // Charger entrepôts avec authentification
            fetch('/api/api/storage/availability', {
                headers: {
                    'Authorization': 'Bearer ' + token
                }
            })
                .then(res => {
                    if (!res.ok) throw new Error("Erreur " + res.status);
                    return res.json();
                })
                .then(data => {
                    if (!Array.isArray(data)) {
                        alert("Erreur inattendue côté serveur.");
                        return;
                    }

                    container.innerHTML = ''; // Vide le container

                    data.forEach(w => {
                        const card = document.createElement('div');
                        card.className = 'warehouse-card';
                        card.dataset.id = w.warehouse_id;
                        card.dataset.city = w.city;

                        card.innerHTML = `
                <h5>${w.city}</h5>
                <p>${w.available_boxes} box${w.available_boxes > 1 ? 'es' : ''} disponibles</p>
            `;

                        container.appendChild(card);
                    });

                    // Gestion du clic
                    document.querySelectorAll('.warehouse-card').forEach(card => {
                        card.addEventListener('click', () => {
                            const city = card.dataset.city;
                            const id = card.dataset.id;

                            document.querySelectorAll('.warehouse-card').forEach(c => c.classList.remove('active'));
                            card.classList.add('active');

                            selectedCity.textContent = city;
                            document.getElementById('warehouse_id').value = id;
                            section.classList.remove('d-none');
                            section.scrollIntoView({ behavior: 'smooth' });
                        });
                    });
                })
                .catch(err => {
                    console.error("Erreur de chargement :", err);
                    alert("Erreur lors du chargement des entrepôts. Veuillez vous reconnecter.");
                });

            // Réservation
            document.getElementById('reservation-form').addEventListener('submit', e => {
                e.preventDefault();

                const start_date = document.getElementById('start_date').value;
                const end_date = document.getElementById('end_date').value;
                const warehouse_id = document.getElementById('warehouse_id').value;

                fetch('/api/api/storage/reserve', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Authorization: 'Bearer ' + token
                    },
                    body: JSON.stringify({ start_date, end_date, warehouse_id })
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            window.location.reload();
                        } else {
                            alert(data.error || "Erreur lors de la réservation.");
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert("Erreur serveur.");
                    });
            });
        });
    </script>
@endsection
