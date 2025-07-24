const db = require("../config/db");

exports.getUserAvailabilities = async (req, res) => {
    const userId = req.user.user_id;

    try {
        const [rows] = await db.promise().query(
            `SELECT
                 a.id, a.date, a.start_time, a.end_time,
                 l.annonce_title,
                 b.booking_id, b.client_id,
                 b.status AS booking_status,
                 u.name AS client_name,
                 u.avatar_url AS client_avatar
             FROM availabilities a
                      LEFT JOIN bookings b ON a.id = b.availability_id
                      LEFT JOIN users u ON u.user_id = b.client_id
                      JOIN listings l ON l.listing_id = a.listing_id
             WHERE a.provider_id = ?`,
            [userId]
        );

        res.json(rows);
    } catch (err) {
        console.error("Erreur récupération des disponibilités :", err);
        res.status(500).json({ error: "Erreur serveur" });
    }
};


exports.createAvailability = async (req, res) => {
    const { date, start_time, end_time, listing_id } = req.body;
    const provider_id = req.user?.user_id;

    if (!date || !start_time || !end_time || !listing_id || !provider_id) {
        return res.status(400).json({ error: "Champs manquants" });
    }

    // Ne pas corriger manuellement la date — elle est déjà correcte
    const correctedDate = date;

    try {
        const [result] = await db.promise().query(
            `INSERT INTO availabilities (provider_id, date, start_time, end_time, listing_id)
             VALUES (?, ?, ?, ?, ?)`,
            [provider_id, correctedDate, start_time, end_time, listing_id]
        );
        res.status(201).json({ success: true, id: result.insertId });
    } catch (err) {
        console.error("Erreur ajout :", err);
        res.status(500).json({ error: "Erreur serveur" });
    }
};




exports.getListingAvailabilities = async (req, res) => {
    const { listingId } = req.params;

    try {
        const [rows] = await db.promise().query(
            `SELECT id, date, start_time, end_time
       FROM   availabilities
       WHERE  listing_id = ?
         AND  date >= CURDATE()
       ORDER BY date, start_time`,
            [listingId]
        );
        res.json(rows);
    } catch (err) {
        console.error('Erreur lecture dispo par annonce :', err);
        res.status(500).json({ error: 'Erreur serveur' });
    }
};

exports.deleteAvailability = async (req, res) => {
    const { id } = req.params;

    try {
        const [result] = await db.promise().query(
            'DELETE FROM availabilities WHERE id = ?',
            [id]
        );

        if (result.affectedRows === 0) {
            return res.status(404).json({ message: "Créneau non trouvé" });
        }

        res.json({ message: "Créneau supprimé" });
    } catch (err) {
        console.error("Erreur suppression disponibilité :", err);
        res.status(500).json({ message: "Erreur serveur" });
    }
};
