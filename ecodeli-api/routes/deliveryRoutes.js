const express = require("express");
const router = express.Router();
const auth = require("../middlewares/auth");
const deliveryController = require("../controllers/deliveryController");

router.post("/create", auth, deliveryController.createDelivery);
router.get("/byUser", auth, deliveryController.getRoutesByUser);
router.post("/lines", auth, deliveryController.addToRoute);
router.get("/success/:id", deliveryController.getDeliverySummary);
router.get("/check_reserved/:listing_id", auth, deliveryController.checkIfReserved);
router.post("/declare-route", auth, deliveryController.declareRoute);

// Suivi Livraison cote livreur
router.post("/update-location", auth, deliveryController.updateSegmentLocation);
router.get("/segments/active", auth, deliveryController.getActiveSegments);
router.post("/split", auth, deliveryController.splitDeliverySegment);
router.post("/complete", auth, deliveryController.completeSegment);
router.post("/status", auth, deliveryController.updateDeliveryStatus);
router.post("/claim", auth, deliveryController.claimSegment);
router.get('/segments/pending', deliveryController.getPendingSegments);



module.exports = router;
