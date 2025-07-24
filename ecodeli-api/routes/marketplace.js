const express = require('express');
const router = express.Router();
const marketplaceController = require('../controllers/marketplaceController');

router.get('/marketplace/services', marketplaceController.getAvailableServices);

module.exports = router;
