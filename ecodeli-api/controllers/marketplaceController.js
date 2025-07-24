const db = require('../config/db');

exports.getAvailableServices = (req, res) => {
    const { lat, lng, city, type, mainType, providerType, radius } = req.query;

    const searchRadius = parseFloat(radius) || 999999;
    const values = [lat, lng, lat, searchRadius];

    let baseQuery = `
        SELECT * FROM (
                          SELECT
                              l.*,
                              (
                                  SELECT photo_path
                                  FROM listing_photos
                                  WHERE listing_id = l.listing_id
                                  LIMIT 1
                      ) AS photo_path,
                      (
                          6371 * acos(
                        cos(radians(?)) * cos(radians(l.departure_lat)) *
                        cos(radians(l.departure_lng) - radians(?)) +
                        sin(radians(?)) * sin(radians(l.departure_lat))
                    )
                ) AS distance,
                      u.type AS user_type
            FROM listings l
            LEFT JOIN users u ON l.user_id = u.user_id
        WHERE l.status = 'pending'
          AND l.is_archived = 0
          AND l.departure_lat IS NOT NULL
          AND l.departure_lng IS NOT NULL
          AND (
            (l.type = 'colis' AND l.listing_id NOT IN (SELECT listing_id FROM delivery_lines))
           OR
            (l.type = 'service' AND l.listing_id NOT IN (SELECT prestation_id FROM reservations))
            )
            ) AS base
        WHERE distance <= ?
    `;

    if (mainType && mainType !== "all") {
        baseQuery += " AND base.type = ?";
        values.push(mainType);
    }

    if (city) {
        baseQuery += " AND (base.departure_city LIKE ? OR base.arrival_city LIKE ?)";
        values.push(`%${city}%`, `%${city}%`);
    }

    if (type) {
        baseQuery += " AND base.category = ?";
        values.push(type);
    }

    if (providerType === 'client') {
        baseQuery += " AND base.user_type = 'client'";
    } else if (providerType === 'service_provider') {
        baseQuery += " AND base.user_type = 'service_provider'";
    }

    db.query(baseQuery, values, (err, results) => {
        if (err) {
            console.error("Erreur SQL marketplace:", err);
            return res.status(500).json({ message: "Erreur serveur", error: err });
        }

        res.json(results);
    });
};

exports.getWarehouseDeliveries = async (req, res) => {
    try {
        const sql = `
            SELECT 
                l.listing_id, l.annonce_title, l.type, l.photo_path,
                w.name AS warehouse_name, w.city AS warehouse_city,
                dl.line_id, dl.current_lat AS departure_lat, dl.current_lng AS departure_lng
            FROM delivery_lines dl
            JOIN listings l ON dl.listing_id = l.listing_id
            JOIN warehouses w ON dl.warehouse_id = w.warehouse_id
            WHERE dl.status = 'en_attente'
        `;

        const [rows] = await db.promise().query(sql);
        res.json(rows);
    } catch (err) {
        console.error("Erreur récupération segments entrepôt :", err);
        res.status(500).json({ error: "Erreur serveur" });
    }
};