const express = require('express');
const router = express.Router();
const notificationsController = require('../controllers/notificationsController');
const auth = require('../middlewares/auth');

router.get('/my', auth, notificationsController.getUserNotifications);

module.exports = router;
