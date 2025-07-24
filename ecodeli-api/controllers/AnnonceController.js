const db = require('../config/db');
const fs = require('fs');
const path = require('path')

exports.getAllAnnonce = (req, res) => {
    const userId = req.user.user_id;

    const sql = `
        SELECT 
            l.*, 
            lp.photo_path 
        FROM listings l
        LEFT JOIN listing_photos lp ON l.listing_id = lp.listing_id
        WHERE l.user_id = ? AND is_archived = FALSE
        GROUP BY l.listing_id
    `;

    db.query(sql, [userId], (err, result) => {
        if (err) {
            return res.status(500).json({ message: "Erreur serveur", error: err });
        }

        res.json(result);
    });
};


exports.getAnnonceByid = (req, res) => {
    const listing_id = req.params.id;

    const sqlAnnonce = "SELECT * FROM listings WHERE listing_id = ?";
    const sqlObjects = "SELECT * FROM listing_objects WHERE listing_id = ?";
    const sqlPhotos = "SELECT * FROM listing_photos WHERE listing_id = ?";

    db.query(sqlAnnonce, [listing_id], (err, result) => {
        if (err || result.length === 0) {
            return res.status(500).json({ message: "Erreur serveur ou annonce introuvable", error: err });
        }

        const annonce = result[0];

        db.query(sqlObjects, [listing_id], (err, objects) => {
            if (err) {
                return res.status(500).json({ message: "Erreur lors de la récupération des objets", error: err });
            }

            db.query(sqlPhotos, [listing_id], (err, photos) => {
                if (err) {
                    return res.status(500).json({ message: "Erreur lors de la récupération des photos", error: err });
                }

                annonce.objects = objects;
                annonce.photos = photos;

                res.json(annonce); // ✅ Un seul objet avec tous les sous-données incluses
            });
        });
    });
};

exports.getAllAnnonce = (req, res) => {
    const userId = req.user.user_id;

    const sql = `
        SELECT 
            l.*, 
            lp.photo_path 
        FROM listings l
        LEFT JOIN listing_photos lp ON l.listing_id = lp.listing_id
        WHERE l.user_id = ? AND is_archived = FALSE
        GROUP BY l.listing_id
    `;

    db.query(sql, [userId], (err, result) => {
        if (err) {
            return res.status(500).json({ message: "Erreur serveur", error: err });
        }

        res.json(result);
    });
};

