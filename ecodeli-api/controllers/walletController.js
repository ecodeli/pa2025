const db = require('../config/db');
const stripe = require('stripe')(process.env.STRIPE_SECRET_KEY);


exports.getWallet = (req, res) => {
    const userId = req.user.user_id;
    const sql = `SELECT wallet_id, CAST(balance AS DECIMAL(10,2)) AS balance FROM wallet WHERE user_id = ?`;
    db.query(sql, [userId], (err, rows) => {
        if (err) return res.status(500).json({ error: 'Erreur serveur' });
        if (rows.length === 0) return res.status(404).json({ error: 'Portefeuille introuvable' });
        res.json(rows[0]);
    });
};


exports.getTransactions = (req, res) => {
    const userId = req.user.user_id;

    db.query(`SELECT wallet_id FROM wallet WHERE user_id = ?`, [userId], (err, walletRows) => {
        if (err) return res.status(500).json({ error: 'Erreur serveur' });
        if (walletRows.length === 0) return res.status(404).json({ error: 'Portefeuille introuvable' });

        const walletId = walletRows[0].wallet_id;

        const sql = `
            SELECT transaction_id, amount, type, description, created_at
            FROM wallet_transactions
            WHERE wallet_id = ?
            ORDER BY created_at DESC
        `;
        db.query(sql, [walletId], (err2, txns) => {
            if (err2) return res.status(500).json({ error: 'Erreur serveur' });
            res.json({ data: txns });
        });
    });
};


exports.createCheckoutSession = async (req, res) => {
    const userId = req.user?.user_id;
    const { amount } = req.body;

    if (!userId) return res.status(401).json({ error: 'Non authentifié' });
    if (!amount || amount <= 0) return res.status(400).json({ error: 'Montant invalide' });

    try {
        const session = await stripe.checkout.sessions.create({
            payment_method_types: ['card'],
            line_items: [{
                price_data: {
                    currency: 'eur',
                    product_data: { name: `Dépôt EcoDeli user ${userId}` },
                    unit_amount: Math.round(amount * 100),
                },
                quantity: 1,
            }],
            mode: 'payment',
            success_url: '/api/wallet/success?session_id={CHECKOUT_SESSION_ID}',
            cancel_url: '/api/wallet/cancel',
            metadata: { user_id: userId, amount }
        });

        res.json({ url: session.url });

    } catch (err) {
        console.error("Erreur createCheckoutSession :", err);
        res.status(500).json({ error: 'Erreur Stripe' });
    }
};


exports.checkoutSuccess = async (req, res) => {
    const sessionId = req.body.session_id;

    try {
        const session = await stripe.checkout.sessions.retrieve(sessionId);
        const userId = session.metadata.user_id;
        const amount = session.metadata.amount; // ✅ ici amount est défini

        console.log("Session récupérée :", session);
        console.log("userId:", userId, "amount:", amount);

        if (session.payment_status !== 'paid') {
            console.log("Paiement non confirmé");
            return res.status(400).json({ error: 'Paiement non confirmé' });
        }

        // Reste de ton code utilisant amount
        const description = `Ajout de ${parseFloat(amount).toFixed(2)} €`;

        db.query(`SELECT wallet_id FROM wallet WHERE user_id = ?`, [userId], (err, rows) => {
            if (err) {
                console.error("Erreur SELECT wallet_id :", err);
                return res.status(500).json({ error: 'Erreur serveur' });
            }

            if (rows.length === 0) {
                console.error("Aucun wallet_id trouvé pour user_id :", userId);
                return res.status(404).json({ error: 'Wallet non trouvé' });
            }

            const walletId = rows[0].wallet_id;

            db.query(`UPDATE wallet SET balance = balance + ? WHERE user_id = ?`, [amount, userId], (err2) => {
                if (err2) {
                    console.error("Erreur UPDATE balance :", err2);
                    return res.status(500).json({ error: 'Erreur mise à jour wallet' });
                }

                db.query(`INSERT INTO wallet_transactions (wallet_id, sender_id, receiver_id, amount, type, description)
                          VALUES (?, ?, ?, ?, 'credit', ?)`,
                    [walletId, userId, userId, amount, description],
                    (err3) => {
                        if (err3) {
                            console.error("Erreur INSERT transaction :", err3);
                            return res.status(500).json({ error: 'Erreur transaction' });
                        }

                        console.log("Transaction ajoutée avec succès");
                        res.json({ success: true });
                    });
            });
        });
    } catch (err) {
        console.error("Erreur checkoutSuccess :", err);
        res.status(500).json({ error: 'Erreur serveur' });
    }
};




exports.withdraw = (req, res) => {
    const userId = req.user.user_id;
    const amount = parseFloat(req.body.amount);

    console.log("Retrait demandé par userId:", userId, "Montant:", amount);

    if (!userId) return res.status(401).json({ error: 'Non authentifié' });
    if (isNaN(amount) || amount <= 0) return res.status(400).json({ error: 'Montant invalide' });

    db.query(`SELECT balance, wallet_id FROM wallet WHERE user_id = ?`, [userId], (err, rows) => {
        if (err) {
            console.error("Erreur SELECT wallet:", err);
            return res.status(500).json({ error: 'Erreur serveur' });
        }
        if (rows.length === 0) return res.status(404).json({ error: 'Wallet introuvable' });

        const { balance, wallet_id } = rows[0];
        if (balance < amount) return res.status(400).json({ error: 'Solde insuffisant' });

        // Mise à jour du solde
        db.query(`UPDATE wallet SET balance = balance - ? WHERE user_id = ?`, [amount, userId], (err2) => {
            if (err2) {
                console.error("Erreur UPDATE balance retrait:", err2);
                return res.status(500).json({ error: 'Erreur mise à jour wallet' });
            }

            // Insertion transaction
            const description = `Retrait de ${parseFloat(amount).toFixed(2)} €`;

            db.query(`INSERT INTO wallet_transactions (wallet_id, sender_id, receiver_id, amount, type, description)
                      VALUES (?, ?, ?, ?, 'debit', ?)`,
                [wallet_id, userId, userId, amount, description],
                (err3) => {
                    if (err3) {
                        console.error("Erreur INSERT transaction retrait:", err3);
                        return res.status(500).json({ error: 'Erreur transaction' });
                    }

                    console.log("Retrait enregistré avec succès");
                    res.json({ success: true });
                });
        });
    });
};
