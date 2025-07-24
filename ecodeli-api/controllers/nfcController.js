const db = require('../config/db');

exports.updateTag = (req, res) => {
    const { userId, role, nfcTag } = req.body;

    if (!userId || !nfcTag) {
        return res.status(400).json({ message: "Paramètres manquants" });
    }

    const sql = `UPDATE users SET nfc_tag = ? WHERE user_id = ?`;

    db.query(sql, [nfcTag, userId], (err, result) => {
        if (err) {
            console.error("Erreur update NFC tag:", err);
            return res.status(500).json({ message: "Erreur serveur" });
        }

        res.json({ message: "Tag NFC mis à jour avec succès" });
    });
};

exports.checkTag = (req, res) => {
    const tagId = req.query.tagId;

    if (!tagId) {
        return res.status(400).json({ message: "Tag manquant" });
    }

    const sql = `SELECT name FROM users WHERE nfc_tag = ?`;

    db.query(sql, [tagId], (err, results) => {
        if (err) {
            console.error("Erreur check tag:", err);
            return res.status(500).json({ message: "Erreur serveur" });
        }

        if (results.length === 0) {
            return res.json({ valid: false });
        }

        res.json({ valid: true, name: results[0].name });
    });
};
