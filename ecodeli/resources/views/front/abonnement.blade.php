@extends('layouts.app')

@section('title', 'Choisir un abonnement')

@section('content')
    <link rel="stylesheet" href="{{ secure_asset('css/abonnement.css') }}">

    <div class="subscription-container">
        <h1>Choisissez votre abonnement</h1>

        <div class="plans">

            <div class="plan-card" id="starter-card">
                <h2>Free</h2>
                <p class="price">0 € / mois</p>
                <p class="desc">Aucun avantage mais vous nous êtes quand même fidèle.</p>
                <button class="subscribe-btn" data-price="0">Souscrire</button>
            </div>

            <div class="plan-card" id="starter-card">
                <h2>Standard</h2>
                <p class="price">5 € / mois</p>
                <p class="desc">5% de réduction sur vos frais de livraison.</p>
                <button class="subscribe-btn" data-price="5">Souscrire</button>
            </div>

            <div class="plan-card premium" id="premium-card">
                <h2>Premium</h2>
                <p class="price">10 € / mois</p>
                <p class="desc">10% de réduction sur vos frais de livraison.</p>
                <button class="subscribe-btn" data-price="10">Souscrire</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const API_URL = '/api(';
            const token = localStorage.getItem('token');

            // 🔧 1. Récupérer l'abonnement actuel
            fetch(`${API_URL}/api/abonnement/current`, {
                headers: { 'Authorization': 'Bearer ' + token }
            })
                .then(res => res.json())
                .then(data => {
                    const buttons = document.querySelectorAll('.subscribe-btn');

                    if (data.active) {
                        // Marquer l'abonnement actuel
                        document.querySelectorAll('.plan-card').forEach(card => {
                            const btn = card.querySelector('.subscribe-btn');
                            const price = parseFloat(btn.getAttribute('data-price'));

                            let type = '';
                            if (price === 0) type = 'free';
                            else if (price === 5) type = 'starter';
                            else if (price === 10) type = 'premium';

                            if (type === data.type) {
                                btn.textContent = "Actuel";
                                btn.disabled = true;
                                btn.classList.add('current-plan');
                            } else {
                                btn.textContent = "Changer d'abonnement";
                                btn.disabled = false;
                                btn.classList.remove('current-plan');
                            }
                        });
                    }
                })
                .catch(err => console.error("Erreur récupération abonnement :", err));

            // 🔧 2. Souscription / changement abonnement
            document.querySelectorAll('.subscribe-btn').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const price = btn.getAttribute('data-price');

                    if (!confirm(`Confirmer votre abonnement à ${price} €/mois ?`)) return;

                    const res = await fetch(`${API_URL}/api/abonnement/subscribe`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': 'Bearer ' + token
                        },
                        body: JSON.stringify({ price })
                    });

                    const data = await res.json();
                    if (data.success) {
                        alert(data.message);
                        window.location.reload();
                    } else {
                        alert(data.error || 'Erreur lors de la souscription.');
                    }
                });
            });
        });
    </script>
@endsection
