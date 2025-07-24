const express = require("express");
const router = express.Router();
const auth = require("../middlewares/auth");
const likeController = require("../controllers/likeController");

router.post("/like/ad", auth, likeController.toggleLike);
router.get("/ad/like", auth, likeController.getLikedAds);
router.get('/like/check/:id', auth, likeController.checkLikeStatus);



module.exports = router;
