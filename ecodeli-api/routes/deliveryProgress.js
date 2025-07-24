const express = require('express');
const router = express.Router();
const auth = require('../middlewares/auth'); // si besoin d'auth
const controller = require('../controllers/deliveryProgressController');

router.post('/delivery/steps', auth, controller.addStep); // Ajout d'une étape
router.get('/delivery/steps/:id', auth, controller.getStepsByDeliveryLine); // Récupération des étapes d'une ligne

module.exports = router;
