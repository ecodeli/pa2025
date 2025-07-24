@extends('layouts.app')

@section('title', 'Expéditeurs - EcoDeli')

@section('content')
    <link rel="stylesheet" href="{{ secure_asset('css/layout-expéditeurs.css') }}">

    <div class="expediteur-hero">
        <div class="container">
            <div class="expediteur-text">
                <h1>Envoyez vos colis autrement 🚀</h1>
                <p>Profitez d'un réseau de livreurs disponibles près de chez vous. Plus simple, plus rapide, plus écolo.</p>
                <a href="/register" class="btn-call">Je deviens expéditeur</a>
            </div>
            <div class="expediteur-img">
                <img src="{{ secure_asset('images/expedier.png') }}" alt="Expéditeur illustration">
            </div>
        </div>
    </div>

    <section class="expediteur-avantages">
        <h2>Pourquoi choisir EcoDeli ?</h2>
        <div class="avantages-grid">
            <div class="avantage">
                <h3>Livraison flexible</h3>
                <p>Choisissez le livreur qui correspond à votre timing et à votre lieu.</p>
            </div>
            <div class="avantage">
                <h3>Prix avantageux</h3>
                <p>Tarifs plus bas qu’un service classique grâce à l’optimisation des trajets.</p>
            </div>
            <div class="avantage">
                <h3>Impact réduit</h3>
                <p>Chaque colis livré limite les trajets inutiles. Bon pour vous, bon pour la planète.</p>
            </div>
        </div>
    </section>

    <section class="expediteur-fonctionnement">
        <h2>Comment ça marche ?</h2>
        <div class="etapes-grid">
            <div class="etape">
                <img src="{{ secure_asset('images/deposer.png') }}" alt="Ajout annonce">
                <h3>1. Déposez votre annonce</h3>
                <p>Renseignez les infos sur votre colis (poids, destination, date).</p>
            </div>
            <div class="etape">
                <img src="{{ secure_asset('images/transporteur.png') }}" alt="Choisir livreur">
                <h3>2. Sélectionnez un livreur</h3>
                <p>Choisissez parmi les livreurs disponibles et vérifiés.</p>
            </div>
            <div class="etape">
                <img src="{{ secure_asset('images/colis.png') }}" alt="Suivi colis">
                <h3>3. Suivez votre colis</h3>
                <p>Suivi en temps réel jusqu’à la livraison.</p>
            </div>
        </div>
    </section>

    <section class="expediteur-cta">
        <h2>Prêt à expédier facilement ?</h2>
        <a href="/register" class="btn-call">Créer une annonce</a>
    </section>
@endsection
