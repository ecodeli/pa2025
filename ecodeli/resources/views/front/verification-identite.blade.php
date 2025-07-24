@extends('layouts.app')

@section('title', 'Vérification d\'identité')

@section('content')
    <link href="{{ secure_asset('css/layout-verification.css') }}" rel="stylesheet">

    <div class="container py-4 text-center">
        <h2 class="mb-4">Vérification d'identité</h2>

        <div id="verification-section">
            <button id="start-stripe-verif" class="btn btn-primary mb-4">Vérifier pièce d'identité ou permis</button>

            <div id="upload-address-section" style="display: none;">
                <h4 class="mb-2">Justificatif de domicile</h4>
                <form id="address-form" enctype="multipart/form-data">
                    <input type="file" name="address" accept=".pdf,image/*" required>
                    <br />
                    <button type="submit" class="btn btn-primary mt-2">Envoyer le justificatif</button>
                </form>
                <div id="address-status" class="mt-3"></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            const API_URL = '/api';
            const urlParams = new URLSearchParams(window.location.search);
            const sessionId = urlParams.get("session_id") || localStorage.getItem('stripe_session_id');
            const urlToken = urlParams.get("t");
            const token = localStorage.getItem('token') || urlToken;

            const verificationSection = document.getElementById('verification-section');
            const stripeBtn = document.getElementById("start-stripe-verif");
            const uploadSection = document.getElementById("upload-address-section");
            const form = document.getElementById("address-form");
            const statusBox = document.getElementById("address-status");

            if (!token) {
                verificationSection.innerHTML = '<p class="text-danger">Vous devez être connecté.</p>';
                return;
            }

            try {
                const resUser = await fetch(`${API_URL}/api/user`, {
                    headers: { Authorization: 'Bearer ' + token }
                });

                if (!resUser.ok) throw new Error("Échec récupération user");

                const currentUser = (await resUser.json()).user;

                if (currentUser.identity_verified === 1 && currentUser.domicile_verified === 1) {
                    verificationSection.innerHTML = `
                        <div class="alert alert-success full-centered">
                            Tout est vérifié !
                        </div>
                    `;
                    return;
                }

                if (currentUser.identity_verified === 1) {
                    uploadSection.style.display = "block";
                }

                // 🟡 Vérifie session Stripe uniquement si sessionId défini
                if (sessionId && sessionId !== 'undefined') {
                    try {
                        const resStripe = await fetch(`${API_URL}/api/identity/status/${sessionId}`, {
                            headers: { Authorization: 'Bearer ' + token }
                        });
                        const result = await resStripe.json();

                        if (result.status !== "verified") {
                            verificationSection.innerHTML = `
                                <div class="alert alert-danger">
                                    La vérification a échoué ou est incomplète.
                                </div>
                                <button id="retry-btn" class="btn btn-secondary mt-3">Réessayer</button>
                            `;
                            document.getElementById("retry-btn").addEventListener("click", () => {
                                window.location.href = "/verification-identite";
                            });
                        } else {
                            // Nettoyage après succès
                            localStorage.removeItem('stripe_session_id');
                        }
                    } catch (err) {
                        console.error("Erreur récupération Stripe:", err);
                    }
                }

            } catch (err) {
                console.error(err);
                verificationSection.innerHTML = '<p class="text-danger">Erreur lors de la récupération de votre compte.</p>';
                return;
            }

            // Lancer Stripe Identity
            stripeBtn.addEventListener("click", async () => {
                try {
                    const res = await fetch(`${API_URL}/api/identity/start`, {
                        method: "POST",
                        headers: { Authorization: "Bearer " + token }
                    });
                    const data = await res.json();
                    if (data.url && data.session_id) {
                        localStorage.setItem('stripe_session_id', data.session_id);
                        window.location.href = data.url;
                    } else {
                        alert("Erreur création session Stripe Identity");
                    }
                } catch (e) {
                    alert("Erreur réseau");
                }
            });

            // Upload justificatif de domicile
            form.addEventListener("submit", async (e) => {
                e.preventDefault();
                const formData = new FormData(form);
                try {
                    const res = await fetch(`${API_URL}/api/upload-document`, {
                        method: "POST",
                        headers: { Authorization: "Bearer " + token },
                        body: formData
                    });
                    const data = await res.json();
                    if (data.success) {
                        statusBox.innerHTML = "<p class='text-success'>Document envoyé. En attente de validation.</p>";
                        form.reset();
                    } else {
                        statusBox.innerHTML = "<p class='text-danger'>Erreur lors de l'envoi.</p>";
                    }
                } catch {
                    statusBox.innerHTML = "<p class='text-danger'>Erreur serveur.</p>";
                }
            });
        });
    </script>
@endpush
