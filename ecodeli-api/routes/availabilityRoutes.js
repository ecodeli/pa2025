const express = require("express");
const router = express.Router();
const availabilityController = require("../controllers/availabilityController");
const auth = require("../middlewares/auth");

router.get("/", auth, availabilityController.getUserAvailabilities);
router.post("/", auth, availabilityController.createAvailability);
router.get("/listing/:listingId", availabilityController.getListingAvailabilities);
router.delete('/:id', auth, availabilityController.deleteAvailability);




module.exports = router;
