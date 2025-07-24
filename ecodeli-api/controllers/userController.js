// controllers/userController.js

const db     = require('../config/db');
const bcrypt = require('bcrypt');
const jwt    = require('jsonwebtoken');

// Inscription
exports.registerUser = (req, res) => {
  const { name, email, password, type, phone, address } = req.body;
  const hashedPassword = bcrypt.hashSync(password, 10);

  const sqlUser = `
    INSERT INTO users (name, email, password, type, phone, address, registration_date)
    VALUES (?, ?, ?, ?, ?, ?, NOW())
  `;

  db.query(sqlUser, [name, email, hashedPassword, type, phone, address], (err, result) => {
    if (err) {
      console.error('Erreur lors de l’inscription :', err);
      return res.status(500).json({ message: "Erreur lors de l'inscription", error: err });
    }

    const user_id = result.insertId;

    // Création du wallet
    const sqlWallet = `INSERT INTO wallet (user_id, balance) VALUES (?, 0.00)`;
    db.query(sqlWallet, [user_id], (err2) => {
      if (err2) console.error('Erreur création wallet :', err2);

      // Création de l’abonnement FREE par défaut
      const sqlSubscription = `
        INSERT INTO subscriptions (user_id, type, status, start_date)
        VALUES (?, 'free', 'active', CURDATE())
      `;
      db.query(sqlSubscription, [user_id], (err3) => {
        if (err3) console.error('Erreur création abonnement FREE :', err3);

        // Création du token
        const token = jwt.sign(
            { user_id, name, email, type },
            process.env.JWT_SECRET,
            { expiresIn: '2h' }
        );

        res.status(201).json({
          message: "Utilisateur inscrit avec abonnement FREE",
          token
        });
      });
    });
  });
};


// Tous les utilisateurs
exports.getAllUsers = (req, res) => {
  db.query("SELECT * FROM users", (err, results) => {
    if (err) {
      console.error("Erreur SQL :", err);
      return res.status(500).json({ message: "Erreur BDD", error: err });
    }
    res.json(results);
  });
};

// Utilisateur par ID
exports.getUserById = (req, res) => {
  db.query("SELECT * FROM users WHERE user_id = ?", [req.params.id], (err, results) => {
    if (err) return res.status(500).json({ message: "Erreur BDD", error: err });
    if (results.length === 0) return res.status(404).json({ message: "Utilisateur non trouvé" });
    res.json(results[0]);
  });
};

// Modifier un utilisateur
exports.updateUser = (req, res) => {
  const { name, email, type, phone, address } = req.body;
  const sql = `
    UPDATE users
    SET name = ?, email = ?, type = ?, phone = ?, address = ?
    WHERE user_id = ?
  `;
  db.query(sql, [name, email, type, phone, address, req.params.id], (err) => {
    if (err) return res.status(500).json({ message: "Erreur BDD", error: err });
    res.json({ message: "Utilisateur mis à jour" });
  });
};

// Supprimer un utilisateur
exports.deleteUser = (req, res) => {
  db.query("DELETE FROM users WHERE user_id = ?", [req.params.id], (err) => {
    if (err) return res.status(500).json({ message: "Erreur BDD", error: err });
    res.json({ message: "Utilisateur supprimé" });
  });
};

// Connexion d'un utilisateur
exports.loginUser = (req, res) => {
  const { email, password } = req.body;

  db.query("SELECT * FROM users WHERE email = ?", [email], (err, results) => {
    if (err) return res.status(500).json({ message: "Erreur BDD", error: err });
    if (results.length === 0) return res.status(404).json({ message: "Utilisateur introuvable" });

    const user = results[0];


    if (user.is_banned) {
      return res.status(403).json({ message: "Votre compte est actuellement banni." });
    }


    if (!bcrypt.compareSync(password, user.password)) {
      return res.status(401).json({ message: "Mot de passe incorrect" });
    }


    // Vérifier l'abonnement
    const sqlSub = `SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active'`;

    db.query(sqlSub, [user.user_id], (errSub, subResults) => {
      if (errSub) {
        console.error("Erreur abonnement :", errSub);
        return res.status(500).json({ message: "Erreur vérification abonnement" });
      }

      if (subResults.length > 0) {
        const sub = subResults[0];
        const lastPayDate = new Date(sub.start_date);
        const today = new Date();
        const diffDays = Math.floor((today - lastPayDate) / (1000 * 60 * 60 * 24));

        // Si l'abonnement a plus de 30 jours
        if (diffDays >= 30) {
          const amount = sub.type === 'premium' ? 10 : 5;

          const sqlWallet = `SELECT * FROM wallet WHERE user_id = ?`;
          db.query(sqlWallet, [user.user_id], (errW, walletResults) => {
            if (errW || walletResults.length === 0) {
              console.error("Erreur wallet :", errW);
              return res.status(500).json({ message: "Erreur wallet" });
            }

            const wallet = walletResults[0];
            if (wallet.balance >= amount) {
              // Prélever et prolonger abonnement
              const newStartDate = new Date();
              db.query("UPDATE wallet SET balance = balance - ? WHERE wallet_id = ?", [amount, wallet.wallet_id]);
              db.query("UPDATE subscriptions SET start_date = ? WHERE subscription_id = ?", [newStartDate, sub.subscription_id]);

              // Créer transaction wallet
              const txnSql = `
                INSERT INTO wallet_transactions (wallet_id, amount, type, description, sender_id, receiver_id)
                VALUES (?, ?, 'debit', ?, ?, ?)
              `;
              db.query(txnSql, [wallet.wallet_id, amount, `Renouvellement abonnement ${sub.type}`, user.user_id, user.user_id]);

            } else {
              // Solde insuffisant -> stop abonnement + notification
              db.query("UPDATE subscriptions SET status = 'expired' WHERE subscription_id = ?", [sub.subscription_id]);

              const notifSql = `
                INSERT INTO notifications (message, user_id)
                VALUES ('Votre abonnement a été stoppé : solde insuffisant pour le renouvellement.', ?)
              `;
              db.query(notifSql, [user.user_id]);
            }
          });
        }
      }

      // Générer token après traitement abonnement
      const token = jwt.sign(
          { user_id: user.user_id, name: user.name, email: user.email, type: user.type },
          process.env.JWT_SECRET,
          { expiresIn: '2h' }
      );

      res.json({
        message: "Connexion réussie",
        token,
        user: {
          id: user.user_id,
          name: user.name,
          email: user.email,
          type: user.type
        }
      });
    });
  });
};


