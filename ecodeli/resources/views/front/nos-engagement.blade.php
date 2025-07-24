@extends('layouts.app')

@section('title', 'Nos Engagements - EcoDeli')

@section('content')
    <link rel="stylesheet" href="{{ secure_asset('css/layout-nos-engagement.css') }}">

    <div class="engagement-hero">
        <div class="container">
            <h1>Nos Engagements</h1>
            <p>Chez EcoDeli, on livre autrement : avec cœur, bon sens et respect.</p>
        </div>
    </div>

    <section class="engagements-principes">
        <h2>Des valeurs concrètes, pas des promesses en l’air</h2>
        <div class="engagements-grid">
            <div class="engagement-card">
                <img src="{{ secure_asset('images/ecologie.png') }}" alt="Écologie">
                <h3>Écologie</h3>
                <p>Optimisation des trajets, partenaires locaux, zéro emballage superflu.</p>
            </div>
            <div class="engagement-card">
                <img src="{{ secure_asset('images/securisé.png') }}" alt="Confiance">
                <h3>Confiance</h3>
                <p>Identités vérifiées, profils notés, paiements sécurisés.</p>
            </div>
            <div class="engagement-card">
                <img src="{{ secure_asset('images/solidarite.png') }}" alt="Solidarité">
                <h3>Solidarité</h3>
                <p>Une partie des trajets permet de rendre service dans son quartier.</p>
            </div>
            <div class="engagement-card">
                <img src="{{ secure_asset('images/choix-trajets.png') }}" alt="Humain">
                <h3>Humain</h3>
                <p>Pas d’algorithme opaque : vous choisissez vos trajets et missions.</p>
            </div>
        </div>
    </section>

    <section class="engagement-cta">
        <h2>Un projet pour demain, à vivre aujourd’hui</h2>
        <a href="/register" class="btn-call">Rejoindre la communauté</a>
    </section>
@endsection
