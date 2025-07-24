const db = require('../config/db');

exports.getMyTrips = (req, res) => {
    const courierId = req.user?.user_id;

    if (!courierId || isNaN(courierId)) {
        return res.status(400).json({ error: 'Utilisateur invalide' });
    }

    const page = parseInt(req.query.page) || 1;
    const limit = 2;
    const offset = (page - 1) * limit;

    // 1. Compter le total des trajets liés au livreur
    const countSql = `SELECT COUNT(*) AS total FROM deliveries WHERE courier_id = ?`;

    db.query(countSql, [courierId], (countErr, countRows) => {
        if (countErr) return res.status(500).json({ error: 'Erreur serveur (count)' });

        const total = countRows[0].total;
        const totalPages = Math.ceil(total / limit);

        // 2. Sélection paginée des trajets
        const sql = `
            SELECT 
                d.delivery_id,
                d.status AS delivery_status,
                d.departure_date,
                d.arrival_date,
                l.listing_id,
                l.annonce_title,
                l.description,
                l.departure_city,
                l.arrival_city,
                l.departure_address,
                l.delivery_address,
                l.price,
                l.type,
                l.status AS listing_status,
                l.details
            FROM deliveries d
            JOIN listings l ON d.listing_id = l.listing_id
            WHERE d.courier_id = ?
            ORDER BY d.departure_date DESC
            LIMIT ? OFFSET ?
        `;

        db.query(sql, [courierId, limit, offset], (err, rows) => {
            if (err) {
                console.error("Erreur getMyTrips:", err);
                return res.status(500).json({ error: 'Erreur serveur (query)' });
            }

            res.json({
                currentPage: page,
                totalPages,
                trips: rows
            });
        });
    });
};

