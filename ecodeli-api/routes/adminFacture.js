const express = require('express');
const router = express.Router();
const auth = require('../middlewares/auth');
const adminFactureController = require('../controllers/adminFactureController');

router.get('/', auth, adminFactureController.getAllInvoices);
router.delete('/', auth, adminFactureController.deleteInvoices);

module.exports = router;
