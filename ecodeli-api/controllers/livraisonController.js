const db = require('../config/db');
const fs = require('fs');
const path = require('path');
const PDFDocument = require('pdfkit');

// Génère un numéro de facture au format fac-0001
const getNextInvoiceNumber = (callback) => {
    const sql = `SELECT COUNT(*) AS total FROM invoices`;
    db.query(sql, (err, rows) => {
        if (err) return callback(err);
        const next = rows[0].total + 1;
        const ref = `fac-${String(next).padStart(4, '0')}`;
        callback(null, ref);
    });
};


exports.markAsDelivered = (req, res) => {
    const { delivery_id, verification_code } = req.body;
    const courierId = req.user.user_id;

    if (!delivery_id || !verification_code) {
        return res.status(400).json({ error: "ID livraison et code requis" });
    }

    const sql = `
        SELECT d.delivery_id,d.verification_code, d.arrival_date,
               l.listing_id, l.annonce_title, l.departure_city, l.arrival_city, l.price, l.user_id AS annonceur_id,
               u1.name AS livreur_nom, u1.email AS livreur_email,
               u2.name AS emetteur_nom, u2.email AS emetteur_email,
               dl.line_id
        FROM deliveries d
                 JOIN listings l ON d.listing_id = l.listing_id
                 JOIN users u1 ON d.courier_id = u1.user_id
                 JOIN users u2 ON l.user_id = u2.user_id
                 JOIN delivery_lines dl ON dl.listing_id = l.listing_id
        WHERE d.delivery_id = ?
    `;

    db.query(sql, [delivery_id], (err, rows) => {
        if (err || rows.length === 0) return res.status(404).json({ error: "Livraison non trouvée" });

        const data = rows[0];

        if (verification_code !== data.verification_code) {
            return res.status(401).json({ error: "Code de livraison incorrect" });
        }

        const update1 = `UPDATE deliveries SET status = 'delivered' WHERE delivery_id = ?`;
        const update2 = `UPDATE listings SET status = 'delivered' WHERE listing_id = ?`;

        db.query(update1, [delivery_id], (err1) => {
            if (err1) return res.status(500).json({ error: "Erreur livraison" });

            db.query(update2, [data.listing_id], (err2) => {
                if (err2) return res.status(500).json({ error: "Erreur annonce" });

                // 🔁 Mise à jour latest_step et insertion étape 'completed'
                const updateStep = `
                    UPDATE delivery_lines SET latest_step = 'completed' WHERE line_id = ?
                `;
                const insertStep = `
                    INSERT INTO delivery_progress_steps (line_id, step, location)
                    VALUES (?, 'completed', NULL)
                `;

                db.query(updateStep, [data.line_id], (err3) => {
                    if (err3) return res.status(500).json({ error: "Erreur update étape" });

                    db.query(insertStep, [data.line_id], (err4) => {
                        if (err4) return res.status(500).json({ error: "Erreur insertion étape" });

                        // Génération numéro facture
                        getNextInvoiceNumber((err5, invoiceRef) => {
                            if (err5) return res.status(500).json({ error: "Erreur numéro facture" });

                            const fileName = `${invoiceRef}.pdf`;
                            const filePath = path.join(__dirname, '../invoices', fileName);

                            const doc = new PDFDocument();
                            doc.pipe(fs.createWriteStream(filePath));

                            doc.fontSize(20).text(`Facture ${invoiceRef}`, { align: 'center' });
                            doc.moveDown();

                            doc.fontSize(12).text(`N° Livraison : ${data.delivery_id}`);
                            doc.text(`Date de livraison : ${new Date(data.arrival_date).toLocaleDateString()}`);
                            doc.moveDown();

                            doc.fontSize(14).text("Émetteur de l'annonce");
                            doc.fontSize(12).text(`Nom : ${data.emetteur_nom}`);
                            doc.text(`Email : ${data.emetteur_email}`);
                            doc.moveDown();

                            doc.fontSize(14).text("Livreur");
                            doc.fontSize(12).text(`Nom : ${data.livreur_nom}`);
                            doc.text(`Email : ${data.livreur_email}`);
                            doc.moveDown();

                            doc.fontSize(14).text("Détails de la livraison");
                            doc.fontSize(12).text(`Titre : ${data.annonce_title}`);
                            doc.text(`Départ : ${data.departure_city}`);
                            doc.text(`Arrivée : ${data.arrival_city}`);
                            doc.text(`Montant : ${data.price} €`);
                            doc.end();

                            // Insertion de la facture
                            const insertInvoice = `
                                INSERT INTO invoices (user_id, courier_id, amount, invoice_file)
                                VALUES (?, ?, ?, ?)
                            `;
                            db.query(insertInvoice, [
                                data.annonceur_id,
                                courierId,
                                data.price,
                                `/invoices/${fileName}`
                            ], (err6) => {
                                if (err6) {
                                    console.error("Erreur insert facture :", err6);
                                    return res.status(500).json({ error: 'Erreur enregistrement facture' });
                                }

                                // TRAITEMENT FINANCIER
                                const getWalletSql = `SELECT wallet_id FROM wallet WHERE user_id = ?`;
                                db.query(getWalletSql, [courierId], (errW1, resW1) => {
                                    if (errW1 || resW1.length === 0) return res.status(500).json({ error: "Livreur sans wallet" });

                                    const courierWallet = resW1[0].wallet_id;

                                    db.query(`UPDATE wallet SET balance = balance + ? WHERE wallet_id = ?`, [data.price, courierWallet], (errCred) => {
                                        if (errCred) return res.status(500).json({ error: "Erreur crédit livreur" });

                                        const desc = `Paiement de livraison annonce #${data.listing_id}`;
                                        db.query(`
                                            INSERT INTO wallet_transactions (wallet_id, amount, type, description, sender_id, receiver_id)
                                            VALUES (?, ?, 'credit', ?, ?, ?)
                                        `, [courierWallet, data.price, desc, data.annonceur_id, courierId], (errTx2) => {
                                            if (errTx2) return res.status(500).json({ error: "Erreur transaction crédit" });

                                            res.json({ success: true });
                                        });
                                    });
                                });
                            });
                        });
                    });
                });
            });
        });
    });
};

