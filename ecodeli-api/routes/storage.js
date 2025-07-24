const express = require('express');
const router = express.Router();
const auth = require("../middlewares/auth");
const storageController = require('../controllers/storageController');


router.post('/reserve', auth, storageController.reserveBox);
router.get('/availability',auth, storageController.getAvailability);

module.exports = router;