exports.createAnnonce = (req, res) => {
    const {
        departure_city, arrival_city, deadline_date, price,
        details, departure_lat, departure_lng, arrival_lat,
        arrival_lng, type, annonce_title, service_radius,
        departure_address, delivery_address
    } = req.body;

    const livraison_directe = req.body.livraison_directe === "1" ? 1 : 0;
    const userId = req.user.user_id;

    const departureCity = departure_city || "Ville non précisée";
    const arrivalCity = arrival_city || "Ville non précisée";
    const deadlineDate = deadline_date || null;
    const prix = parseFloat(price) || 0.00;
    const listingType = type || "colis";
    const titleAnnonce = annonce_title || "Annonce EcoDeli";
    const verification_code = Math.floor(100000 + Math.random() * 900000).toString();

    const abonnementSql = `
        SELECT type FROM subscriptions
        WHERE user_id = ? AND status = 'active'
        ORDER BY start_date DESC LIMIT 1
    `;

    db.query(abonnementSql, [userId], (err, result) => {
        if (err) return res.status(500).json({ error: "Erreur abonnement", details: err });

        const subType = result.length ? result[0].type : 'free';
        console.log("Type d'abonnement détecté :", subType);

        const fraisBase = 10;
        let fraisLivraison = fraisBase;
        if (subType === 'starter') fraisLivraison *= 0.95;
        if (subType === 'premium') fraisLivraison *= 0.9;
        fraisLivraison = parseFloat(fraisLivraison.toFixed(2));
        console.log("💡 Frais livraison calculé :", fraisLivraison);

        const sqlAnnonce = `
            INSERT INTO listings (
                description, departure_city, arrival_city, deadline_date, price,
                user_id, type, category, details,
                departure_lat, departure_lng, arrival_lat, arrival_lng,
                annonce_title, status, verification_code, service_radius,
                delivery_fees, livraison_directe,
                departure_address, delivery_address
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        `;


        const valuesAnnonce = [
            "Annonce client créée",
            departureCity, arrivalCity, deadlineDate, prix,
            userId, listingType, null, details || null,
            departure_lat, departure_lng, arrival_lat, arrival_lng,
            titleAnnonce, "pending", verification_code, service_radius,
            fraisLivraison, livraison_directe,
            departure_address || null, delivery_address || null
        ];


        db.query(sqlAnnonce, valuesAnnonce, (err, result) => {
            if (err) {
                console.error("Erreur création annonce :", err);
                return res.status(500).json({ message: "Erreur serveur", error: err });
            }

            const listingId = result.insertId;
// 🔔 Notification
            const notificationMessage = `L'annonce "${titleAnnonce}" a bien été postée.`;
            const insertNotifSql = `
                INSERT INTO notifications (message, user_id)
                VALUES (?, ?)
            `;
            db.query(insertNotifSql, [notificationMessage, userId], (errNotif) => {
                if (errNotif) {
                    console.error("Erreur création notification :", errNotif);
                    // Ne bloque pas la réponse même s'il y a une erreur de notif
                }
            });

            // 📸 Photos
            const photos = req.files || [];
            if (photos.length > 0) {
                const photoValues = photos.map(photo => [
                    listingId,
                    '/uploads/photos/' + photo.filename
                ]);
                db.query(
                    `INSERT INTO listing_photos (listing_id, photo_path) VALUES ?`,
                    [photoValues],
                    err2 => { if (err2) console.error("Erreur insertion photos :", err2); }
                );
            }

            // 📦 Objets
            const {
                quantity = [], object_name: objectNames = [],
                format = [], poids = []
            } = req.body;

            let multiObj = [];
            if (Array.isArray(objectNames)) {
                multiObj = objectNames.map((name, i) => [
                    listingId,
                    quantity[i] || 1,
                    name,
                    format[i] || null,
                    poids[i] || null
                ]);
            } else if (objectNames) {
                multiObj.push([listingId, quantity, objectNames, format, poids]);
            }

            if (multiObj.length > 0) {
                db.query(
                    `INSERT INTO listing_objects (listing_id, quantity, object_name, format, poids) VALUES ?`,
                    [multiObj],
                    err3 => { if (err3) console.error("Erreur insertion objets :", err3); }
                );
            }

            res.status(201).json({
                message: "Annonce créée avec succès",
                listing_id: listingId,
                verification_code,
                abonnement: subType,
                delivery_fees: fraisLivraison,
                price: prix
            });
        });
    });
};




