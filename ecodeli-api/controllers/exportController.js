const db = require("../config/db");

exports.getAllUsersWithFactures = async (req, res) => {
    try {
        const [rows] = await db.promise().query(`
            SELECT 
                u.user_id,
                u.name,
                u.email,
                u.type,
                COUNT(i.invoice_id) AS nb_factures,
                COALESCE(SUM(i.amount), 0) AS total_facture,
                MAX(i.invoice_date) AS derniere_facture
            FROM users u
            LEFT JOIN invoices i ON i.user_id = u.user_id
            WHERE u.type != 'admin'
            GROUP BY u.user_id, u.name, u.email, u.type
            ORDER BY total_facture DESC;
        `);

        return res.json(rows);
    } catch (err) {
        console.error("Erreur export comptes utilisateurs :", err);
        return res.status(500).json({ error: "Erreur serveur" });
    }
};

exports.getCAParJour = async (req, res) => {
    try {
        const [rows] = await db.promise().query(`
            SELECT 
                DATE(invoice_date) AS date,
                SUM(amount) AS total
            FROM invoices
            GROUP BY DATE(invoice_date)
            ORDER BY date;
        `);

        return res.json(rows);
    } catch (err) {
        console.error("Erreur export CA par jour :", err);
        return res.status(500).json({ error: "Erreur serveur" });
    }
};

exports.getDetailedDeliveries = async (req, res) => {
    try {
        const [rows] = await db.promise().query(`
            SELECT
                d.delivery_id,
                d.status,
                dr.start_city AS departure_city,
                dr.end_city AS arrival_city,
                d.departure_date,
                d.arrival_date,
                l.description AS content,
                lo.poids
            FROM deliveries d
                     LEFT JOIN delivery_lines dl ON dl.delivery_id = d.delivery_id
                     LEFT JOIN delivery_routes dr ON dr.route_id = dl.route_id
                     LEFT JOIN listings l ON l.listing_id = dl.listing_id
                     LEFT JOIN listing_objects lo ON lo.listing_id = l.listing_id
            ORDER BY dr.departure_date DESC;
        `);
        res.json(rows);
    } catch (error) {
        console.error("Erreur récupération livraisons détaillées :", error);
        res.status(500).json({ error: "Erreur serveur" });
    }
};

exports.getServicesDetails = async (req, res) => {
    try {
        const [rows] = await db.promise().query(`
            SELECT 
                l.listing_id,
                l.description,
                l.departure_city,
                l.arrival_city,
                l.price,
                l.deadline_date,
                a.date AS availability_date,
                a.start_time,
                a.end_time,
                a.reserved,
                b.status AS booking_status,
                b.booked_at
            FROM listings l
            LEFT JOIN availabilities a ON a.listing_id = l.listing_id
            LEFT JOIN bookings b ON b.listing_id = l.listing_id
            WHERE l.type = 'service';
        `);
        return res.json(rows);
    } catch (err) {
        console.error("Erreur export services détaillés :", err);
        return res.status(500).json({ error: "Erreur serveur" });
    }
};


