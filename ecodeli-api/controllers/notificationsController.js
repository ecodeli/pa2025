const db = require('../config/db');

// Récupérer les notifications d'un user
exports.getUserNotifications = (req, res) => {
    const user_id = req.user.user_id;

    const sql = `
    SELECT notification_id, message, send_date, is_read
    FROM notifications
    WHERE user_id = ?
    ORDER BY send_date DESC
  `;

    db.query(sql, [user_id], (err, results) => {
        if (err) {
            console.error("Erreur getUserNotifications :", err);
            return res.status(500).json({ error: "Erreur serveur" });
        }

        res.json({ notifications: results });
    });
};
