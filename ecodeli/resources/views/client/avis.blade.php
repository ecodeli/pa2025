@extends('layouts.app')

@section('title', 'Laisser un avis')

@section('content')
    <link href="{{ secure_asset('css/client/layout-avis.css') }}" rel="stylesheet">

    <div class="container py-4">
        <h2 class="mb-4">Laisser un avis</h2>

        <div id="annonce-details">Chargement…</div>

        <div id="avis-form-container" style="display:none;">
            <h4 class="mt-4" id="avis-titre">Votre avis</h4>

            <form id="avis-form">
                <div class="form-group mb-3">
                    <label for="note">Note</label>
                    <div id="star-rating" style="font-size: 2rem;">
                        <span class="star" data-value="1">&#9734;</span>
                        <span class="star" data-value="2">&#9734;</span>
                        <span class="star" data-value="3">&#9734;</span>
                        <span class="star" data-value="4">&#9734;</span>
                        <span class="star" data-value="5">&#9734;</span>
                    </div>
                    <input type="hidden" id="note" name="note" required>
                </div>

                <div class="form-group mb-3">
                    <label for="commentaire">Commentaire</label>
                    <textarea id="commentaire" name="commentaire" class="form-control" rows="3" required></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Envoyer l'avis</button>
            </form>

            <div id="avis-status" class="mt-3"></div>
        </div>
    </div>

    <style>
        #star-rating .star {
            cursor: pointer;
            color: grey;
        }

        #star-rating .star.selected,
        #star-rating .star.hover {
            color: gold;
        }
    </style>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', async () => {
                const API_URL = '/api(';
                const token = localStorage.getItem('token');
                const annonceId = window.location.pathname.split('/')[3];
                const annonceDetails = document.getElementById('annonce-details');
                const formContainer = document.getElementById('avis-form-container');
                const form = document.getElementById('avis-form');
                const statusDiv = document.getElementById('avis-status');
                const noteInput = document.getElementById('note');
                const avisTitre = document.getElementById('avis-titre');
                let courierId = null;
                let clientId = null;
                let userId = null;
                let role = null;

                if (!token) {
                    annonceDetails.innerHTML = '<p class="text-danger">Vous devez être connecté.</p>';
                    return;
                }

                try {
                    const userResp = await fetch(`${API_URL}/api/user`, {
                        headers: { Authorization: 'Bearer ' + token }
                    });
                    const user = await userResp.json();
                    role = user.role;
                    userId = user.user_id;
                } catch (e) {
                    console.error('Erreur récupération utilisateur :', e);
                    annonceDetails.innerHTML = '<p class="text-danger">Impossible de récupérer vos informations.</p>';
                    return;
                }

                const reviewEndpoint = role === 'courier'
                    ? `/api/annonce/${annonceId}/for-review-client`
                    : `/api/annonce/${annonceId}/for-review`;

                fetch(`${API_URL}${reviewEndpoint}`, {
                    headers: { Authorization: 'Bearer ' + token }
                })
                    .then(res => res.json())
                    .then(data => {
                        if (!data || data.error) {
                            annonceDetails.innerHTML = '<p class="text-danger">Annonce introuvable.</p>';
                            return;
                        }

                        if (role === 'courier') {
                            clientId = data.client_id;
                        } else {
                            courierId = data.courier_id;
                        }

                        const personName = role === 'courier' ? data.client_name : data.courier_name;

                        annonceDetails.innerHTML = `
                            <div class="annonce-card">
                                <h3>${data.annonce_title}</h3>
                                <p><strong>${role === 'courier' ? 'Client' : 'Livreur'} :</strong> ${personName ?? 'Non assigné'}</p>
                            </div>
                        `;

                        avisTitre.innerText = role === 'courier'
                            ? "Votre avis sur le client"
                            : "Votre avis sur le livreur";

                        formContainer.style.display = 'block';
                    })
                    .catch(err => {
                        console.error(err);
                        annonceDetails.innerHTML = '<p class="text-danger">Erreur serveur.</p>';
                    });

                const stars = document.querySelectorAll('#star-rating .star');
                stars.forEach(star => {
                    star.addEventListener('mouseover', () => {
                        stars.forEach(s => s.classList.remove('hover'));
                        for (let i = 0; i < star.dataset.value; i++) {
                            stars[i].classList.add('hover');
                        }
                    });

                    star.addEventListener('mouseout', () => {
                        stars.forEach(s => s.classList.remove('hover'));
                    });

                    star.addEventListener('click', () => {
                        stars.forEach(s => s.classList.remove('selected'));
                        for (let i = 0; i < star.dataset.value; i++) {
                            stars[i].classList.add('selected');
                        }
                        noteInput.value = star.dataset.value;
                    });
                });

                form.addEventListener('submit', e => {
                    e.preventDefault();

                    const note = noteInput.value;
                    const commentaire = document.getElementById('commentaire').value;

                    if (!note || !commentaire) {
                        statusDiv.innerHTML = '<p class="text-danger">Veuillez remplir tous les champs.</p>';
                        return;
                    }

                    const endpoint = role === 'courier'
                        ? `/api/annonce/${annonceId}/review-client`
                        : `/api/annonce/${annonceId}/review`;

                    const payload = {
                        note,
                        commentaire
                    };

                    if (role === 'courier') {
                        payload.user_id = clientId;
                    } else {
                        payload.courier_id = courierId;
                    }

                    fetch(`${API_URL}${endpoint}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Authorization: 'Bearer ' + token
                        },
                        body: JSON.stringify(payload)
                    })
                        .then(res => res.json())
                        .then(resp => {
                            if (resp.success) {
                                statusDiv.innerHTML = '<p class="text-success">Avis envoyé avec succès.</p>';
                                form.reset();
                                stars.forEach(s => s.classList.remove('selected'));
                                noteInput.value = '';
                            } else {
                                statusDiv.innerHTML = '<p class="text-danger">' + (resp.error || 'Erreur lors de l\'envoi.') + '</p>';
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            statusDiv.innerHTML = '<p class="text-danger">Erreur serveur.</p>';
                        });
                });
            });
        </script>
    @endpush
@endsection
