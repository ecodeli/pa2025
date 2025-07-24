const db = require("../config/db");

exports.bookService = async (req, res) => {
    const { listing_id, availability_id } = req.body;
    const client_id = req.user?.user_id;

    if (!listing_id || !availability_id || !client_id)
        return res.status(400).json({ message: "Champs manquants" });

    const connection = db.promise();

    try {
        // Vérifie que le créneau existe et qu’il n’est pas déjà réservé
        const [[slot]] = await connection.query(
            `SELECT provider_id, listing_id, reserved
       FROM availabilities
       WHERE id = ?`,
            [availability_id]
        );

        if (!slot)
            return res.status(404).json({ message: "Créneau introuvable" });

        if (slot.reserved)
            return res.status(409).json({ message: "Ce créneau est déjà réservé" });

        if (+listing_id !== +slot.listing_id)
            return res.status(400).json({ message: "Incohérence listing/créneau" });

        if (+client_id === +slot.provider_id)
            return res.status(403).json({ message: "Vous ne pouvez pas réserver votre propre service" });

        // Commencer une transaction
        await connection.beginTransaction();

        // Insérer la réservation dans la table `bookings`
        const [insert] = await connection.query(
            `INSERT INTO bookings
        (client_id, provider_id, listing_id, availability_id, status)
       VALUES (?, ?, ?, ?, ?)`,
            [client_id, slot.provider_id, listing_id, availability_id, 'pending']
        );

        // Marquer le créneau comme réservé
        await connection.query(
            `UPDATE availabilities SET reserved = 1 WHERE id = ?`,
            [availability_id]
        );

        await connection.commit();

        res.status(201).json({ booking_id: insert.insertId });
    } catch (err) {
        await connection.rollback();
        console.error("Erreur réservation :", err);
        res.status(500).json({ message: "Erreur serveur" });
    }
};
