const fs = require('fs');
const path = require('path');
const db = require('../config/db');

exports.updateAvatar = (req, res) => {
    const user_id = req.user.user_id;

    if (!req.file) {
        return res.status(400).json({ message: "Aucun fichier envoyé." });
    }

    const newAvatarUrl = '/uploads/avatars/' + req.file.filename;

    const getOldAvatarSql = "SELECT avatar_url FROM users WHERE user_id = ?";
    db.query(getOldAvatarSql, [user_id], (err, results) => {
        if (err) return res.status(500).json({ message: "Erreur récupération avatar", error: err });

        const oldAvatarPath = results[0]?.avatar_url;

        const updateSql = "UPDATE users SET avatar_url = ? WHERE user_id = ?";
        db.query(updateSql, [newAvatarUrl, user_id], (err) => {
            if (err) return res.status(500).json({ message: "Erreur mise à jour", error: err });

            // Suppression de l’ancien fichier
            if (oldAvatarPath && oldAvatarPath.startsWith('/uploads/avatars/') && oldAvatarPath !== newAvatarUrl) {
                const filePath = path.join(__dirname, '..', oldAvatarPath.startsWith('/') ? oldAvatarPath.slice(1) : oldAvatarPath);
                console.log("Suppression de :", filePath);
                fs.unlink(filePath, (err) => {
                    if (err) console.warn("Erreur suppression ancienne photo :", err.message);
                    else console.log("Ancien avatar supprimé :", filePath);
                });
            }

            res.json({ message: "Avatar mis à jour", avatar_url: newAvatarUrl });
        });
    });
};
