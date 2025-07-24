const express = require('express');
const router  = express.Router();
const auth    = require('../middlewares/auth');
const deliveryController = require('../controllers/deliveryController');

// Liste les trajets du coursier connecté
router.get('/delivery/my-trips', auth, deliveryController.getMyTrips);

module.exports = router;
