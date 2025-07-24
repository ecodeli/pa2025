@extends('layouts.app')

@section('title', 'Nos Services - EcoDeli')

@section('content')

    <link rel="stylesheet" href="{{ secure_asset('css/layout-nos-services.css') }}">

    <section class="page-header">
        <h1>Nos Services</h1>
        <p>Découvrez l'ensemble des services proposés par EcoDeli pour faciliter votre quotidien.</p>
    </section>

    <section class="services-colis">
        <h2>📦 Services de livraison de colis</h2>
        <div class="services-grid">
            <div class="service-card">
                <h3>Annonce de livraison</h3>
                <p>Publiez vos annonces pour envoyer un colis ou un achat à n’importe quelle destination.</p>
            </div>
            <div class="service-card">
                <h3>Livraison collaborative</h3>
                <p>Faites livrer vos colis par des particuliers sur des trajets existants, de manière économique et écologique.</p>
            </div>
            <div class="service-card">
                <h3>Suivi en temps réel</h3>
                <p>Suivez l’avancement de votre livraison en temps réel grâce à notre système de tracking intégré.</p>
            </div>
            <div class="service-card">
                <h3>Assurance colis</h3>
                <p>Chaque envoi est couvert par notre système d’assurance. Jusqu’à 3000€ avec l’abonnement Premium.</p>
            </div>
            <div class="service-card">
                <h3>Livraison multi-étapes</h3>
                <p>Vos colis peuvent être stockés temporairement dans nos entrepôts (Paris, Lyon, Marseille...)</p>
            </div>
            <div class="service-card">
                <h3>Chariot connecté</h3>
                <p>Depuis un commerce partenaire, demandez la livraison à domicile directement en caisse.</p>
            </div>
        </div>
    </section>

    <section class="services-personne">
        <h2>Services à la personne</h2>
        <div class="services-grid">
            <div class="service-card">
                <h3>Transport de personnes</h3>
                <p>Emmenez un voisin, un proche ou une personne âgée à un rendez-vous, une gare ou un aéroport.</p>
            </div>
            <div class="service-card">
                <h3>Transfert aéroport</h3>
                <p>Planifiez vos trajets vers/depuis les aéroports grâce aux membres de la communauté EcoDeli.</p>
            </div>
            <div class="service-card">
                <h3>Courses à domicile</h3>
                <p>Un livreur EcoDeli peut effectuer vos courses et vous les livrer selon votre liste.</p>
            </div>
            <div class="service-card">
                <h3>Achat à l’étranger</h3>
                <p>Commandez des produits introuvables en France, livrés par des voyageurs depuis l’étranger.</p>
            </div>
            <div class="service-card">
                <h3>Garde d’animaux</h3>
                <p>Faites garder vos animaux par un membre de la communauté pendant vos déplacements.</p>
            </div>
            <div class="service-card">
                <h3>Petits travaux</h3>
                <p>Demandez de l’aide pour le ménage, le jardinage ou d’autres services ponctuels.</p>
            </div>
        </div>
    </section>

    <section class="cta-abonnement">
        <h2>Nos formules d’abonnement</h2>
        <div class="formules">
            <div class="formule">
                <h3>Free</h3>
                <p>Services de base sans réduction ni assurance.</p>
            </div>
            <div class="formule">
                <h3>Starter - 9,90€/mois</h3>
                <ul>
                    <li>Assurance jusqu’à 115€</li>
                    <li>Réduction de 5% sur l’envoi</li>
                    <li>5% du montant de
                        l’envoi en supplément</li>
                    <li>Réduction permante de 5% uniquement sur les petits colis</li>
                </ul>
            </div>
            <div class="formule">
                <h3>Premium - 19,99€/mois</h3>
                <ul>
                    <li>Assurance jusqu’à 3000€</li>
                    <li>Réduction de 9% sur l’envoi / Premier envoi offert si inférieur à 150€</li>
                    <li>3 envois prioritaires offerts par mois,au-delà 5% du montant de l’envoi en supplément</li>
                    <li>Réduction de 5% sur tous les colis</li>
                </ul>
            </div>
        </div>
    </section>

@endsection
