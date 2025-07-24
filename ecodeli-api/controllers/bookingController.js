const db = require('../config/db');

exports.getBookingDetails = async (req, res) => {
    const bookingId = req.params.bookingId;

    try {
        const [rows] = await db.promise().query(`
            SELECT
                b.booking_id,
                b.status,
                b.booked_at,
                a.date,
                a.start_time,
                a.end_time,
                l.annonce_title,
                l.details,
                l.category,
                l.price,
                l.service_radius,
                u.name AS provider_name,
                u.avatar_url,
                l.departure_city AS city,
                l.departure_lat,
                l.departure_lng,
                l.arrival_lat,
                l.arrival_lng,
                l.type
            FROM bookings b
                     JOIN availabilities a ON b.availability_id = a.id
                     JOIN listings l       ON b.listing_id = l.listing_id
                     JOIN users u          ON b.provider_id = u.user_id
            WHERE b.booking_id = ?
        `, [bookingId]);


        if (rows.length === 0) {
            return res.status(404).json({ message: "Réservation introuvable" });
        }

        res.json(rows[0]);
    } catch (err) {
        console.error("Erreur getBookingDetails:", err);
        res.status(500).json({ message: "Erreur serveur" });
    }

};


exports.getBookingsByClient = async (req, res) => {
    const userId = req.user?.user_id;

    if (!userId) return res.status(401).json({ message: "Non autorisé" });

    try {
        const [rows] = await db.promise().query(`
            SELECT 
                b.booking_id,
                b.status,
                b.booked_at,
                l.annonce_title,
                b.listing_id
            FROM bookings b
            JOIN listings l ON b.listing_id = l.listing_id
            WHERE b.client_id = ?
        `, [userId]);

        res.json(rows);
    } catch (err) {
        console.error("Erreur getBookingsByClient:", err);
        res.status(500).json({ message: "Erreur serveur" });
    }
};

exports.cancelBooking = async (req, res) => {
    const bookingId = req.params.bookingId;
    const userId = req.user.user_id;

    try {
        const [rows] = await db.promise().query(`SELECT * FROM bookings WHERE booking_id = ?`, [bookingId]);
        if (!rows.length) return res.status(404).json({ message: "Réservation introuvable" });
        const booking = rows[0];

        if (booking.client_id !== userId)
            return res.status(403).json({ message: "Non autorisé à annuler cette réservation." });

        await db.promise().query(`UPDATE bookings SET status = 'annulée' WHERE booking_id = ?`, [bookingId]);
        res.json({ message: "Réservation annulée." });
    } catch (err) {
        console.error("Erreur annulation réservation:", err);
        res.status(500).json({ message: "Erreur serveur" });
    }
};