exports.updateAnnonce = (req, res) => {
    const listingId = req.params.id;
    const userId = req.user.user_id;

    if (!req.body || !req.body.annonce_title) {
        return res.status(400).json({ message: "Champs manquants dans la requête." });
    }

    const {
        annonce_title,
        type,
        price,
        details,
        departure_city,
        arrival_city,
        departure_lat,
        departure_lng,
        arrival_lat,
        arrival_lng,
        service_radius
    } = req.body;

    const sql = `
        UPDATE listings SET
                            annonce_title = ?,
                            type = ?,
                            price = ?,
                            details = ?,
                            departure_city = ?,
                            arrival_city = ?,
                            departure_lat = ?,
                            departure_lng = ?,
                            arrival_lat = ?,
                            arrival_lng = ?,
                            service_radius = ?
        WHERE listing_id = ? AND user_id = ?
    `;

    const values = [
        annonce_title,
        type,
        price,
        details,
        departure_city,
        arrival_city,
        departure_lat,
        departure_lng,
        arrival_lat,
        arrival_lng,
        service_radius || 0,
        listingId,
        userId
    ];

    db.query(sql, values, (err, result) => {
        if (err) return res.status(500).json({ message: "Erreur SQL", error: err });
        if (result.affectedRows === 0) return res.status(403).json({ message: "Annonce non trouvée ou non autorisée." });

        // Suppression des anciens objets
        db.query("DELETE FROM listing_objects WHERE listing_id = ?", [listingId], (err) => {
            if (err) return res.status(500).json({ message: "Erreur suppression objets", error: err });

            const { quantity = [], object_name = [], format = [], poids = [] } = req.body;
            const multiObj = [];

            if (Array.isArray(object_name)) {
                for (let i = 0; i < object_name.length; i++) {
                    if (object_name[i]) {
                        multiObj.push([
                            listingId,
                            quantity[i] || 1,
                            object_name[i],
                            format[i] || null,
                            poids[i] || null
                        ]);
                    }
                }
            } else if (object_name) {
                multiObj.push([
                    listingId,
                    quantity || 1,
                    object_name,
                    format || null,
                    poids || null
                ]);
            }

            // Insertion objets
            const insertObjects = () => {
                if (multiObj.length > 0) {
                    db.query(
                        `INSERT INTO listing_objects (listing_id, quantity, object_name, format, poids) VALUES ?`,
                        [multiObj],
                        (err) => {
                            if (err) return res.status(500).json({ message: "Erreur ajout objets", error: err });
                            insertPhotos(); // ensuite on traite les photos
                        }
                    );
                } else {
                    insertPhotos(); // aucun objet à insérer, on passe directement aux photos
                }
            };

            // Insertion photos
            const insertPhotos = () => {
                const files = req.files || [];
                if (Array.isArray(files) && files.length > 0) {
                    const photoValues = files.map(photo => [
                        listingId,
                        '/uploads/photos/' + photo.filename
                    ]);

                    db.query(
                        `INSERT INTO listing_photos (listing_id, photo_path) VALUES ?`,
                        [photoValues],
                        (err) => {
                            if (err) {
                                return res.status(500).json({ message: "Erreur insertion photos", error: err });
                            }
                            return res.json({ message: "Annonce mise à jour avec succès (objets + photos)." });
                        }
                    );
                } else {
                    return res.json({ message: "Annonce mise à jour avec succès (sans nouvelles photos)." });
                }
            };

            insertObjects();
        });
    });
};



exports.deleteAnnonce = (req, res) => {
    const id = req.params.id;

    const archiveSql = "UPDATE listings SET is_archived = TRUE WHERE listing_id = ?";
    db.query(archiveSql, [id], (err, result) => {
        if (err) {
            console.error("Erreur lors de l'archivage :", err);
            return res.status(500).json({ message: "Erreur serveur lors de l'archivage de l'annonce", error: err });
        }

        return res.json({ message: "Annonce archivée avec succès" });
    });
};
    /*
    const findPhotosSql = "SELECT photo_path FROM listing_photos WHERE listing_id = ?";
    db.query(findPhotosSql, [id], (err, results) => {
        if (err) return res.status(500).json({ message: "Erreur serveur lors de la recherche des photos", error: err });

        results.forEach(row => {
            if (row.photo_path) {
                const fullPath = path.join(__dirname, '..', row.photo_path);
                if (fs.existsSync(fullPath)) {
                    fs.unlink(fullPath, (err) => {
                        if (err) {
                            console.error("Erreur suppression photo fichier :", err);
                        }
                    });
                }
            }
        });

        const deleteSql = "DELETE FROM listings WHERE listing_id = ?";
        db.query(deleteSql, [id], (err, result) => {
            if (err) return res.status(500).json({ message: "Erreur serveur lors de la suppression de l'annonce", error: err });
            res.json({ message: "Annonce et toutes ses photos supprimées avec succès" });
        });
    });
};
*/

