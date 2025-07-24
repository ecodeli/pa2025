const db = require('../config/db');

exports.toggleLike = (req, res) => {
    const { listing_id } = req.body;
    const user_id = req.user.user_id;

    if (!listing_id) return res.status(400).json({ message: "listing_id requis" });

    const checkSql = "SELECT * FROM liked WHERE user_id = ? AND listing_id = ?";
    db.query(checkSql, [user_id, listing_id], (err, rows) => {
        if (err) return res.status(500).json({ message: "Erreur BDD", error: err });

        if (rows.length > 0) {
            const deleteSql = "DELETE FROM liked WHERE user_id = ? AND listing_id = ?";
            db.query(deleteSql, [user_id, listing_id], (err) => {
                if (err) return res.status(500).json({ message: "Erreur delete", error: err });
                return res.json({ liked: false });
            });
        } else {
            const insertSql = "INSERT INTO liked (user_id, listing_id) VALUES (?, ?)";
            db.query(insertSql, [user_id, listing_id], (err) => {
                if (err) return res.status(500).json({ message: "Erreur insert", error: err });
                return res.json({ liked: true });
            });
        }
    });
};


exports.getLikedAds = (req, res) => {
    const user_id = req.user.user_id;

    const sql = `
        SELECT listing_id FROM liked
        WHERE user_id = ?
    `;

    db.query(sql, [user_id], (err, results) => {
        if (err) return res.status(500).json({ message: "Erreur lors de la récupération des likes", error: err });

        const likedIds = results.map(row => row.listing_id);
        res.json({ liked: likedIds });
    });
};

exports.checkLikeStatus = async (req, res) => {
    const userId = req.user.user_id;
    const listingId = req.params.id;

    try {
        const [rows] = await db.promise().query(
            "SELECT * FROM liked WHERE user_id = ? AND listing_id = ?",
            [userId, listingId]
        );

        res.json({ liked: rows.length > 0 });
    } catch (error) {
        console.error("Erreur lors de la vérification du like :", error);
        res.status(500).json({ error: "Erreur serveur" });
    }
};
