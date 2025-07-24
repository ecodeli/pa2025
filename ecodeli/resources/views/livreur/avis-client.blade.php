@extends('layouts.app')

@section('title', 'Laisser un avis client')

@section('content')
    <link href="{{ secure_asset('css/client/layout-avis.css') }}" rel="stylesheet">

    <div class="container py-4">
        <h2 class="mb-4">Laisser un avis sur le client</h2>

        <div id="annonce-details">Chargement…</div>

        <div id="avis-form-container" style="display:none;">
            <h4 class="mt-4">Votre avis</h4>

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
            document.addEventListener('DOMContentLoaded', () => {
                const API_URL = '/api(';
                const token = localStorage.getItem('token');
                const annonceId = window.location.pathname.split('/')[3]; // /{role}/annonce/{id}/avis
                const role = window.location.pathname.split('/')[1]; // 'client' ou 'livreur'
                const isClient = role === 'client';

                const annonceDetails = document.getElementById('annonce-details');
                const formContainer = document.getElementById('avis-form-container');
                const form = document.getElementById('avis-form');
                const statusDiv = document.getElementById('avis-status');
                const noteInput = document.getElementById('note');
                let targetId = null;

                if (!token) {
                    annonceDetails.innerHTML = '<p class="text-danger">Vous devez être connecté.</p>';
                    return;
                }

                // URL dynamiques
                const infoURL = isClient
                    ? `${API_URL}/api/annonce/${annonceId}/for-review`            // infos livreur
                    : `${API_URL}/api/annonce/${annonceId}/for-review-client`;    // infos client

                const postURL = isClient
                    ? `${API_URL}/api/annonce/${annonceId}/review`                // envoie avis client → livreur
                    : `${API_URL}/api/annonce/${annonceId}/review-client`;        // envoie avis livreur → client

                fetch(infoURL, {
                    headers: { Authorization: 'Bearer ' + token }
                })
                    .then(res => res.json())
                    .then(data => {
                        if (!data || data.error) {
                            annonceDetails.innerHTML = '<p class="text-danger">Annonce introuvable.</p>';
                            return;
                        }

                        targetId = isClient ? data.courier_id : data.client_id;

                        annonceDetails.innerHTML = `
                <div class="annonce-card">
                    <h3>${data.annonce_title}</h3>
                    <p><strong>${isClient ? 'Livreur' : 'Client'} :</strong> ${isClient ? data.courier_name : data.client_name ?? 'Non assigné'}</p>
                </div>
            `;

                        formContainer.style.display = 'block';
                    })
                    .catch(err => {
                        console.error(err);
                        annonceDetails.innerHTML = '<p class="text-danger">Erreur serveur.</p>';
                    });

                // Système d’étoiles
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

                // Soumission du formulaire
                form.addEventListener('submit', e => {
                    e.preventDefault();

                    const note = noteInput.value;
                    const commentaire = document.getElementById('commentaire').value;

                    if (!note || !commentaire) {
                        statusDiv.innerHTML = '<p class="text-danger">Veuillez remplir tous les champs.</p>';
                        return;
                    }

                    // Clé dynamique : user_id ou courier_id
                    const bodyData = {
                        note,
                        commentaire,
                        [isClient ? 'courier_id' : 'user_id']: targetId
                    };

                    console.log({
                        postURL,
                        bodyData,
                        role,
                        isClient,
                        targetId
                    });


                    fetch(postURL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Authorization: 'Bearer ' + token
                        },
                        body: JSON.stringify(bodyData)
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