exports.getMyDeliveries = async (req, res) => {
    const userId = req.user?.user_id;

    if (!userId) {
        return res.status(401).json({ error: "Non autorisé" });
    }

    try {
        const [rows] = await db.promise().query(
            `SELECT 
                listing_id,
                annonce_title,
                departure_city,
                arrival_city,
                status,
                deadline_date,
                price
            FROM listings
            WHERE user_id = ?`,
            [userId]
        );

        return res.json(rows);
    } catch (err) {
        console.error("Erreur lors de la récupération des listings :", err);
        return res.status(500).json({ error: "Erreur serveur" });
    }
};



exports.getDeliveryDetails = async (req, res) => {
    const { id } = req.params;

    try {
        const [rows] = await db.promise().query(
            `SELECT 
                l.annonce_title AS title,
                l.description,
                l.price,
                l.verification_code,
                l.departure_city,
                l.arrival_city,
                l.departure_address,
                l.delivery_address,
                l.deadline_date,
                l.status,
                l.user_id,
                u.name AS courier_name,
                u.avatar_url
             FROM listings l
             LEFT JOIN users u ON l.user_id = u.user_id
             WHERE l.listing_id = ?`,
            [id]
        );

        if (rows.length === 0) {
            return res.status(404).json({ message: 'Annonce non trouvée' });
        }

        res.json(rows[0]);

    } catch (err) {
        console.error("Erreur SQL dans getDeliveryDetails:", err);
        res.status(500).json({ message: "Erreur serveur" });
    }
};
