// controllers/adminListingController.js

const db = require('../config/db');

/**
 * Récupère les annonces d'un commerçant (paginated).
 */
exports.getListingsByMerchant = (req, res) => {
    const merchantId = req.params.id;
    const page       = parseInt(req.query.page, 10)    || 1;
    const perPage    = parseInt(req.query.per_page, 10) || 8;
    const offset     = (page - 1) * perPage;

    // 1) total des annonces
    db.query(
        `SELECT COUNT(*) AS total FROM listings WHERE user_id = ?`,
        [merchantId],
        (err, cnt) => {
            if (err) return res.status(500).json({ error: 'Erreur count listings' });
            const total    = cnt[0].total;
            const lastPage = Math.ceil(total / perPage);

            // 2) récupération des lignes
            const sql = `
        SELECT 
          listing_id,
          annonce_title,
          departure_city,
          arrival_city,
          price,
          status,
          is_archived
        FROM listings
        WHERE user_id = ?
        ORDER BY listing_id DESC
        LIMIT ?, ?
      `;
            db.query(sql, [merchantId, offset, perPage], (err2, rows) => {
                if (err2) return res.status(500).json({ error: 'Erreur fetch listings' });
                res.json({
                    data: rows,
                    meta: {
                        total,
                        per_page: perPage,
                        current_page: page,
                        last_page: lastPage
                    }
                });
            });
        }
    );
};

/**
 * Archive ou restaure une annonce.
 */
exports.toggleArchiveListing = (req, res) => {
    const listingId = req.params.id;
    const { archive } = req.body; // true = archiver, false = restaurer

    db.query(
        `UPDATE listings SET is_archived = ? WHERE listing_id = ?`,
        [archive ? 1 : 0, listingId],
        err => {
            if (err) return res.status(500).json({ error: 'Erreur archivage listing' });
            res.json({ success: true, is_archived: !!archive });
        }
    );
};

/**
 * Supprime une annonce.
 */
exports.deleteListing = (req, res) => {
    const listingId = req.params.id;
    db.query(
        `DELETE FROM listings WHERE listing_id = ?`,
        [listingId],
        err => {
            if (err) return res.status(500).json({ error: 'Erreur suppression listing' });
            res.json({ success: true });
        }
    );
};
