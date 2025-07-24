const db = require("../config/db");

exports.createDelivery = async (req, res) => {
    const { listing_id } = req.body;
    const courier_id = req.user?.user_id;

    if (!listing_id || !courier_id) {
        return res.status(400).json({ error: "Champs manquants" });
    }

    try {
        const now = new Date();

        //Vérifie que le livreur est vérifié
        const [courierRows] = await db.promise().query(
            `SELECT is_verified FROM users WHERE user_id = ? AND type = 'courier'`,
            [courier_id]
        );

        if (courierRows.length === 0) {
            return res.status(403).json({ error: "Utilisateur introuvable ou non livreur" });
        }

        if (!courierRows[0].is_verified) {
            return res.status(403).json({ error: "Votre compte n'est pas vérifié. Vous ne pouvez pas réserver un trajet." });
        }

        // Récupération de l'annonce
        const [listingRows] = await db.promise().query(
            `SELECT departure_city, arrival_city, departure_lat, departure_lng, arrival_lat, arrival_lng, verification_code, user_id, price, delivery_fees
             FROM listings WHERE listing_id = ?`,
            [listing_id]
        );

        if (listingRows.length === 0) {
            return res.status(404).json({ error: "Annonce introuvable" });
        }

        const ad = listingRows[0];
        const client_id = ad.user_id;
        const total = parseFloat(ad.price) + parseFloat(ad.delivery_fees || 0);

        // Vérifie le wallet du client
        const [walletRows] = await db.promise().query(
            `SELECT wallet_id, balance FROM wallet WHERE user_id = ?`,
            [client_id]
        );

        if (walletRows.length === 0) {
            return res.status(404).json({ error: "Wallet non trouvé pour l'utilisateur" });
        }

        const wallet = walletRows[0];
        if (parseFloat(wallet.balance) < total) {
            return res.status(400).json({ error: "Solde insuffisant dans le wallet" });
        }

        //Débit du wallet
        await db.promise().query(
            `UPDATE wallet SET balance = balance - ? WHERE wallet_id = ?`,
            [total, wallet.wallet_id]
        );

        await db.promise().query(
            `INSERT INTO wallet_transactions (wallet_id, amount, type, description, sender_id, receiver_id)
             VALUES (?, ?, 'debit', 'Paiement de la livraison', ?, NULL)`,
            [wallet.wallet_id, total, client_id, courier_id]
        );

        //Création du trajet
        const [routeResult] = await db.promise().query(
            `INSERT INTO delivery_routes (courier_id, start_city, end_city, start_lat, start_lng, end_lat, end_lng, departure_date) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
            [
                courier_id,
                ad.departure_city,
                ad.arrival_city,
                ad.departure_lat,
                ad.departure_lng,
                ad.arrival_lat,
                ad.arrival_lng,
                now
            ]
        );

        const route_id = routeResult.insertId;

        //Liaison ligne de livraison
        await db.promise().query(
            `INSERT INTO delivery_lines (route_id, listing_id) VALUES (?, ?)`,
            [route_id, listing_id]
        );

        // Création livraison
        const [deliveryResult] = await db.promise().query(
            `INSERT INTO deliveries (courier_id, listing_id, departure_date, status, verification_code) VALUES (?, ?, ?, ?, ?)`,
            [courier_id, listing_id, now, "in_progress", ad.verification_code]
        );

        // Notification client
        const [courierNameRows] = await db.promise().query(
            `SELECT name FROM users WHERE user_id = ?`,
            [courier_id]
        );

        const courierName = courierNameRows.length > 0
            ? `${courierNameRows[0].name} `
            : "un livreur";

        const notifMessage = `Votre livraison est prise en charge par ${courierName}`;

        await db.promise().query(
            `INSERT INTO notifications (message, user_id) VALUES (?, ?)`,
            [notifMessage, client_id]
        );

        return res.status(201).json({ delivery_id: deliveryResult.insertId });

    } catch (err) {
        console.error("Erreur création livraison :", err);
        return res.status(500).json({ error: "Erreur serveur" });
    }
};



exports.getRoutesByUser = async (req, res) => {
    const courier_id = req.user?.user_id;

    if (!courier_id) {
        return res.status(401).json({ error: "Utilisateur non authentifié" });
    }

    try {
        const [rows] = await db.promise().query(
            `SELECT route_id, start_city, end_city, departure_date
             FROM delivery_routes
             WHERE courier_id = ?
             ORDER BY departure_date DESC`,
            [courier_id]
        );

        return res.json({ routes: rows });

    } catch (err) {
        console.error("Erreur chargement trajets :", err);
        return res.status(500).json({ error: "Erreur serveur" });
    }
};

exports.addToRoute = async (req, res) => {
    const { route_id, listing_id, custom_start_address, warehouse_id } = req.body;

    if (!route_id || !listing_id) {
        return res.status(400).json({ error: "Champs manquants" });
    }

    try {
        // Vérifie qu'il n'existe pas déjà une ligne pour ce route_id + listing_id
        const [existing] = await db.promise().query(
            `SELECT * FROM delivery_lines WHERE route_id = ? AND listing_id = ?`,
            [route_id, listing_id]
        );

        if (existing.length > 0) {
            return res.status(400).json({ error: "Cette annonce a déjà été réservée pour ce trajet." });
        }

        const [insertResult] = await db.promise().query(
            `INSERT INTO delivery_lines (route_id, listing_id, custom_start_address, warehouse_id)
             VALUES (?, ?, ?, ?)`,
            [route_id, listing_id, custom_start_address || null, warehouse_id || null]
        );

        const delivery_line_id = insertResult.insertId;

        await db.promise().query(
            `INSERT INTO delivery_status_history (delivery_line_id, status, location)
             VALUES (?, 'pris_en_charge', ?)`,
            [delivery_line_id, custom_start_address || 'Adresse personnalisée']
        );

        if (custom_start_address) {
            await db.promise().query(
                `UPDATE listings SET departure_city = ? WHERE listing_id = ?`,
                [custom_start_address, listing_id]
            );
        }

        return res.status(201).json({ success: true, delivery_line_id });

    } catch (err) {
        console.error("Erreur ajout à un trajet :", err);
        return res.status(500).json({ error: "Erreur serveur" });
    }
};

exports.getDeliverySummary = async (req, res) => {
    const id = req.params.id;

    try {
        const [fullDelivery] = await db.promise().query(
            `SELECT d.delivery_id, d.departure_date, dl.route_id, r.start_city, r.end_city
             FROM deliveries d
             JOIN delivery_lines dl ON dl.listing_id = d.listing_id
             JOIN delivery_routes r ON dl.route_id = r.route_id
             WHERE d.delivery_id = ?`,
            [id]
        );

        if (fullDelivery.length > 0) {
            const d = fullDelivery[0];
            return res.json({
                type: "full",
                route: {
                    start_city: d.start_city,
                    end_city: d.end_city,
                    departure_date: d.departure_date
                },
                custom_start_address: null,
                warehouse: null
            });
        }

        const [partial] = await db.promise().query(
            `SELECT dl.*, r.start_city, r.end_city, r.departure_date,
                    w.name as warehouse_name, w.city as warehouse_city
             FROM delivery_lines dl
             JOIN delivery_routes r ON dl.route_id = r.route_id
             LEFT JOIN warehouses w ON dl.warehouse_id = w.warehouse_id
             WHERE dl.route_id = ?
             ORDER BY dl.line_id DESC
             LIMIT 1`,
            [id]
        );

        if (partial.length === 0) {
            return res.status(404).json({ message: "Aucune livraison trouvée." });
        }

        const p = partial[0];
        return res.json({
            type: "partial",
            route: {
                start_city: p.start_city,
                end_city: p.end_city,
                departure_date: p.departure_date
            },
            custom_start_address: p.custom_start_address,
            warehouse: p.warehouse_name ? {
                name: p.warehouse_name,
                city: p.warehouse_city
            } : null
        });

    } catch (err) {
        console.error("Erreur récupération résumé livraison :", err);
        return res.status(500).json({ message: "Erreur serveur" });
    }
};


exports.checkIfReserved = async (req, res) => {
    const user_id = req.user?.user_id;
    const listing_id = req.params.listing_id;

    try {
        // Vérifie dans delivery_lines
        const [lines] = await db.promise().query(
            `SELECT * FROM delivery_lines dl
             JOIN delivery_routes dr ON dl.route_id = dr.route_id
             WHERE dl.listing_id = ? AND dr.courier_id = ?`,
            [listing_id, user_id]
        );

        return res.json({ reserved: lines.length > 0 });
    } catch (err) {
        console.error("Erreur check réservation :", err);
        return res.status(500).json({ error: "Erreur serveur" });
    }
};

exports.declareRoute = async (req, res) => {
    const user_id = req.user?.user_id;
    const { start_city, end_city, departure_date } = req.body;

    if (!user_id || !start_city || !end_city || !departure_date) {
        return res.status(400).json({ error: "Champs manquants" });
    }

    try {
        const [result] = await db.promise().query(
            `INSERT INTO delivery_routes (courier_id, start_city, end_city, departure_date)
             VALUES (?, ?, ?, ?)`,
            [user_id, start_city, end_city, departure_date]
        );

        return res.status(201).json({ success: true, route_id: result.insertId });
    } catch (err) {
        console.error("Erreur déclaration trajet :", err);
        return res.status(500).json({ error: "Erreur serveur" });
    }
};

exports.getActiveSegments = async (req, res) => {
    const courier_id = req.user?.user_id;

    try {
        const [rows] = await db.promise().query(
            `SELECT
                 ds.line_id AS segment_id,
                 ds.route_id,
                 ds.listing_id,
                 ds.status,
                 ds.latest_step,
                 l.annonce_title,
                 l.departure_city,
                 l.departure_address,
                 l.departure_lat,
                 l.departure_lng,
                 l.arrival_city,
                 l.delivery_address,
                 l.arrival_lat,
                 l.arrival_lng,
                 l.verification_code,
                 r.start_city,
                 r.end_city
             FROM delivery_lines ds
                      JOIN listings l ON ds.listing_id = l.listing_id
                      JOIN delivery_routes r ON ds.route_id = r.route_id
             WHERE r.courier_id = ? AND ds.status != 'livré'`,
            [courier_id]
        );


        res.json(rows);
    } catch (err) {
        console.error("Erreur récupération segments actifs :", err);
        res.status(500).json({ error: "Erreur serveur" });
    }
};


exports.updateSegmentLocation = async (req, res) => {
    const { segment_id, lat, lng } = req.body;
    const courier_id = req.user?.user_id;

    try {
        await db.promise().query(
            `UPDATE delivery_lines 
             SET current_lat = ?, current_lng = ?, updated_at = NOW()
             WHERE delivery_line_id = ?`,
            [lat, lng, segment_id]
        );
        res.json({ success: true });
    } catch (err) {
        console.error("Erreur update position :", err);
        res.status(500).json({ error: "Erreur serveur" });
    }
};

exports.splitDeliverySegment = async (req, res) => {
    const { segment_id, lat, lng, warehouse_id } = req.body;
    const courier_id = req.user?.user_id;

    try {
        // Terminer le segment actuel
        await db.promise().query(
            `UPDATE delivery_lines SET status = 'terminé', current_lat = ?, current_lng = ? WHERE line_id = ?`,
            [lat, lng, segment_id]
        );

        // Récupérer les infos du listing
        const [[line]] = await db.promise().query(
            `SELECT listing_id, route_id FROM delivery_lines WHERE line_id = ?`,
            [segment_id]
        );

        // Créer le nouveau segment libre
        await db.promise().query(
            `INSERT INTO delivery_lines (route_id, listing_id, current_lat, current_lng, status, warehouse_id)
             VALUES (?, ?, ?, ?, 'en_attente', ?)`,
            [line.route_id, line.listing_id, lat, lng, warehouse_id || null]
        );

        res.json({ success: true });
    } catch (err) {
        console.error("Erreur split segment :", err);
        res.status(500).json({ error: "Erreur serveur" });
    }
};

exports.completeSegment = async (req, res) => {
    const { segment_id, verification_code } = req.body;
    const courier_id = req.user?.user_id;

    try {
        const [[line]] = await db.promise().query(
            `SELECT dl.listing_id, l.verification_code
             FROM delivery_lines dl
             JOIN listings l ON dl.listing_id = l.listing_id
             WHERE dl.line_id = ?`,
            [segment_id]
        );

        if (!line || line.verification_code !== verification_code) {
            return res.status(400).json({ error: "Code de livraison incorrect" });
        }

        await db.promise().query(
            `UPDATE delivery_lines SET status = 'livré' WHERE line_id = ?`,
            [segment_id]
        );

        await db.promise().query(
            `UPDATE listings SET status = 'delivered' WHERE listing_id = ?`,
            [line.listing_id]
        );

        res.json({ success: true });
    } catch (err) {
        console.error("Erreur livraison finale :", err);
        res.status(500).json({ error: "Erreur serveur" });
    }
};

exports.updateDeliveryStatus = async (req, res) => {
    const { segment_id, status } = req.body;
    const courier_id = req.user?.user_id;

    if (!segment_id || !status) {
        return res.status(400).json({ success: false, error: "Champs segment_id et status requis" });
    }

    const allowedSteps = ['picked_up', 'in_transit', 'arrived', 'completed'];
    if (!allowedSteps.includes(status)) {
        return res.status(400).json({ success: false, error: "Statut invalide" });
    }

    try {
        // Enregistrement de l'étape dans l'historique
        await db.promise().query(
            `INSERT INTO delivery_progress_steps (line_id, step, location)
             VALUES (?, ?, NULL)`,
            [segment_id, status]
        );

        // Mise à jour du statut courant dans delivery_lines
        await db.promise().query(
            `UPDATE delivery_lines
             SET latest_step = ?
             WHERE line_id = ?`,
            [status, segment_id]
        );

        return res.json({ success: true, message: "Étape enregistrée" });

    } catch (err) {
        console.error("Erreur insertion étape progression :", err);
        return res.status(500).json({ success: false, error: "Erreur serveur lors de l'enregistrement" });
    }
};

exports.claimSegment = async (req, res) => {
    const { line_id } = req.body;
    const courier_id = req.user?.user_id;

    if (!line_id) {
        return res.status(400).json({ success: false, error: "Champ line_id requis" });
    }

    try {
        // Vérifier que le segment est bien dispo
        const [[line]] = await db.promise().query(
            `SELECT * FROM delivery_lines WHERE line_id = ? AND status = 'en_attente' AND courier_id IS NULL`,
            [line_id]
        );

        if (!line) {
            return res.status(404).json({ success: false, error: "Segment non disponible" });
        }

        // Mettre à jour le segment avec le livreur et le statut
        await db.promise().query(
            `UPDATE delivery_lines SET courier_id = ?, status = 'en_cours' WHERE line_id = ?`,
            [courier_id, line_id]
        );

        // Ajouter une étape "picked_up" (optionnel mais utile pour suivi)
        await db.promise().query(
            `INSERT INTO delivery_progress_steps (line_id, step, location) VALUES (?, 'picked_up', NULL)`,
            [line_id]
        );

        return res.json({ success: true });

    } catch (err) {
        console.error("Erreur claimSegment :", err);
        return res.status(500).json({ success: false, error: "Erreur serveur" });
    }
};


exports.getPendingSegments = async (req, res) => {
    try {
        const [rows] = await db.promise().query(`
            SELECT
                dl.line_id AS segment_id,
                dl.current_lat AS departure_lat,
                dl.current_lng AS departure_lng,
                l.listing_id,
                l.annonce_title,
                l.type,
                w.name AS warehouse_name,
                w.city,
                lp.photo_path
            FROM delivery_lines dl
                     JOIN listings l ON l.listing_id = dl.listing_id
                     LEFT JOIN warehouses w ON dl.warehouse_id = w.warehouse_id
                     LEFT JOIN (
                SELECT listing_id, MIN(photo_path) AS photo_path
                FROM listing_photos
                GROUP BY listing_id
            ) lp ON l.listing_id = lp.listing_id
            WHERE dl.status = 'en_attente' AND dl.warehouse_id IS NOT NULL
        `);

        res.json(rows);
    } catch (err) {
        console.error("Erreur getPendingSegments:", err);
        res.status(500).json({ error: "Erreur serveur" });
    }
};

