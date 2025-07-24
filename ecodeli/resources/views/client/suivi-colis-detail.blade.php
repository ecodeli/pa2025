@extends('layouts.app')

@section('title', 'Détails de la Livraison')

@section('content')
    <link rel="stylesheet" href="{{ secure_asset('css/layout-suivi-colis-detail.css') }}">
    <div class="container my-4">
        <div id="delivery-detail-container" class="card p-4 shadow-sm">
            <h2 class="mb-4">Détails de la Livraison</h2>
            <div id="deliveryDetailContent">
                <p>Chargement des informations...</p>
            </div>
        </div>
    </div>

    <script>
        const API_URL = '/api';
        const listingId = window.location.pathname.split('/').pop();
        const token = localStorage.getItem('token');

        fetch(`${API_URL}/api/livraison/delivery/${listingId}`, {
            headers: { Authorization: 'Bearer ' + token }
        })
            .then(response => response.json())
            .then(data => {
                const html = `
                <div class="d-flex justify-content-between mb-3 delivery-summary flex-wrap">
                    <div>
                        <h5 class="mb-1">${data.title}</h5>
                        <p><strong>De :</strong> ${data.departure_city} → <strong>À :</strong> ${data.arrival_city}</p>
                    </div>
                    <div class="text-md-end mt-2 mt-md-0">
                        <small class="text-muted">Date prévue :</small>
                        <div>${data.deadline_date || 'Non précisée'}</div>
                    </div>
                </div>

                <div class="row align-items-center mb-4">
                    <div class="col-md-6 d-flex align-items-center">
                        <div>
                            <div><strong>Livreur :</strong> ${data.courier_name}</div>
                        </div>
                    </div>
                    <div class="col-md-6 text-center mt-3 mt-md-0">
                        <div class="delivery-code-card p-4">
                            <div class="text-uppercase small text-muted">Code à remettre</div>
                            <div class="h3">${data.verification_code}</div>
                            <div class="text-muted small mt-1">Montrez ce code à votre livreur lors de la réception.</div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h5>Annonce concernée</h5>
                    <div class="delivery-description">
                        <p>${data.description}</p>
                        <p><strong>Prix :</strong> ${data.price} €</p>
                    </div>
                </div>

                <div class="mb-4">
                    <h5>Statut de la Livraison</h5>
                    <span class="badge ${data.status === 'delivered' ? 'bg-success' : 'bg-warning'}">
                        ${data.status === 'delivered' ? 'Livrée' : 'En cours'}
                    </span>
                </div>

                `;

                document.getElementById('deliveryDetailContent').innerHTML = html;
            })
            .catch(error => {
                console.error('Erreur chargement détails :', error);
                document.getElementById('deliveryDetailContent').innerHTML =
                    "<p class='text-danger'>Impossible de charger les données.</p>";
            });
    </script>

@endsection
