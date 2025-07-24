const db = require('../config/db');

exports.getUserDocuments = (req, res) => {
    const userId = req.user.user_id;
    const page = parseInt(req.query.page) || 1;
    const limit = 5;
    const offset = (page - 1) * limit;

    const countSql = `SELECT COUNT(*) AS total FROM documents WHERE user_id = ?`;
    const dataSql = `
        SELECT * FROM documents
        WHERE user_id = ?
        ORDER BY upload_date DESC
        LIMIT ? OFFSET ?
    `;

    db.query(countSql, [userId], (err, countRows) => {
        if (err) {
            console.error("Erreur count documents :", err);
            return res.status(500).json({ error: 'Erreur serveur' });
        }

        const total = countRows[0].total;
        const totalPages = Math.ceil(total / limit);

        db.query(dataSql, [userId, limit, offset], (err2, rows) => {
            if (err2) {
                console.error("Erreur récupération documents :", err2);
                return res.status(500).json({ error: 'Erreur serveur' });
            }

            res.json({
                documents: rows,
                currentPage: page,
                totalPages
            });
        });
    });
};


exports.deleteDocument = (req, res) => {
    const userId = req.user.user_id;
    const documentId = req.body.document_id;

    if (!documentId) {
        return res.status(400).json({ error: 'ID document manquant' });
    }

    const sql = `DELETE FROM documents WHERE document_id = ? AND user_id = ?`;
    db.query(sql, [documentId, userId], (err) => {
        if (err) {
            console.error("Erreur deleteDocument :", err);
            return res.status(500).json({ error: 'Erreur suppression' });
        }
        res.json({ success: true });
    });
};
