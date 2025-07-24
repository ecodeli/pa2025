@extends('layouts.app')

@section('title', 'Suivi des livraisons')

@section('content')
    <link rel="stylesheet" href="{{ secure_asset('css/layout-suivi-colis.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <div class="container py-5">
        <h2 class="mb-3 fw-bold">Suivi des livraisons</h2>
        <p class="text-muted mb-4">Voici vos colis actuellement en cours de livraison.</p>

        <div id="delivery-list" class="row g-4">
            <!-- Les cartes de livraison seront injectées ici -->
        </div>
    </div>

    {{-- FontAwesome pour les icônes --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const API_URL = '/api';
            const token = localStorage.getItem('token');
            const deliveryList = document.getElementById('delivery-list');

            fetch(`${API_URL}/api/livraison/my-deliveries`, {
                headers: { Authorization: 'Bearer ' + token }
            })
                .then(res => res.json())
                .then(data => {
                    if (!Array.isArray(data)) throw new Error("Format inattendu");

                    data.forEach(livraison => {
                        const isDelivered = livraison.status === 'delivered';
                        const status = isDelivered ? 'livree' : 'en-attente';
                        const statusLabel = isDelivered ? 'Livré' : 'En attente';
                        const statusIcon = isDelivered ? 'fa-check-circle' : 'fa-hourglass-half';

                        const col = document.createElement('div');
                        col.className = 'col-md-6 col-lg-4';

                        col.innerHTML = `
        <a href="/client/suivi-colis/${livraison.listing_id}" class="delivery-card ${status}">
            <div class="delivery-card-header">
                <i class="fas ${statusIcon} ${isDelivered ? 'text-success' : 'text-warning'}"></i>
            </div>
            <div class="delivery-card-body">
                <div class="delivery-title">${livraison.annonce_title}</div>
                <div class="delivery-destination"><strong>Destination :</strong> ${livraison.arrival_city}</div>
                <div class="delivery-status ${status}">
                    <i class="fas ${isDelivered ? 'fa-check' : 'fa-hourglass'}"></i> ${statusLabel}
                </div>
            </div>
        </a>
    `;

                        deliveryList.appendChild(col);
                    });

                })
                .catch(err => {
                    console.error('Erreur chargement livraisons :', err);
                    deliveryList.innerHTML = `<div class="col-12"><p class="text-danger">Impossible de charger les livraisons.</p></div>`;
                });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@endsection
