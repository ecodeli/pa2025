const router = require('express').Router();
const auth = require('../middlewares/auth');
const trajetsController = require('../controllers/trajetsController');

// même logique que /wallet
router.get('/my-trips', auth, trajetsController.getMyTrips);


module.exports = router;
