const express = require('express');
const router = express.Router();
const auth = require('../middlewares/auth');
const bookingController = require('../controllers/bookingController');

router.get('/client', auth, bookingController.getBookingsByClient);
router.get('/:bookingId', auth, bookingController.getBookingDetails);
router.patch('/:bookingId/cancel', auth, bookingController.cancelBooking);


module.exports = router;
