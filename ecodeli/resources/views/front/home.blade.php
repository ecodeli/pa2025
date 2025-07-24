@extends('layouts.app')

@section('title', 'Accueil - EcoDeli')

@section('content')

    <section class="hero">
        <div class="hero-content">
            <div class="textes">
                <h1>
                    {{__('Faites voyager vos colis autrement,')}}<br>
                    <span>{{__('économique et écologique !')}}</span>
                </h1>
                <p>
                    {{__('Envoyez vos colis partout en France !')}}<br>
                    {{__('C’est rapide, flexible et bon pour la planète 🌱')}}
                </p>
                <div class="boutons">
                    <a href="/marketplace" class="btn btn-yellow">{{__('Expédier / recevoir un colis')}}</a>
                    <a href="/marketplace" class="btn btn-blue">{{__('Des colis sur ma route ?')}}</a>
                </div>
            </div>
            <div class="illustration">
                <img src="{{ secure_asset('images/telephone.png') }}" alt="Illustration téléphone">
            </div>
        </div>
    </section>

    <section class="fonctionnement">
        <h2>{{__('Comment ça marche ?')}}</h2>
        <div class="etapes">
            <div class="etape">
                <img src="{{ secure_asset('images/annonce.png') }}" alt="Annonce">
                <h3>{{__('1. Publiez votre annonce')}}</h3>
                <p>{{__('Indiquez l’objet, l’adresse et la date d’envoi.')}}</p>
            </div>
            <div class="etape">
                <img src="{{ secure_asset('images/transporteur.png') }}" alt="Transporteur">
                <h3>{{__('2. Trouvez un transporteur')}}</h3>
                <p>{{__('un livreur disponible réserve votre annonce.')}}</p>
            </div>
            <div class="etape">
                <img src="{{ secure_asset('images/colis.png') }}" alt="Colis">
                <h3>{{__('3. Recevez votre colis')}}</h3>
                <p>{{__('Votre colis est livré en toute sécurité !')}}</p>
            </div>
        </div>
    </section>

    <section class="profils">
        <h2>{{__('Rejoignez la communauté EcoDeli')}}</h2>
        <div class="profils-cartes">
            <div class="carte">
                <img src="{{ secure_asset('images/livreur.png') }}" alt="Livreur">
                <h3>{{__('Livreur')}}</h3>
                <p>{{__('Proposez vos trajets et gagnez de l\'argent en livrant.')}}</p>
            </div>
            <div class="carte">
                <img src="{{ secure_asset('images/expedier.png') }}" alt="Client">
                <h3>{{__('Client')}}</h3>
                <p>{{__('Expédiez facilement vos colis partout en France.')}}</p>
            </div>
            <div class="carte">
                <img src="{{ secure_asset('images/commercant.png') }}" alt="Commerçant">
                <h3>{{__('Commerçant')}}</h3>
                <p>{{__('Livrez vos produits à vos clients avec EcoDeli.')}}</p>
            </div>
            <div class="carte">
                <img src="{{ secure_asset('images/prestataires.png') }}" alt="Prestataire">
                <h3>{{__('Prestataire')}}</h3>
                <p>{{__('Proposez des services à la personne via EcoDeli.')}}</p>
            </div>
        </div>
    </section>

    <section class="services-personnalises">
        <h2>{{__('Bien plus que la livraison de colis')}}</h2>
        <div class="services-liste">
            <div class="service">
                <h3{{__('Transport de personnes')}}</h3>
                <p>{{__('Accompagnez une personne âgée, un voisin ou un collègue.')}}</p>
            </div>
            <div class="service">
                <h3>✈{{__('Transfert aéroport')}}</h3>
                <p>{{__('Planifiez un transport depuis ou vers un aéroport.')}}</p>
            </div>
            <div class="service">
                <h3>{{__('Courses à domicile')}}</h3>
                <p>{{__('Un livreur récupère et dépose vos courses selon votre liste.')}}</p>
            </div>
            <div class="service">
                <h3>{{__('Achat à l’étranger')}}</h3>
                <p>{{__('Recevez des produits introuvables chez vous.')}}</p>
            </div>
            <div class="service">
                <h3>{{__('Garde d’animaux')}}</h3>
                <p>{{__('Un voisin garde vos animaux pendant vos déplacements.')}}</p>
            </div>
            <div class="service">
                <h3>{{__('Petits travaux')}}</h3>
                <p>{{__('Ménage, jardinage, aide à domicile… le tout entre particuliers.')}}</p>
            </div>
        </div>
    </section>

    <section class="avantages">
        <h2>{{__('Pourquoi choisir EcoDeli ?')}}</h2>
        <div class="avantages-liste">
            <div class="avantage">
                <h3>{{__('Écologique')}}</h3>
                <p>{{__('Optimisez les trajets existants pour réduire l’empreinte carbone.')}}</p>
            </div>
            <div class="avantage">
                <h3>{{__('Économique')}}</h3>
                <p>{{__('Des tarifs imbattables grâce à la livraison collaborative.')}}</p>
            </div>
            <div class="avantage">
                <h3>{{__('Sécurisé')}}</h3>
                <p>{{__('Suivi en temps réel et assurance sur les colis.')}}</p>
            </div>
            <div class="avantage">
                <h3>{{__('+ 100 000 colis livrés')}}</h3>
                <p>{{__('Une satisfaction client de 98% sur nos livraisons.')}}</p>
            </div>
        </div>
    </section>

    <section class="logistique">
        <h2>{{__('Logistique intelligente')}}</h2>
        <p>{{__('Nos entrepôts situés à Paris, Marseille, Lyon, Lille, Montpellier et Rennes permettent une prise en charge rapide et une meilleure organisation des livraisons longues distances ou par étapes.')}}</p>
    </section>

    <section class="chariot">
        <h2>{{__('Le lâcher de chariot')}}</h2>
        <p>{{__('Faites vos courses chez un commerçant partenaire et demandez la livraison à l\'adresse et à l\'horaire de votre choix directement depuis la caisse !')}}</p>
    </section>

    <section class="social">
        <h2>{{__('Une mission sociale')}}</h2>
        <p>{{__('EcoDeli favorise le lien social et lutte contre l’isolement en permettant à chacun de rendre service à ses voisins et sa communauté.')}}</p>
    </section>

    <section class="abonnements">
        <h2>{{__('Nos formules d’abonnement')}}</h2>
        <div class="formules">
            <div class="formule">
                <h3>{{__('Free')}}</h3>
                <p>{{__('Service de base sans réduction ni assurance.')}}</p>
            </div>
            <div class="formule">
                <h3>{{__('Starter - 9,90€/mois')}}</h3>
                <ul>
                    <li>{{__('Assurance jusqu’à 115€')}}</li>
                    <li>{{__('Réduction de 5% sur l’envoi')}}</li>
                    <li>{{__('Envoi prioritaire en option')}}</li>
                </ul>
            </div>
            <div class="formule">
                <h3>{{__('Premium - 19,99€/mois')}}</h3>
                <ul>
                    <li>{{__('Assurance jusqu’à 3000€')}}</li>
                    <li>{{__('3 envois prioritaires offerts / mois')}}</li>
                    <li>{{__('Réduction de 5% sur tous les colis')}}</li>
                </ul>
            </div>
        </div>
    </section>

    <section class="aide">
        <h2>{{__('Besoin d’aide ?')}}</h2>
        <p>{{__('Consultez notre')}}<a href="#">{{__('tutoriel de démarrage')}}</a>{{__('pour tout comprendre sur EcoDeli.')}}</p>
    </section>

@endsection
