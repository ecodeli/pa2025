@extends('layouts.app')

@section('title', 'Mes notifications')

@section('content')
    <link rel="stylesheet" href="{{ secure_asset('css/notifications.css') }}">
    <div class="container py-4">
        <h1>Mes notifications</h1>

        <div id="notifications-container" class="mt-4">
            <p>Chargement…</p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const API_URL = '/api(';
            const token = localStorage.getItem('token');
            const container = document.getElementById('notifications-container');

            if (!token) {
                container.innerHTML = "<p class='text-danger'>Veuillez vous connecter pour voir vos notifications.</p>";
                return;
            }

            fetch(`${API_URL}/api/notifications/my`, {
                headers: { Authorization: 'Bearer ' + token }
            })
                .then(res => res.json())
                .then(data => {
                    const notifications = data.notifications;
                    if (!notifications || notifications.length === 0) {
                        container.innerHTML = "<p>Aucune notification.</p>";
                        return;
                    }

                    const list = document.createElement('ul');
                    list.classList.add('list-group');

                    notifications.forEach(n => {
                        const item = document.createElement('li');
                        item.className = 'list-group-item d-flex justify-content-between align-items-center';
                        item.innerHTML = `
                        <span>${n.message}</span>
                        <small class="text-muted">${new Date(n.send_date).toLocaleString()}</small>
                    `;
                        if (!n.is_read) {
                            item.classList.add('fw-bold');
                        }
                        list.appendChild(item);
                    });

                    container.innerHTML = '';
                    container.appendChild(list);
                })
                .catch(err => {
                    console.error(err);
                    container.innerHTML = "<p class='text-danger'>Erreur lors du chargement des notifications.</p>";
                });
        });
    </script>
@endsection