exports.deletePhoto = (req, res) => {
    const filename = req.params.filename;
    const photoPath = `/uploads/photos/${filename}`;

    const fullPath = path.join(__dirname, '..', photoPath);
    if (!fs.existsSync(fullPath)) {
        return res.status(404).json({ message: "Fichier non trouvé" });
    }

    fs.unlink(fullPath, (err) => {
        if (err) return res.status(500).json({ message: "Erreur lors de la suppression", error: err });

        // Supprimer l'entrée en base
        db.query("DELETE FROM listing_photos WHERE photo_path = ?", [photoPath], (err, result) => {
            if (err) return res.status(500).json({ message: "Erreur BDD", error: err });
            res.json({ message: "Photo supprimée avec succès" });
        });
    });
};

exports.getAnnonceFullDetails = (req, res) => {
    const listing_id = req.params.id;

    const sqlAnnonce = `
        SELECT
            l.listing_id, l.user_id, l.annonce_title, l.details, l.price, l.type,
            l.departure_city, l.arrival_city, l.deadline_date, l.livraison_directe,
            u.name AS creator_name, u.email, u.phone, u.avatar_url AS creator_photo,
            (SELECT AVG(note) FROM delivery_notes WHERE listing_id = ?) AS average_note
        FROM listings l
                 JOIN users u ON l.user_id = u.user_id
        WHERE l.listing_id = ?


    `;

    const sqlPhotos = `SELECT photo_path FROM listing_photos WHERE listing_id = ?`;
    const sqlObjects = `SELECT * FROM listing_objects WHERE listing_id = ?`;

    db.query(sqlAnnonce, [listing_id, listing_id], (err, annonceResult) => {
        if (err || annonceResult.length === 0) {
            console.log("Erreur SQL :", err);
            return res.status(500).json({ message: "Annonce introuvable ou erreur serveur", error: err });
        }


        const annonce = annonceResult[0];

        db.query(sqlPhotos, [listing_id], (err, photos) => {
            if (err) return res.status(500).json({ message: "Erreur chargement photos", error: err });

            db.query(sqlObjects, [listing_id], (err, objects) => {
                if (err) return res.status(500).json({ message: "Erreur chargement objets", error: err });

                annonce.photos = photos;
                annonce.objects = objects;

                return res.json(annonce);
            });
        });
    });
};

exports.getAnnonceForReview = (req, res) => {
    const listing_id = req.params.id;

    const sql = `
        SELECT
            l.listing_id, l.annonce_title, l.status, l.price,
            l.departure_city, l.arrival_city,
            l.user_id AS client_id,
            u.user_id AS courier_id, u.name AS courier_name
        FROM listings l
        LEFT JOIN deliveries d ON l.listing_id = d.listing_id
        LEFT JOIN users u ON d.courier_id = u.user_id
        WHERE l.listing_id = ?
    `;

    db.query(sql, [listing_id], (err, result) => {
        if (err) {
            console.error("Erreur getAnnonceForReview :", err);
            return res.status(500).json({ error: "Erreur serveur." });
        }

        if (result.length === 0) {
            return res.status(404).json({ error: "Annonce introuvable." });
        }

        res.json(result[0]);
    });
};



