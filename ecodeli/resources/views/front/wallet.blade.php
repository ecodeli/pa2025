@extends('layouts.app')

@section('title', 'Mon portefeuille')

@section('content')
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="{{ secure_asset('css/wallet.css') }}" rel="stylesheet">

    <div class="wallet-container">
        <h1>Mon portefeuille</h1>

        <!-- Solde actuel -->
        <div class="wallet-balance">
            <p>Solde actuel :</p>
            <h2 id="balance">–,–– €</h2>
        </div>

        <!-- Ajout de fonds -->
        <div class="wallet-actions">
            <div class="wallet-actions-row">
                <input type="number" id="amountInput" placeholder="Montant à ajouter" required min="0.01" step="0.01">
                <button id="checkoutButton">Ajouter des fonds</button>
            </div>
        </div>

        <!-- Retrait -->
        <div class="wallet-actions">
            <div class="wallet-actions-row">
                <input type="number" id="withdrawAmount" placeholder="Montant à retirer" required min="0.01" step="0.01">
                <button id="withdrawButton">Retirer</button>
            </div>
        </div>




        <!-- Transactions -->
        <div class="wallet-transactions" style="margin-top: 30px;">
            <h3>Historique des transactions</h3>
            <table>
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Montant</th>
                    <th>Description</th>
                </tr>
                </thead>
                <tbody id="txnBody">
                <tr><td colspan="4">Chargement…</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const API_URL = '/api';
            const token = localStorage.getItem('token');

            async function fetchWallet() {
                const res = await fetch(`${API_URL}/api/wallet`, {
                    headers: { Authorization: 'Bearer ' + token }
                });
                if (!res.ok) return console.error('Wallet API error', res.status);
                const { balance } = await res.json();
                document.getElementById('balance').textContent = parseFloat(balance).toFixed(2) + ' €';
            }

            async function fetchTxns() {
                const res = await fetch(`${API_URL}/api/wallet/transactions`, {
                    headers: { Authorization: 'Bearer ' + token }
                });
                if (!res.ok) return console.error('Txns API error', res.status);
                const { data } = await res.json();
                const body = document.getElementById('txnBody');
                if (data.length === 0) {
                    body.innerHTML = '<tr><td colspan="4">Aucune transaction.</td></tr>';
                    return;
                }
                body.innerHTML = '';
                data.forEach(t => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${new Date(t.created_at).toLocaleDateString()}</td>
                        <td>${t.type === 'credit' ? 'Crédit' : 'Débit'}</td>
                        <td>${parseFloat(t.amount).toFixed(2)} €</td>
                        <td>${t.description || '-'}</td>
                    `;
                    body.appendChild(tr);
                });
            }

            fetchWallet();
            fetchTxns();

            // Stripe Checkout
            document.getElementById('checkoutButton').addEventListener('click', async () => {
                const amount = parseFloat(document.getElementById('amountInput').value);
                if (isNaN(amount) || amount <= 0) {
                    alert("Montant invalide");
                    return;
                }

                const res = await fetch(`${API_URL}/api/wallet/create-checkout-session`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + token
                    },
                    body: JSON.stringify({ amount })
                });

                const data = await res.json();
                if (data.url) {
                    window.location.href = data.url;
                } else {
                    alert('Erreur lors de la création de la session Checkout');
                }
            });

            // Retrait
            document.getElementById('withdrawButton').addEventListener('click', async () => {
                const amount = parseFloat(document.getElementById('withdrawAmount').value);
                if (isNaN(amount) || amount <= 0) {
                    alert("Montant invalide");
                    return;
                }

                const res = await fetch(`${API_URL}/api/wallet/withdraw`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + token
                    },
                    body: JSON.stringify({ amount })
                });

                const data = await res.json();
                if (data.success) {
                    alert('Retrait effectué');
                    fetchWallet();
                    fetchTxns();
                } else {
                    alert(data.error || 'Erreur lors du retrait');
                }
            });
        });
    </script>
@endsection
