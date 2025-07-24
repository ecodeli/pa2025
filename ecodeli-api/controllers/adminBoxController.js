const db = require('../config/db');

// Get all boxes by warehouse
exports.getBoxesByWarehouse = (req, res) => {
    const warehouseId = req.params.id;

    const sql = `
        SELECT sb.box_id, sb.start_date, sb.end_date, sb.status,
               u.name AS user_name
        FROM storage_boxes sb
        LEFT JOIN users u ON sb.user_id = u.user_id
        WHERE sb.warehouse_id = ?
        ORDER BY sb.box_id ASC
    `;

    db.query(sql, [warehouseId], (err, rows) => {
        if (err) return res.status(500).json({ error: "Erreur BDD", details: err });
        return res.json(rows);
    });
};


// Libérer un box
exports.freeBox = (req, res) => {
    const { box_id } = req.body;

    const sql = `
        UPDATE storage_boxes
        SET status = 'free', end_date = NULL, user_id = NULL
        WHERE box_id = ?
    `;

    db.query(sql, [box_id], (err) => {
        if (err) return res.status(500).json({ error: "Erreur libération", details: err });
        return res.json({ success: true, message: `Box ${box_id} libéré.` });
    });
};

exports.getAllWarehouses = (req, res) => {
    const sql = `
        SELECT warehouse_id, city, address
        FROM warehouses
        ORDER BY city ASC
    `;

    db.query(sql, (err, rows) => {
        if (err) return res.status(500).json({ error: "Erreur lors de la récupération des entrepôts" });
        return res.json(rows);
    });
};