exports.postReview = (req, res) => {
    const user_id = req.user.user_id; // client
    const listingId = req.params.id;
    const { note, commentaire, courier_id } = req.body;

    const parsedNote = parseFloat(note);
    if (isNaN(parsedNote) || parsedNote < 0 || parsedNote > 5) {
        return res.status(400).json({ error: 'Note invalide (0-5).' });
    }

    if (!courier_id) {
        return res.status(400).json({ error: 'ID du livreur manquant.' });
    }

    const checkSql = `
        SELECT * FROM reviews
        WHERE listing_id = ? AND auteur_id = ?
    `;

    db.query(checkSql, [listingId, user_id], (err, results) => {
        if (err) {
            console.error("Erreur vérif doublon avis :", err);
            return res.status(500).json({ error: 'Erreur serveur.' });
        }

        if (results.length > 0) {
            return res.status(400).json({ error: 'Vous avez déjà laissé un avis pour cette annonce.' });
        }

        const insertSql = `
            INSERT INTO reviews (listing_id, user_id, courier_id, note, commentaire, auteur_id)
            VALUES (?, ?, ?, ?, ?, ?)
        `;

        db.query(insertSql, [listingId, user_id, courier_id, parsedNote, commentaire, user_id], (err) => {
            if (err) {
                console.error("Erreur postReview :", err);
                return res.status(500).json({ error: 'Erreur serveur.' });
            }

            res.json({ success: true, message: 'Avis sur le livreur ajouté avec succès.' });
        });
    });
};




exports.getAnnonceForReviewClient = (req, res) => {
    const listing_id = req.params.id;

    const sql = `
        SELECT
            l.listing_id, l.annonce_title,
            u.user_id AS client_id, u.name AS client_name
        FROM listings l
                 LEFT JOIN users u ON l.user_id = u.user_id
        WHERE l.listing_id = ?
    `;

    db.query(sql, [listing_id], (err, result) => {
        if (err) {
            console.error("Erreur getAnnonceForReviewClient :", err);
            return res.status(500).json({ error: "Erreur serveur." });
        }

        if (result.length === 0) {
            return res.status(404).json({ error: "Annonce introuvable." });
        }

        res.json(result[0]);
    });
};


exports.postReviewClient = (req, res) => {
    const courier_id = req.user.user_id; // livreur
    const listingId = req.params.id;
    const { note, commentaire, user_id } = req.body;

    const parsedNote = parseFloat(note);
    if (isNaN(parsedNote) || parsedNote < 0 || parsedNote > 5) {
        return res.status(400).json({ error: 'Note invalide (0-5).' });
    }

    if (!user_id) {
        return res.status(400).json({ error: 'ID du client manquant.' });
    }

    const checkSql = `
        SELECT * FROM reviews
        WHERE listing_id = ? AND auteur_id = ?
    `;

    db.query(checkSql, [listingId, courier_id], (err, results) => {
        if (err) {
            console.error("Erreur vérif doublon avis (livreur) :", err);
            return res.status(500).json({ error: 'Erreur serveur.' });
        }

        if (results.length > 0) {
            return res.status(400).json({ error: 'Vous avez déjà laissé un avis pour cette annonce.' });
        }

        const insertSql = `
            INSERT INTO reviews (listing_id, courier_id, user_id, note, commentaire, auteur_id)
            VALUES (?, ?, ?, ?, ?, ?)
        `;

        db.query(insertSql, [listingId, courier_id, user_id, parsedNote, commentaire, courier_id], (err) => {
            if (err) {
                console.error("Erreur postReviewClient :", err);
                return res.status(500).json({ error: 'Erreur serveur.' });
            }

            res.json({ success: true, message: 'Avis sur le client ajouté avec succès.' });
        });
    });
};


exports.getReviewsReceived = (req, res) => {
    const userId = req.user.user_id;

    const sql = `
        SELECT 
            r.*, 
            a.annonce_title,
            auteur.name AS auteur_name
        FROM reviews r
        LEFT JOIN listings a ON a.listing_id = r.listing_id
        LEFT JOIN users auteur ON auteur.user_id = r.auteur_id
        WHERE (r.user_id = ? OR r.courier_id = ?)
          AND r.auteur_id != ?
        ORDER BY r.created_at DESC
    `;

    db.query(sql, [userId, userId, userId], (err, results) => {
        if (err) {
            console.error("Erreur getReviewsReceived :", err);
            return res.status(500).json({ error: "Erreur serveur." });
        }

        res.json(results);
    });
};






