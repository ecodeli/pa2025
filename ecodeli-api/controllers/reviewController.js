const db = require('../config/db');

exports.getMyReviews = (req, res) => {
    const userId = req.user.user_id;

    const sql = `
        SELECT r.note, r.commentaire, r.created_at, l.annonce_title
        FROM reviews r
        JOIN listings l ON r.listing_id = l.listing_id
        WHERE r.courier_id = ?
        ORDER BY r.created_at DESC
    `;

    db.query(sql, [userId], (err, results) => {
        if (err) {
            console.error("Erreur getMyReviews :", err);
            return res.status(500).json({ error: "Erreur serveur." });
        }

        res.json(results);
    });
};

exports.getAllReviews = (req, res) => {
    const page = parseInt(req.query.page) || 1;
    const limit = 5;
    const offset = (page - 1) * limit;

    // Compter le total
    const countSql = `SELECT COUNT(*) AS total FROM reviews`;

    db.query(countSql, (countErr, countRows) => {
        if (countErr) return res.status(500).json({ error: 'Erreur serveur (count)' });

        const total = countRows[0].total;
        const totalPages = Math.ceil(total / limit);

        const sql = `
            SELECT r.review_id, r.note, r.commentaire, r.created_at,
                   ue.user_id AS emetteur_id, ue.name AS emetteur_name,
                   ur.user_id AS receveur_id, ur.name AS receveur_name
            FROM reviews r
            JOIN users ue ON r.user_id = ue.user_id
            JOIN users ur ON r.courier_id = ur.user_id
            ORDER BY r.created_at DESC
            LIMIT ? OFFSET ?
        `;

        db.query(sql, [limit, offset], (err, results) => {
            if (err) {
                console.error("Erreur getAllReviews :", err);
                return res.status(500).json({ error: "Erreur serveur (query)" });
            }

            res.json({
                currentPage: page,
                totalPages,
                reviews: results
            });
        });
    });
};

exports.deleteReview = (req, res) => {
    const reviewId = req.params.id;

    const sql = `DELETE FROM reviews WHERE review_id = ?`;
    db.query(sql, [reviewId], (err, result) => {
        if (err) {
            console.error("Erreur deleteReview :", err);
            return res.status(500).json({ error: "Erreur serveur." });
        }

        res.json({ success: true, message: "Avis supprimé." });
    });
};
