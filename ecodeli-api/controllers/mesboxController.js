const db = require('../config/db');

// GET /api/warehouse-boxes — récupère les boxes de l’utilisateur connecté
exports.getUserBoxes = (req, res) => {
    const userId = req.user.user_id;

    const sql = `
        SELECT b.box_id, b.start_date, b.end_date, b.status,
               w.city, w.address
        FROM storage_boxes b
        JOIN warehouses w ON b.warehouse_id = w.warehouse_id
        WHERE b.user_id = ?
        ORDER BY b.start_date DESC
    `;

    db.query(sql, [userId], (err, results) => {
        if (err) {
            console.error("Erreur SQL :", err);
            return res.status(500).json({ error: "Erreur serveur", details: err });
        }

        return res.json(results);
    });
};
