const db = require('../config/db');

exports.subscribe = (req, res) => {
    const { price } = req.body;
    const userId = req.user.user_id;

    let reduction = 0;
    let type = '';

    if (price == 0) {
        reduction = 0;
        type = 'free';
    } else if (price == 5) {
        reduction = 5;
        type = 'starter';
    } else if (price == 10) {
        reduction = 10;
        type = 'premium';
    } else {
        return res.status(400).json({ error: "Abonnement invalide." });
    }

    // 1. Vérifier le solde du wallet
    const walletSql = `SELECT wallet_id, balance FROM wallet WHERE user_id = ?`;

    db.query(walletSql, [userId], (errW, walletRows) => {
        if (errW || walletRows.length === 0) {
            console.error("Erreur récupération wallet :", errW);
            return res.status(500).json({ error: "Erreur wallet" });
        }

        const wallet = walletRows[0];
        if (parseFloat(wallet.balance) <parseFloat(price)) {
            return res.status(400).json({ message: 'Solde insuffisant dans votre portefeuille.' });
        }


        // 2. Résilier l'ancien abonnement (si actif)
        const cancelOldSql = `
            UPDATE subscriptions
            SET status = 'terminated', end_date = CURDATE()
            WHERE user_id = ? AND status = 'active'
        `;

        db.query(cancelOldSql, [userId], (errCancel) => {
            if (errCancel) {
                console.error("Erreur résiliation abonnement :", errCancel);
                return res.status(500).json({ error: "Erreur résiliation" });
            }

            // 3. Débiter le wallet
            const updateWalletSql = `UPDATE wallet SET balance = balance - ? WHERE wallet_id = ?`;

            db.query(updateWalletSql, [price, wallet.wallet_id], (errU) => {
                if (errU) {
                    console.error("Erreur mise à jour wallet :", errU);
                    return res.status(500).json({ error: "Erreur débit wallet" });
                }

                // 4. Créer la transaction wallet
                const txnSql = `
                    INSERT INTO wallet_transactions (wallet_id, amount, type, description, sender_id, receiver_id)
                    VALUES (?, ?, 'debit', ?, ?, ?)
                `;
                const desc = `Paiement abonnement ${type}`;
                db.query(txnSql, [wallet.wallet_id, price, desc, userId, userId], (errTx) => {
                    if (errTx) {
                        console.error("Erreur transaction wallet :", errTx);
                        return res.status(500).json({ error: "Erreur transaction" });
                    }

                    // 5. Souscrire le nouvel abonnement
                    const subSql = `
                        INSERT INTO subscriptions (type, start_date, status, user_id)
                        VALUES (?, CURDATE(), 'active', ?)
                    `;

                    db.query(subSql, [type, userId], (errS) => {
                        if (errS) {
                            console.error("Erreur souscription :", errS);
                            return res.status(500).json({ error: "Erreur souscription" });
                        }

                        res.json({ success: true, message: `Abonnement ${type} souscrit, ${reduction}% de réduction activée.` });
                    });
                });
            });
        });
    });
};

exports.getCurrent = (req, res) => {
    const userId = req.user.user_id;

    const sql = `
        SELECT type, start_date, status
        FROM subscriptions
        WHERE user_id = ? AND status = 'active'
    `;

    db.query(sql, [userId], (err, rows) => {
        if (err) {
            console.error("Erreur récupération abonnement :", err);
            return res.status(500).json({ error: "Erreur serveur" });
        }

        if (rows.length === 0) {
            return res.json({ active: false });
        }

        res.json({
            active: true,
            type: rows[0].type,
            start_date: rows[0].start_date
        });
    });
};
