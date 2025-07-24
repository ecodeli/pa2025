const express = require('express');
const router = express.Router();
const nfcController = require('../controllers/nfcController');

router.post('/updateTag', nfcController.updateTag);
router.get('/check', nfcController.checkTag);

module.exports = router;
