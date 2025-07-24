const express = require('express');
const router = express.Router();
const auth = require('../middlewares/auth');
const reviewController = require('../controllers/reviewController');

router.get('/reviews/me', auth, reviewController.getMyReviews);
router.get('/reviews', auth, reviewController.getAllReviews);
router.delete('/review/:id', auth, reviewController.deleteReview);


module.exports = router;
