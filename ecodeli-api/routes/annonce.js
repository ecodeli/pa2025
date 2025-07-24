const express = require('express');
const router = express.Router();
const auth = require('../middlewares/auth');
const upload = require('../middlewares/upload');


const AnnonceController = require('../controllers/AnnonceController');
const reviewController = require("../controllers/reviewController");

// CRUD
router.get('/annonce/user', auth, AnnonceController.getAllAnnonce);
router.get('/annonce/:id', auth, AnnonceController.getAnnonceByid);
router.post('/annonce', auth, upload.array('photos[]', 7), AnnonceController.createAnnonce);
router.post('/annonce/:id', auth, upload.array('photos[]', 7), AnnonceController.updateAnnonce);
router.delete('/annonce/:id', auth, AnnonceController.deleteAnnonce);
router.delete('/photo/:filename', auth, AnnonceController.deletePhoto);
router.get('/annonce/details/:id', AnnonceController.getAnnonceFullDetails);
router.get('/annonce/:id/for-review', auth, AnnonceController.getAnnonceForReview);
router.get('/annonce/:id/for-review-client', auth, AnnonceController.getAnnonceForReviewClient);
router.post('/annonce/:id/review', auth, AnnonceController.postReview);
router.post('/annonce/:id/review-client', auth, AnnonceController.postReviewClient);
router.get('/user/reviews-received', auth, AnnonceController.getReviewsReceived);



module.exports = router;
