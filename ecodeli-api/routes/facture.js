const express = require('express');
const router = express.Router();
const auth = require('../middlewares/auth');
const factureController = require('../controllers/factureController');

router.get('/my-invoices', auth, factureController.getMyInvoices);

module.exports = router;
