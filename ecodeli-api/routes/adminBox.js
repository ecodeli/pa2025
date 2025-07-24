const express = require('express');
const router = express.Router();
const adminBoxController = require('../controllers/adminBoxController');
const auth = require('../middlewares/auth');

router.get('/admin/boxes/:id', auth, adminBoxController.getBoxesByWarehouse);
router.get('/admin/warehouses', auth, adminBoxController.getAllWarehouses);
router.post('/admin/boxes/free', auth, adminBoxController.freeBox);

module.exports = router;
