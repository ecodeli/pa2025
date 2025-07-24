const db = require('../config/db');
const path = require('path');
const fs = require('fs');
const multer = require('multer');

// Storage configuration
const storage = multer.diskStorage({
    destination: (req, file, cb) => {
        cb(null, 'Documents/');
    },
    filename: (req, file, cb) => {
        const uniqueName = Date.now() + '-' + file.originalname;
        cb(null, uniqueName);
    }
});
const upload = multer({ storage: storage });

exports.uploadDocuments = (req, res) => {
    const userId = req.user.user_id;
    const files = req.files;

    if (!files || (!files.identity && !files.address && !files.permis)) {
        return res.status(400).json({ error: 'Aucun fichier téléversé' });
    }

    const sql = `INSERT INTO documents (user_id, document_type, file_path) VALUES ?`;
    const values = [];

    if (files.identity) {
        values.push([userId, 'identite', '/Documents/' + files.identity[0].filename]);
    }
    if (files.permis) {
        values.push([userId, 'permis', '/Documents/' + files.permis[0].filename]);
    }
    if (files.address) {
        values.push([userId, 'domicile', '/Documents/' + files.address[0].filename]);
    }

    db.query(sql, [values], (err) => {
        if (err) {
            console.error("Erreur upload documents :", err);
            return res.status(500).json({ error: 'Erreur serveur' });
        }
        res.json({ success: true });
    });
};

exports.getAllDocuments = (req, res) => {
    const search = req.query.search || '';
    const page = parseInt(req.query.page) || 1;
    const limit = 5;
    const offset = (page - 1) * limit;

    const countSql = `
        SELECT COUNT(*) AS total
        FROM documents d
        JOIN users u ON d.user_id = u.user_id
        WHERE u.name LIKE ?
    `;
    const dataSql = `
        SELECT d.*, u.name
        FROM documents d
        JOIN users u ON d.user_id = u.user_id
        WHERE u.name LIKE ?
        ORDER BY d.upload_date DESC
        LIMIT ? OFFSET ?
    `;
    const searchTerm = '%' + search + '%';

    db.query(countSql, [searchTerm], (err, countRows) => {
        if (err) {
            console.error("Erreur count documents :", err);
            return res.status(500).json({ error: 'Erreur serveur' });
        }
        const total = countRows[0].total;
        const totalPages = Math.ceil(total / limit);

        db.query(dataSql, [searchTerm, limit, offset], (err2, rows) => {
            if (err2) {
                console.error("Erreur récupération documents :", err2);
                return res.status(500).json({ error: 'Erreur serveur' });
            }

            res.json({
                documents: rows,
                currentPage: page,
                totalPages
            });
        });
    });
};

exports.validateDocument = (req, res) => {
    const documentId = req.body.document_id;

    if (!documentId) return res.status(400).json({ error: "ID document manquant" });

    // 1. Récupérer l'utilisateur et le type de document
    const getUserSql = `SELECT user_id, document_type FROM documents WHERE document_id = ?`;

    db.query(getUserSql, [documentId], (err, result) => {
        if (err || result.length === 0) {
            console.error("Erreur récupération document :", err);
            return res.status(500).json({ error: "Erreur serveur ou document introuvable" });
        }

        const userId = result[0].user_id;
        const type = result[0].document_type;

        // 2. Valider le document
        const updateDocSql = `UPDATE documents SET is_verified = 1 WHERE document_id = ?`;
        db.query(updateDocSql, [documentId], (err2) => {
            if (err2) {
                console.error("Erreur validation document :", err2);
                return res.status(500).json({ error: 'Erreur serveur' });
            }

            // 3. Si c'est un justificatif de domicile, mettre à jour aussi users.domicile_verified = 1
            if (type === 'domicile') {
                db.query(`UPDATE users SET domicile_verified = 1 WHERE user_id = ?`, [userId], (err3) => {
                    if (err3) {
                        console.error("Erreur update domicile_verified :", err3);
                        return res.status(500).json({ error: "Erreur serveur" });
                    }

                    // 4. Vérifie si identity_verified est déjà OK
                    db.query(`SELECT identity_verified FROM users WHERE user_id = ?`, [userId], (err4, rows) => {
                        if (err4) return res.status(500).json({ error: "Erreur serveur" });

                        if (rows[0].identity_verified === 1) {
                            // Si les deux sont bons, activer is_verified
                            db.query(`UPDATE users SET is_verified = 1 WHERE user_id = ?`, [userId], (err5) => {
                                if (err5) return res.status(500).json({ error: "Erreur update is_verified" });
                                return res.json({ success: true, message: "Domicile validé + utilisateur vérifié " });
                            });
                        } else {
                            return res.json({ success: true, message: "Domicile validé. En attente de l’identité." });
                        }
                    });
                });

            } else {
                // Si c’est un autre type → utiliser ta logique actuelle
                const checkAllSql = `
                    SELECT document_type FROM documents
                    WHERE user_id = ? AND is_verified = 1
                `;
                db.query(checkAllSql, [userId], (err3, validDocs) => {
                    if (err3) {
                        console.error("Erreur vérification documents :", err3);
                        return res.status(500).json({ error: "Erreur serveur" });
                    }

                    const typesValidés = validDocs.map(d => d.document_type);
                    const requis = ['identite', 'domicile', 'permis'];
                    const tousValidés = requis.every(type => typesValidés.includes(type));

                    if (tousValidés) {
                        db.query(`UPDATE users SET is_verified = 1 WHERE user_id = ?`, [userId], (err4) => {
                            if (err4) return res.status(500).json({ error: "Erreur mise à jour utilisateur" });
                            return res.json({ success: true, message: "Document validé + utilisateur vérifié " });
                        });
                    } else {
                        return res.json({ success: true, message: "Document validé. En attente des autres." });
                    }
                });
            }
        });
    });
};


exports.refuseDocument = (req, res) => {
    const { document_id } = req.body;
    if (!document_id) return res.status(400).json({ error: 'Missing document_id' });

    db.query('UPDATE documents SET is_verified = -1 WHERE document_id = ?', [document_id], (err) => {
        if (err) return res.status(500).json({ error: 'DB error' });
        return res.json({ success: true });
    });
};

exports.revokeDocument = (req, res) => {
    const { document_id } = req.body;
    if (!document_id) return res.status(400).json({ error: 'Missing document_id' });

    db.query('UPDATE documents SET is_verified = 0 WHERE document_id = ?', [document_id], (err) => {
        if (err) return res.status(500).json({ error: 'DB error' });
        return res.json({ success: true });
    });
};


