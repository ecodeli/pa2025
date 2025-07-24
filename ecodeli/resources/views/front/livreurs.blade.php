@extends('layouts.app')

@section('title', 'Livreurs - EcoDeli')

@section('content')
    <link rel="stylesheet" href="{{ secure_asset('css/layout-livreurs.css') }}">

    <div class="livreur-hero">
        <div class="container">
            <div class="livreur-text">
                <h1>Devenez Livreur avec EcoDeli</h1>
                <p>Travaillez librement, livrez localement, gagnez rapidement.</p>
                <a href="/register" class="btn-call">Je deviens livreur</a>
            </div>
            <div class="livreur-img">
                <img src="{{ secure_asset('images/livreurs.png') }}" alt="Choisir livreur">
            </div>
        </div>
    </div>

    <section class="livreur-benefits">
        <h2>Pourquoi devenir livreur ?</h2>
        <div class="benefits-grid">
            <div class="benefit">
                <h3>Liberté totale</h3>
                <p>Choisissez vos horaires et vos trajets. Vous êtes votre propre patron.</p>
            </div>
            <div class="benefit">
                <h3>Gains immédiats</h3>
                <p>Recevez votre paiement directement après chaque livraison.</p>
            </div>
            <div class="benefit">
                <h3>Missions variées</h3>
                <p>Livraisons, courses, petits services... à vous de choisir ce que vous préférez.</p>
            </div>
            <div class="benefit">
                <h3>Impact positif</h3>
                <p>Participez à une livraison écologique et solidaire.</p>
            </div>
        </div>
    </section>

    <section class="livreur-how">
        <h2>Comment ça marche ?</h2>
        <div class="steps-grid">
            <div class="step">
                <img src="{{ secure_asset('images/register.png') }}" alt="Créer compte">
                <h3>1. Créez un compte</h3>
                <p>Inscrivez-vous et validez votre profil.</p>
            </div>
            <div class="step">
                <img src="{{ secure_asset('images/trajets.png') }}" alt="Trajets">
                <h3>2. Déclarez vos trajets</h3>
                <p>Indiquez vos trajets habituels ou vos disponibilités.</p>
            </div>
            <div class="step">
                <img src="{{ secure_asset('images/accepter.png') }}" alt="Accepter mission">
                <h3>3. Acceptez des missions</h3>
                <p>Choisissez les colis ou services à livrer.</p>
            </div>
            <div class="step">
                <img src="{{ secure_asset('images/gains.png') }}" alt="Paiement">
                <h3>4. Recevez vos gains</h3>
                <p>Une fois livré, l’argent est à vous.</p>
            </div>
        </div>
    </section>

    <section class="livreur-testimonies">
        <h2>Ils en parlent mieux que nous</h2>
        <div class="testimonies-grid">
            <div class="testimony">
                <img src="{{ secure_asset('images/soso.jpg') }}" alt="Sofiane">
                <p><strong>Sofiane</strong> – Étudiant</p>
                <p>“Je rentabilise mes trajets en livrant des colis, c’est efficace.”</p>
            </div>
            <div class="testimony">
                <img src="{{ secure_asset('images/daronne.jpg') }}" alt="Sarah">
                <p><strong>Sarah</strong> – Maman</p>
                <p>“Flexible, simple, je livre quand j’ai du temps libre.”</p>
            </div>
        </div>
    </section>
@endsection
