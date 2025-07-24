const db = require('../config/db');

// Récupère toutes les factures avec les noms des utilisateurs concernés
exports.getAllInvoices = (req, res) => {
    const sql = `
        SELECT i.*,
               u1.name AS emetteur_name,
               u2.name AS livreur_name
        FROM invoices i
                 LEFT JOIN users u1 ON i.user_id = u1.user_id
                 LEFT JOIN users u2 ON i.courier_id = u2.user_id
        ORDER BY i.invoice_id DESC
    `;

    db.query(sql, (err, rows) => {
        if (err) {
            console.error("Erreur récupération factures admin :", err);
            return res.status(500).json({ error: 'Erreur serveur' });
        }

        res.json({ invoices: rows });
    });
};

// Supprime plusieurs factures sélectionnées
exports.deleteInvoices = (req, res) => {
    const ids = req.body.ids;

    if (!Array.isArray(ids) || ids.length === 0) {
        return res.status(400).json({ error: 'Liste invalide ou vide' });
    }

    const placeholders = ids.map(() => '?').join(',');
    const sql = `DELETE FROM invoices WHERE invoice_id IN (${placeholders})`;

    db.query(sql, ids, (err) => {
        if (err) {
            console.error("Erreur suppression factures :", err);
            return res.status(500).json({ error: 'Erreur suppression' });
        }

        res.json({ success: true });
    });
};
