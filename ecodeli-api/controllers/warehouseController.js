const db = require('../config/db');

exports.getAllWarehouses = async (req, res) => {
    try {
        const [rows] = await db.promise().query('SELECT * FROM warehouses');
        res.json(rows);
    } catch (err) {
        console.error("Erreur récupération entrepôts :", err);
        res.status(500).json({ message: "Erreur lors de la récupération des entrepôts." });
    }
};
