const express = require('express');
const router = express.Router();
const stripe = require('stripe')(process.env.STRIPE_SECRET_KEY);
const auth = require('../middlewares/auth');
const db = require('../config/db').promise();


// Créer une session Stripe Identity
router.post('/identity/start', auth, async (req, res) => {
    try {
        const session = await stripe.identity.verificationSessions.create({
            type: 'document',
            metadata: {
                user_id: req.user.user_id
            },
            options: {
                document: {
                    require_matching_selfie: false
                }
            }
        });

        res.json({ url: session.url, session_id: session.id }); // envoie l'URL + session_id
    } catch (err) {
        console.error('[Stripe Identity Error]', err);
        res.status(500).json({ error: 'Erreur création session Stripe Identity' });
    }
});




// Vérifier le statut de la session (utilisé à /verification-finish)
router.get('/identity/status/:session_id', auth, async (req, res) => {
    try {
        const session = await stripe.identity.verificationSessions.retrieve(req.params.session_id);
        const status = session.status;

        if (status === 'verified') {
            const user_id = session.metadata.user_id;

            // ✅ Marquer identity_verified = 1
            await db.query(
                `UPDATE users SET identity_verified = 1 WHERE user_id = ?`,
                [user_id]
            );

            // ✅ Vérifie si domicile_verified est déjà OK → alors is_verified = 1
            await db.query(`
                UPDATE users
                SET is_verified = 1
                WHERE user_id = ?
                  AND identity_verified = 1
                  AND domicile_verified = 1
            `, [user_id]);
        }

        res.json({ status });
    } catch (err) {
        console.error('[Stripe Identity Status Error]', err);
        res.status(500).json({ error: 'Erreur récupération statut Stripe ou mise à jour DB' });
    }
});

module.exports = router;
