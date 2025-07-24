@extends('layouts.app')

@section('title', 'Modifier mon profil')

@section('content')
<link rel="stylesheet" href="{{ secure_asset('css/edit-profile.css') }}">

<div class="edit-profile-container">
    <div class="edit-profile-header">
        <a href="javascript:history.back()" class="back-button">
            ← Retour au profil
        </a>
        <h1>Modifier mon profil</h1>
        <p>Gérez vos informations personnelles et paramètres de compte</p>
    </div>

    <div id="success-message" class="success-message" style="display: none;">
        <span>Informations mises à jour avec succès !</span>
    </div>

    <div id="error-message" class="error-message" style="display: none;">
        <span id="error-text"></span>
    </div>

    <div class="profile-sections">
        <!-- Section Informations personnelles -->
        <div class="profile-section">
            <h2>Informations personnelles</h2>
            <form id="personal-info-form">
                <div class="form-group">
                    <label for="name">Nom complet *</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="phone">Numéro de téléphone</label>
                    <input type="tel" id="phone" name="phone" placeholder="Ex: +33 6 12 34 56 78">
                </div>

                <div class="form-group">
                    <label for="address">Adresse</label>
                    <textarea id="address" name="address" rows="3" placeholder="Votre adresse complète"></textarea>
                </div>

                <!-- Champs spécifiques selon le type d'utilisateur -->
                <div id="professional-fields" style="display: none;">
                    <div class="form-group">
                        <label for="company_name">Nom de l'entreprise</label>
                        <input type="text" id="company_name" name="company_name" placeholder="Nom de votre entreprise">
                    </div>

                    <div class="form-group">
                        <label for="siret">Numéro SIRET</label>
                        <input type="text" id="siret" name="siret" placeholder="Ex: 12345678901234">
                    </div>
                </div>

                <div id="courier-fields" style="display: none;">
                    <div class="form-group">
                        <label for="vehicle_type">Type de véhicule</label>
                        <select id="vehicle_type" name="vehicle_type">
                            <option value="">Sélectionner un véhicule</option>
                            <option value="bike">Vélo</option>
                            <option value="scooter">Scooter</option>
                            <option value="car">Voiture</option>
                            <option value="van">Camionnette</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="license_plate">Plaque d'immatriculation</label>
                        <input type="text" id="license_plate" name="license_plate" placeholder="Ex: AB-123-CD">
                    </div>
                </div>

                <button type="submit" id="save-personal-info" class="btn-save">
                    Enregistrer les modifications
                </button>
            </form>
        </div>

        <!-- Section Mot de passe -->
        <div class="profile-section">
            <h2>🔒 Changer le mot de passe</h2>
            <form id="password-form">
                <div class="form-group">
                    <label for="current_password">Mot de passe actuel *</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>

                <div class="form-group">
                    <label for="new_password">Nouveau mot de passe *</label>
                    <input type="password" id="new_password" name="new_password" required minlength="6">
                    <small>Minimum 6 caractères</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirmer le nouveau mot de passe *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <button type="submit" id="save-password" class="btn-save">
                    Changer le mot de passe
                </button>
            </form>
        </div>

        <!-- Section Informations du compte -->
        <div class="profile-section">
            <h2>ℹ️ Informations du compte</h2>
            <div class="account-info">
                <div class="info-item">
                    <strong>Type de compte:</strong> <span id="user-type"></span>
                </div>
                <div class="info-item">
                    <strong>Date d'inscription:</strong> <span id="registration-date"></span>
                </div>
                <div class="info-item">
                    <strong>Statut:</strong>
                    <span id="account-status" class="status-badge"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<form id="edit-profile-form">
    @csrf

    <script>
        document.addEventListener("DOMContentLoaded", async () => {
            const token = localStorage.getItem('token');
            const res = await fetch('/api/api/user', {
                headers: { 'Authorization': 'Bearer ' + token }
            });
            const data = await res.json();

            if (data.user) {
                document.getElementById('name').value = data.user.name || '';
                document.getElementById('email').value = data.user.email || '';
                document.getElementById('phone').value = data.user.phone || '';
                document.getElementById('address').value = data.user.address || '';
                // Remplir les informations du compte
                document.getElementById('user-type').textContent = data.user.type || '';
                document.getElementById('registration-date').textContent = new Date(data.user.registration_date).toLocaleDateString('fr-FR');
                document.getElementById('account-status').textContent = data.user.status || 'Actif';
            }

            document.getElementById('personal-info-form').addEventListener('submit', async function(e) {
                e.preventDefault();

                const token = localStorage.getItem('token');

                const payload = {
                    name: document.getElementById('name').value,
                    email: document.getElementById('email').value,
                    phone: document.getElementById('phone').value,
                    address: document.getElementById('address').value
                };

                const res = await fetch('/api/api/user', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + token
                    },
                    body: JSON.stringify(payload)
                });

                const result = await res.json();

                const successMessage = document.getElementById('success-message');
                const errorMessage = document.getElementById('error-message');
                const errorText = document.getElementById('error-text');

                if (res.ok) {
                    successMessage.style.display = 'block';
                    errorMessage.style.display = 'none';
                } else {
                    successMessage.style.display = 'none';
                    errorText.textContent = result.message || 'Erreur lors de la mise à jour.';
                    errorMessage.style.display = 'block';
                }
            });

            document.getElementById('password-form').addEventListener('submit', async function(e) {
                e.preventDefault();

                const current = document.getElementById('current_password').value;
                const password = document.getElementById('new_password').value;
                const confirm = document.getElementById('confirm_password').value;

                const errorMessage = document.getElementById('error-message');
                const errorText = document.getElementById('error-text');
                const successMessage = document.getElementById('success-message');

                if (password !== confirm) {
                    errorMessage.style.display = 'block';
                    successMessage.style.display = 'none';
                    errorText.textContent = 'Les mots de passe ne correspondent pas.';
                    return;
                }

                const token = localStorage.getItem('token');

                const response = await fetch('/api/api/user', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + token
                    },
                    body: JSON.stringify({
                        current_password: current,
                        password: password
                    })
                });

                const result = await response.json();

                if (response.ok) {
                    successMessage.style.display = 'block';
                    errorMessage.style.display = 'none';
                } else {
                    errorMessage.style.display = 'block';
                    successMessage.style.display = 'none';
                    errorText.textContent = result.message || 'Une erreur est survenue.';
                }
            });
        });
    </script>
</form>


@endsection
