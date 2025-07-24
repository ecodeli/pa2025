const express = require('express');
const router = express.Router();
const auth = require('../middlewares/auth');
const livraisonController = require('../controllers/livraisonController');

router.post('/mark-delivered', auth, livraisonController.markAsDelivered);
router.get('/my-deliveries', auth, livraisonController.getMyDeliveries);
router.get('/delivery/:id', auth, livraisonController.getDeliveryDetails);

module.exports = router;
