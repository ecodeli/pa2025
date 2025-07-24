const db = require('../config/db');

exports.getDashboardData = (req, res) => {
    const dashboardData = {};

    //  Nombre d'annonces par ville
    const sql1 = `
        SELECT departure_city, COUNT(*) AS nb_annonces
        FROM listings
        GROUP BY departure_city
    `;

    db.query(sql1, (err1, result1) => {
        if (err1) {
            console.error(err1);
            return res.status(500).json({ error: 'Erreur SQL annonces par ville' });
        }

        dashboardData.annoncesVille = result1;

        // Répartition des utilisateurs par rôle
        const sql2 = `
            SELECT type, COUNT(*) AS nb_users
            FROM users
            GROUP BY type
        `;

        db.query(sql2, (err2, result2) => {
            if (err2) {
                console.error(err2);
                return res.status(500).json({ error: 'Erreur SQL users par rôle' });
            }

            dashboardData.usersRole = result2;

            // Prix moyen des annonces par ville
            const sql3 = `
                SELECT departure_city, ROUND(AVG(price),2) AS prix_moyen
                FROM listings
                GROUP BY departure_city
            `;

            db.query(sql3, (err3, result3) => {
                if (err3) {
                    console.error(err3);
                    return res.status(500).json({ error: 'Erreur SQL prix moyen ville' });
                }

                dashboardData.prixMoyenVille = result3;


                res.json(dashboardData);
            });
        });
    });
};