// Mettre à jour l’avatar
exports.updateAvatar = (req, res) => {
  const user_id = req.user.user_id;
  if (!req.file) {
    return res.status(400).json({ message: "Aucun fichier envoyé." });
  }
  const avatarUrl = '/uploads/avatars/' + req.file.filename;
  db.query("UPDATE users SET avatar_url = ? WHERE user_id = ?", [avatarUrl, user_id], (err) => {
    if (err) return res.status(500).json({ message: "Erreur BDD", error: err });
    res.json({ message: "Avatar mis à jour", avatar_url: avatarUrl });
  });
};

// Récupère l’utilisateur connecté
exports.getConnectedUser = (req, res) => {
  db.query("SELECT * FROM users WHERE user_id = ?", [req.user.user_id], (err, results) => {
    if (err) return res.status(500).json({ message: "Erreur BDD", error: err });
    if (!results.length) return res.status(404).json({ message: "Utilisateur non trouvé" });
    res.json({ user: results[0] });
  });
};

// Passer en livreur
exports.becomeCourier = (req, res) => {
  const userId = req.user.user_id;
  db.query("UPDATE users SET type = 'courier' WHERE user_id = ?", [userId], (err) => {
    if (err) return res.status(500).json({ message: "Erreur serveur", error: err });
    res.json({ message: "Compte mis à jour. Vous êtes maintenant livreur." });
  });
};

// Mettre à jour les infos de l'utilisateur connecté
exports.updateConnectedUser = async (req, res) => {
  const user_id = req.user.user_id;
  const { name, email, phone, address, password, current_password } = req.body;

  const fields = [];
  const values = [];

  if (name) {
    fields.push("name = ?");
    values.push(name);
  }

  if (email) {
    fields.push("email = ?");
    values.push(email);
  }

  if (phone) {
    fields.push("phone = ?");
    values.push(phone);
  }

  if (address) {
    fields.push("address = ?");
    values.push(address);
  }

  const applyUpdate = () => {
    if (fields.length === 0) {
      return res.status(400).json({ message: "Aucune donnée à mettre à jour" });
    }

    const sql = `UPDATE users SET ${fields.join(", ")} WHERE user_id = ?`;
    values.push(user_id);

    db.query(sql, values, (err) => {
      if (err) return res.status(500).json({ message: "Erreur lors de la mise à jour", error: err });
      return res.json({ message: "Profil mis à jour avec succès" });
    });
  };

  if (password) {
    db.query("SELECT password FROM users WHERE user_id = ?", [user_id], async (err, result) => {
      if (err || result.length === 0) {
        return res.status(500).json({ message: "Erreur BDD ou utilisateur introuvable" });
      }

      const isValid = await bcrypt.compare(current_password || "", result[0].password);
      if (!isValid) {
        return res.status(401).json({ message: "Mot de passe actuel incorrect" });
      }

      const hashedPassword = await bcrypt.hash(password, 10);
      fields.push("password = ?");
      values.push(hashedPassword);

      applyUpdate();
    });
  } else {
    applyUpdate();
  }
};

exports.getUserAverageRating = (req, res) => {
  const userId = req.params.id;

  const sql = `
        SELECT ROUND(AVG(note),1) AS average_rating
        FROM reviews
        WHERE courier_id = ?
    `;

  db.query(sql, [userId], (err, results) => {
    if (err) {
      console.error("Erreur getUserAverageRating :", err);
      return res.status(500).json({ error: "Erreur serveur." });
    }

    res.json({ average: results[0].average_rating || 0 });
  });
};
