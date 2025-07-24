const db = require('../config/db');

exports.getAllUsersWithStats = (req, res) => {
    const page    = parseInt(req.query.page, 10)    || 1;
    const perPage = parseInt(req.query.per_page, 10) || 8;
    const offset  = (page - 1) * perPage;

    // 1) total
    db.query(`SELECT COUNT(*) AS total FROM users`, (err, countResults) => {
        if (err) {
            console.error("SQL Error (count):", err);
            return res.status(500).json({ error: 'Erreur serveur' });
        }
        const total    = countResults[0].total;
        const lastPage = Math.ceil(total / perPage);

        // 2) page
        const dataSql = `
      SELECT 
        u.user_id,
        u.name,
        u.email,
        u.type,
        COUNT(DISTINCT a.listing_id) AS annonces_count,
        COUNT(DISTINCT i.invoice_id)  AS documents_count,
        u.is_banned
      FROM users u
      LEFT JOIN listings a  ON a.user_id   = u.user_id
      LEFT JOIN invoices i  ON i.user_id   = u.user_id
      GROUP BY u.user_id, u.name, u.email, u.type, u.is_banned
      ORDER BY u.user_id
      LIMIT ?, ?
    `;
        db.query(dataSql, [offset, perPage], (err2, results) => {
            if (err2) {
                console.error("SQL Error (data):", err2);
                return res.status(500).json({ error: 'Erreur serveur' });
            }
            res.json({
                data: results,
                meta: {
                    total,
                    per_page: perPage,
                    current_page: page,
                    last_page: lastPage
                }
            });
        });
    });
};

exports.changeUserType = (req, res) => {
    const { id }      = req.params;
    const { newType } = req.body;
    db.query(
        `UPDATE users SET type = ? WHERE user_id = ?`,
        [newType, id],
        err => {
            if (err) return res.status(500).json({ error: 'Erreur mise à jour type' });
            res.json({ success: true });
        }
    );
};

exports.toggleBanUser = (req, res) => {
    const { id }  = req.params;
    const { ban } = req.body; // true ou false
    db.query(
        `UPDATE users SET is_banned = ? WHERE user_id = ?`,
        [ban ? 1 : 0, id],
        err => {
            if (err) return res.status(500).json({ error: 'Erreur bannissement' });
            res.json({ success: true, is_banned: ban });
        }
    );
};

exports.deleteUser = (req, res) => {
    const { id } = req.params;
    db.query(
        `DELETE FROM users WHERE user_id = ?`,
        [id],
        err => {
            if (err) return res.status(500).json({ error: 'Erreur suppression' });
            res.json({ success: true });
        }
    );
};
