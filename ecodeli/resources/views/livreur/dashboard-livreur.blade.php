@extends('layouts.app')

@section('title', 'Dashboard Livreur')

@section('content')
    <x-require-auth :role="['courier']" />
    <link rel="stylesheet" href="{{ secure_asset('css/livreur/dashboard-livreur.css') }}">

    <div class="max-w-6xl mx-auto p-6 space-y-10">
        <h1 class="text-3xl font-bold text-gray-800">Bienvenue sur votre espace livreur</h1>

        <!-- Section Livraisons -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Vos livraisons</h2>
            <ul class="space-y-3">
                <li class="border p-4 rounded-lg shadow-sm">
                    Annonce "MacBook Air" → Marseille <br>
                    Statut : <span class="text-green-600 font-semibold">Livrée</span>
                </li>
            </ul>
        </div>

        <!-- Statistiques -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Statistiques</h2>
            <ul>
                <li>Total livraisons : 12</li>
                <li>Note moyenne : ⭐ 4.7</li>
                <li>Revenus estimés : 420€</li>
            </ul>
        </div>
    </div>
@endsection
