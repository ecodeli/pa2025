const db = require('../config/db'); // adapt selon ton setup

exports.addStep = (req, res) => {
    const { delivery_line_id, step, location } = req.body;

    if (!delivery_line_id || !step) {
        return res.status(400).json({ error: "delivery_line_id et step sont requis." });
    }

    const sql = `
        INSERT INTO delivery_progress_steps (delivery_line_id, step, location)
        VALUES (?, ?, ?)
    `;

    db.query(sql, [delivery_line_id, step, location || null], (err, result) => {
        if (err) {
            console.error("Erreur INSERT étape :", err);
            return res.status(500).json({ error: "Erreur serveur lors de l'ajout de l'étape." });
        }

        res.json({ success: true, message: "Étape ajoutée avec succès." });
    });
};

exports.getStepsByDeliveryLine = (req, res) => {
    const delivery_line_id = req.params.id;

    const sql = `
        SELECT * FROM delivery_progress_steps
        WHERE delivery_line_id = ?
        ORDER BY timestamp ASC
    `;

    db.query(sql, [delivery_line_id], (err, results) => {
        if (err) {
            console.error("Erreur SELECT étapes :", err);
            return res.status(500).json({ error: "Erreur serveur." });
        }

        res.json(results);
    });
};
