const express = require("express");
const router = express.Router();
const auth = require("../middlewares/auth");
const exportController = require("../controllers/exportController");

router.get("/utilisateurs-factures", exportController.getAllUsersWithFactures);
router.get("/ca-par-jour", exportController.getCAParJour);
router.get("/livraisons-detaillees", exportController.getDetailedDeliveries);
router.get('/services-details', exportController.getServicesDetails);


module.exports = router;
