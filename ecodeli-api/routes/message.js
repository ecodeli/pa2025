const express = require('express');
const router = express.Router();
const messageController = require('../controllers/messageController');
const auth = require('../middlewares/auth');
const { body, param, query } = require('express-validator');
const { validationResult } = require('express-validator');

// Middleware de validation des erreurs
const handleValidationErrors = (req, res, next) => {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
        return res.status(400).json({
            success: false,
            message: 'Données de validation invalides',
            errors: errors.array()
        });
    }
    next();
};

// Envoyer un message
router.post('/send',
    auth,
    [
        body('receiver_id')
            .isInt({ min: 1 })
            .withMessage('ID du destinataire invalide'),
        body('content')
            .isLength({ min: 1, max: 2000 })
            .withMessage('Le contenu doit contenir entre 1 et 2000 caractères')
            .trim()
    ],
    handleValidationErrors,
    messageController.sendMessage
);

// Récupérer les conversations
router.get('/conversations',
    auth,
    messageController.getConversations
);

// Récupérer les messages d'une conversation
router.get('/conversation/:contact_id',
    auth,
    [
        param('contact_id')
            .isInt({ min: 1 })
            .withMessage('ID du contact invalide'),
        query('page')
            .optional()
            .isInt({ min: 1 })
            .withMessage('Le numéro de page doit être un entier positif'),
        query('limit')
            .optional()
            .isInt({ min: 1, max: 100 })
            .withMessage('La limite doit être entre 1 et 100')
    ],
    handleValidationErrors,
    messageController.getMessages
);

// Marquer un message comme lu
router.patch('/:message_id/read',
    auth,
    [
        param('message_id')
            .isInt({ min: 1 })
            .withMessage('ID du message invalide')
    ],
    handleValidationErrors,
    messageController.markAsRead
);

// Archiver un message
router.patch('/:message_id/archive',
    auth,
    [
        param('message_id')
            .isInt({ min: 1 })
            .withMessage('ID du message invalide')
    ],
    handleValidationErrors,
    messageController.archiveMessage
);

// Rechercher des utilisateurs
router.get('/search-users',
    auth,
    [
        query('query')
            .isLength({ min: 2, max: 100 })
            .withMessage('La recherche doit contenir entre 2 et 100 caractères')
            .trim()
    ],
    handleValidationErrors,
    messageController.searchUsers
);

// Obtenir le nombre de messages non lus
router.get('/unread-count',
    auth,
    messageController.getUnreadCount
);

module.exports = router;