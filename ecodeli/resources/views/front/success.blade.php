@extends('layouts.app')

@section('title', 'Paiement réussi')

@section('content')
    <div class="wallet-container">
        <h1>Paiement réussi </h1>
        <p>Votre portefeuille va être mis à jour. Redirection en cours...</p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            const API_URL = '/api';
            const token = localStorage.getItem('token');

            const urlParams = new URLSearchParams(window.location.search);
            const sessionId = urlParams.get('session_id');

            if (!sessionId) {
                alert('Session ID manquant');
                window.location.href = '/wallet';
                return;
            }

            // Appelle l'API pour confirmer et créditer le wallet
            const res = await fetch(`${API_URL}/api/wallet/checkout-success`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + token
                },
                body: JSON.stringify({ session_id: sessionId })
            });

            const data = await res.json();
            if (data.success) {
                // Redirection vers wallet après succès
                window.location.href = '/wallet';
            } else {
                alert('Erreur lors de la confirmation du paiement');
                window.location.href = '/wallet';
            }
        });
    </script>
@endsection
