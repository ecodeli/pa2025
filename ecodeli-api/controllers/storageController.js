const db = require('../config/db');
const fs = require('fs');
const path = require('path');
const PDFDocument = require('pdfkit');

// Réserver un box de stockage
exports.reserveBox = (req, res) => {
    const userId = req.user.user_id;
    const { warehouse_id, start_date, end_date } = req.body;
    const pricePerMonth = 30;

    const start = new Date(start_date);
    const end = new Date(end_date);
    const months = Math.max(1, end.getMonth() - start.getMonth() + (12 * (end.getFullYear() - start.getFullYear())));
    const totalAmount = months * pricePerMonth;

    if (!warehouse_id || !start_date || !end_date) {
        return res.status(400).json({ error: "Tous les champs sont requis." });
    }

    const sql = `
        SELECT * FROM storage_boxes
        WHERE warehouse_id = ? AND status = 'free'
        LIMIT 1
    `;

    db.query(sql, [warehouse_id], (err, rows) => {
        if (err) return res.status(500).json({ error: "Erreur BDD", details: err });
        if (rows.length === 0) return res.status(400).json({ error: "Aucun box disponible dans cet entrepôt." });

        const boxId = rows[0].box_id;

        const walletSql = `SELECT wallet_id, balance FROM wallet WHERE user_id = ?`;

        db.query(walletSql, [userId], (errW, walletRows) => {
            if (errW || walletRows.length === 0) {
                return res.status(500).json({ error: "Erreur wallet", details: errW });
            }

            const walletId = walletRows[0].wallet_id;
            const balance = parseFloat(walletRows[0].balance);

            if (balance < totalAmount) {
                return res.status(400).json({ error: "Solde insuffisant." });
            }

            const updateBox = `
                UPDATE storage_boxes
                SET status = 'reserved', start_date = ?, end_date = ?, user_id = ?
                WHERE box_id = ?
            `;

            db.query(updateBox, [start_date, end_date, userId, boxId], (err2) => {
                if (err2) return res.status(500).json({ error: "Erreur réservation", details: err2 });

                const updateWalletSql = `UPDATE wallet SET balance = balance - ? WHERE wallet_id = ?`;
                db.query(updateWalletSql, [totalAmount, walletId], (errWU) => {
                    if (errWU) return res.status(500).json({ error: "Erreur mise à jour du solde", details: errWU });

                    const txnSql = `
                        INSERT INTO wallet_transactions (wallet_id, amount, type, description, sender_id, receiver_id)
                        VALUES (?, ?, 'debit', ?, ?, ?)
                    `;
                    const description = `Location box entrepôt ${warehouse_id} - ${months} mois`;

                    db.query(txnSql, [walletId, totalAmount, description, userId, userId], (errTxn) => {
                        if (errTxn) return res.status(500).json({ error: "Erreur transaction", details: errTxn });

                        const invoiceSql = `INSERT INTO invoices (amount, invoice_date, user_id, courier_id) VALUES (?, NOW(), ?, ?)`;
                        db.query(invoiceSql, [totalAmount, userId, userId], (errInv, invRes) => {
                            if (errInv) return res.status(500).json({ error: "Erreur création facture", details: errInv });

                            const invoiceId = invRes.insertId;
                            const filename = `fac-warehouse-${invoiceId}.pdf`;
                            const invoiceFile = path.join('/invoices', filename);
                            const filePath = path.join(__dirname, '..', invoiceFile);

                            const userSql = `SELECT name FROM users WHERE user_id = ?`;
                            const warehouseSql = `SELECT city FROM warehouses WHERE warehouse_id = ?`;

                            db.query(userSql, [userId], (errU, userRows) => {
                                if (errU || userRows.length === 0) return res.status(500).json({ error: "Erreur user" });
                                const name = userRows[0].name;

                                db.query(warehouseSql, [warehouse_id], (errW2, wRows) => {
                                    if (errW2 || wRows.length === 0) return res.status(500).json({ error: "Erreur entrepôt" });
                                    const city = wRows[0].city;

                                    const doc = new PDFDocument();
                                    doc.pipe(fs.createWriteStream(filePath));

                                    doc.fontSize(20).text('Facture de réservation', { align: 'center' }).moveDown();
                                    doc.fontSize(12).text(`Nom : ${name}`);
                                    doc.text(`Entrepôt : ${city}`);
                                    doc.text(`Début : ${start_date}`);
                                    doc.text(`Fin : ${end_date}`);
                                    doc.text(`Durée : ${months} mois`);
                                    doc.text(`Montant : ${totalAmount.toFixed(2)} €`);
                                    doc.text(`Référence : #${invoiceId}`);

                                    doc.end();

                                    const updateFileSql = `UPDATE invoices SET invoice_file = ? WHERE invoice_id = ?`;
                                    db.query(updateFileSql, [invoiceFile, invoiceId]);

                                    return res.json({
                                        success: true,
                                        message: `Box réservé dans l'entrepôt ${city}`,
                                        invoice_url: invoiceFile
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

// Disponibilités par entrepôt
exports.getAvailability = (req, res) => {
    const sql = `
        SELECT w.warehouse_id, w.city, COUNT(sb.box_id) AS available_boxes
        FROM warehouses w
                 LEFT JOIN storage_boxes sb ON w.warehouse_id = sb.warehouse_id AND sb.status = 'free'
        GROUP BY w.warehouse_id, w.city
        ORDER BY w.city ASC
    `;

    db.query(sql, (err, results) => {
        if (err) return res.status(500).json({ error: "Erreur BDD", details: err });
        res.json(results);
    });
};
