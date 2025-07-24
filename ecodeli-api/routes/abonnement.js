const express = require('express');
const router = express.Router();
const auth = require('../middlewares/auth');
const abonnementController = require('../controllers/abonnementController');

router.post('/subscribe', auth, abonnementController.subscribe);
router.get('/current', auth, abonnementController.getCurrent);

module.exports = router;
