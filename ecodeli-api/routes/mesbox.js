const express = require('express');
const router = express.Router();
const warehouseBoxController = require('../controllers/mesboxController');
const auth = require('../middlewares/auth'); // middleware JWT

// Route protégée, sans ID dans l’URL, car on le récupère via le token
router.get('/warehouse-boxes', auth, warehouseBoxController.getUserBoxes);

module.exports = router;
