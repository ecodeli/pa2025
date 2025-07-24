
const db = require('../config/db');

const messageController = {
    // Envoyer un message
    sendMessage: async (req, res) => {
        try {
            const { receiver_id, content } = req.body;
            const sender_id = req.user.user_id;

            if (!receiver_id || !content) {
                return res.status(400).json({
                    success: false,
                    message: 'Destinataire et contenu requis'
                });
            }

            // Vérifier que le destinataire existe
            const [receiverExists] = await db.promise().query(
                'SELECT user_id FROM users WHERE user_id = ?',
                [receiver_id]
            );

            if (receiverExists.length === 0) {
                return res.status(404).json({
                    success: false,
                    message: 'Destinataire non trouvé'
                });
            }

            // Insérer le message
            const [result] = await db.promise().query(
                'INSERT INTO messages (content, sender_id, receiver_id) VALUES (?, ?, ?)',
                [content, sender_id, receiver_id]
            );

            res.status(201).json({
                success: true,
                message: 'Message envoyé avec succès',
                data: {
                    message_id: result.insertId,
                    content,
                    sender_id,
                    receiver_id,
                    send_date: new Date()
                }
            });

        } catch (error) {
            console.error('Erreur lors de l\'envoi du message:', error);
            res.status(500).json({
                success: false,
                message: 'Erreur serveur lors de l\'envoi du message'
            });
        }
    },

    // Récupérer les conversations
    getConversations: async (req, res) => {
        try {
            const user_id = req.user.user_id;

            const [conversations] = await db.promise().query(`
        SELECT DISTINCT
          CASE 
            WHEN m.sender_id = ? THEN m.receiver_id 
            ELSE m.sender_id 
          END as contact_id,
          u.name as contact_name,
          u.avatar_url as contact_avatar,
          (SELECT content FROM messages m2 
           WHERE (m2.sender_id = ? AND m2.receiver_id = contact_id) 
              OR (m2.sender_id = contact_id AND m2.receiver_id = ?)
           ORDER BY m2.send_date DESC LIMIT 1) as last_message,
          (SELECT send_date FROM messages m2 
           WHERE (m2.sender_id = ? AND m2.receiver_id = contact_id) 
              OR (m2.sender_id = contact_id AND m2.receiver_id = ?)
           ORDER BY m2.send_date DESC LIMIT 1) as last_message_date,
          (SELECT COUNT(*) FROM messages m2 
           WHERE m2.sender_id = contact_id 
             AND m2.receiver_id = ? 
             AND m2.status = 'sent') as unread_count
        FROM messages m
        JOIN users u ON u.user_id = CASE 
          WHEN m.sender_id = ? THEN m.receiver_id 
          ELSE m.sender_id 
        END
        WHERE m.sender_id = ? OR m.receiver_id = ?
        ORDER BY last_message_date DESC
      `, [user_id, user_id, user_id, user_id, user_id, user_id, user_id, user_id, user_id]);

            res.json({
                success: true,
                data: conversations
            });

        } catch (error) {
            console.error('Erreur lors de la récupération des conversations:', error);
            res.status(500).json({
                success: false,
                message: 'Erreur serveur lors de la récupération des conversations'
            });
        }
    },

    // Récupérer les messages d'une conversation
    getMessages: async (req, res) => {
        try {
            const { contact_id } = req.params;
            const user_id = req.user.user_id;
            const page = parseInt(req.query.page) || 1;
            const limit = parseInt(req.query.limit) || 50;
            const offset = (page - 1) * limit;

            // Marquer les messages comme lus
            await db.promise().query(
                'UPDATE messages SET status = "read" WHERE sender_id = ? AND receiver_id = ? AND status = "sent"',
                [contact_id, user_id]
            );

            // Récupérer les messages
            const [messages] = await db.promise().query(`
        SELECT 
          m.message_id,
          m.content,
          m.send_date,
          m.status,
          m.sender_id,
          m.receiver_id,
          u.name as sender_name,
          u.avatar_url as sender_avatar
        FROM messages m
        JOIN users u ON u.user_id = m.sender_id
        WHERE (m.sender_id = ? AND m.receiver_id = ?) 
           OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.send_date DESC
        LIMIT ? OFFSET ?
      `, [user_id, contact_id, contact_id, user_id, limit, offset]);

            // Compter le total des messages
            const [countResult] = await db.promise().query(`
        SELECT COUNT(*) as total
        FROM messages 
        WHERE (sender_id = ? AND receiver_id = ?) 
           OR (sender_id = ? AND receiver_id = ?)
      `, [user_id, contact_id, contact_id, user_id]);

            res.json({
                success: true,
                data: {
                    messages: messages.reverse(), // Inverser pour avoir les plus anciens en premier
                    pagination: {
                        page,
                        limit,
                        total: countResult[0].total,
                        totalPages: Math.ceil(countResult[0].total / limit)
                    }
                }
            });

        } catch (error) {
            console.error('Erreur lors de la récupération des messages:', error);
            res.status(500).json({
                success: false,
                message: 'Erreur serveur lors de la récupération des messages'
            });
        }
    },

    // Marquer un message comme lu
    markAsRead: async (req, res) => {
        try {
            const { message_id } = req.params;
            const user_id = req.user.user_id;

            const [result] = await db.promise().query(
                'UPDATE messages SET status = "read" WHERE message_id = ? AND receiver_id = ?',
                [message_id, user_id]
            );

            if (result.affectedRows === 0) {
                return res.status(404).json({
                    success: false,
                    message: 'Message non trouvé ou non autorisé'
                });
            }

            res.json({
                success: true,
                message: 'Message marqué comme lu'
            });

        } catch (error) {
            console.error('Erreur lors du marquage du message:', error);
            res.status(500).json({
                success: false,
                message: 'Erreur serveur lors du marquage du message'
            });
        }
    },

    // Archiver un message
    archiveMessage: async (req, res) => {
        try {
            const { message_id } = req.params;
            const user_id = req.user.user_id;

            const [result] = await db.promise().query(
                'UPDATE messages SET status = "archived" WHERE message_id = ? AND (sender_id = ? OR receiver_id = ?)',
                [message_id, user_id, user_id]
            );

            if (result.affectedRows === 0) {
                return res.status(404).json({
                    success: false,
                    message: 'Message non trouvé ou non autorisé'
                });
            }

            res.json({
                success: true,
                message: 'Message archivé avec succès'
            });

        } catch (error) {
            console.error('Erreur lors de l\'archivage du message:', error);
            res.status(500).json({
                success: false,
                message: 'Erreur serveur lors de l\'archivage du message'
            });
        }
    },

    // Rechercher des utilisateurs pour démarrer une conversation
    searchUsers: async (req, res) => {
        try {
            const { query } = req.query;
            const user_id = req.user.user_id;

            if (!query || query.length < 2) {
                return res.status(400).json({
                    success: false,
                    message: 'La recherche doit contenir au moins 2 caractères'
                });
            }

            const [users] = await db.promise().query(`
        SELECT user_id, name, email, avatar_url, type
        FROM users 
        WHERE (name LIKE ? OR email LIKE ?) 
          AND user_id != ? 
          AND type != 'admin'
        LIMIT 10
      `, [`%${query}%`, `%${query}%`, user_id]);

            res.json({
                success: true,
                data: users
            });

        } catch (error) {
            console.error('Erreur lors de la recherche d\'utilisateurs:', error);
            res.status(500).json({
                success: false,
                message: 'Erreur serveur lors de la recherche d\'utilisateurs'
            });
        }
    },

    // Obtenir le nombre de messages non lus
    getUnreadCount: async (req, res) => {
        try {
            const user_id = req.user.user_id;

            const [result] = await db.promise().query(
                'SELECT COUNT(*) as unread_count FROM messages WHERE receiver_id = ? AND status = "sent"',
                [user_id]
            );

            res.json({
                success: true,
                data: {
                    unread_count: result[0].unread_count
                }
            });

        } catch (error) {
            console.error('Erreur lors du comptage des messages non lus:', error);
            res.status(500).json({
                success: false,
                message: 'Erreur serveur lors du comptage des messages non lus'
            });
        }
    }
};

module.exports = messageController;