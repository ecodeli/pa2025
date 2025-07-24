const db = require('../config/db');

exports.getMyInvoices = (req, res) => {
    const userId = req.user?.user_id;
    if (!userId) return res.status(401).json({ error: "Utilisateur non authentifié" });

    const page = parseInt(req.query.page) || 1;
    const limit = 5;
    const offset = (page - 1) * limit;

    // Compter le total des factures
    const countSql = `
        SELECT COUNT(*) AS total 
        FROM invoices 
        WHERE user_id = ? OR courier_id = ?
    `;

    db.query(countSql, [userId, userId], (errCount, countRows) => {
        if (errCount) {
            console.error("Erreur count invoices :", errCount);
            return res.status(500).json({ error: "Erreur serveur (count)" });
        }

        const total = countRows[0].total;
        const totalPages = Math.ceil(total / limit);

        const sql = `
            SELECT invoice_id, amount, invoice_file, invoice_date
            FROM invoices
            WHERE user_id = ? OR courier_id = ?
            ORDER BY invoice_date DESC
            LIMIT ? OFFSET ?
        `;

        db.query(sql, [userId, userId, limit, offset], (err, rows) => {
            if (err) {
                console.error("Erreur invoices paginées :", err);
                return res.status(500).json({ error: "Erreur serveur (liste)" });
            }

            res.json({
                currentPage: page,
                totalPages,
                invoices: rows
            });
        });
    });
};
