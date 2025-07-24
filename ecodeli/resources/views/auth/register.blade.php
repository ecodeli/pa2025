@extends('layouts.app')

@section('content')
    <div class="register-container">
        <link href="{{ secure_asset('css/layout-register.css') }}" rel="stylesheet">
        <h2>Créer un compte</h2>

        <div id="error-message" class="error-message" style="color: red; display: none;"></div>

        <form id="registerForm">
            <div class="form-group">
                <label for="user_type">Je suis un :</label>
                <select id="user_type" required>
                    <option value="">Sélectionnez un type de compte</option>
                    <option value="client">Client</option>
                    <option value="merchant">Marchand</option>
                    <option value="courier">Livreur</option>
                    <option value="service_provider">Prestataire de service</option>
                </select>
            </div>

            <div class="form-group">
                <label for="nom">Nom :</label>
                <input type="text" id="nom" required>
            </div>

            <div class="form-group">
                <label for="prenom">Prénom :</label>
                <input type="text" id="prenom" required>
            </div>

            <div class="form-group">
                <label for="email">Email :</label>
                <input type="email" id="email" required>
            </div>

            <div class="form-group">
                <label for="phone">Téléphone :</label>
                <input type="tel" id="phone" pattern="[0-9]{10}" required>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe :</label>
                <input type="password" id="password" required>
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirmer le mot de passe :</label>
                <input type="password" id="password_confirmation" required>
            </div>

            <div class="form-group">
                <button type="submit">S'inscrire</button>
            </div>
        </form>

        <p class="login-link">Vous avez déjà un compte ? <a href="{{ route('login') }}">Se connecter</a></p>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById("registerForm").addEventListener("submit", async function(e) {
            e.preventDefault();

            const password = document.getElementById("password").value;
            const confirmPassword = document.getElementById("password_confirmation").value;

            if (password !== confirmPassword) {
                document.getElementById("error-message").style.display = "block";
                document.getElementById("error-message").innerText = "Les mots de passe ne correspondent pas.";
                return;
            }

            const data = {
                name: document.getElementById("prenom").value + " " + document.getElementById("nom").value,
                email: document.getElementById("email").value,
                password: password,
                type: document.getElementById("user_type").value,
                phone: document.getElementById("phone").value,
                address: "Adresse non précisée"
            };

            try {
                const res = await fetch("/api(/api/register", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(data)
                });

                const result = await res.json();

                if (res.ok) {
                    localStorage.setItem("token", result.token);
                    window.location.href = "/";
                } else {
                    document.getElementById("error-message").style.display = "block";
                    document.getElementById("error-message").innerText = result.message || "Erreur lors de l'inscription.";
                }
            } catch (err) {
                console.log(err);
            }
        });
    </script>
@endpush
