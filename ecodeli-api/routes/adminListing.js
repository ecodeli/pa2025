// routes/adminListing.js

const router = require('express').Router();
const ctrl   = require('../controllers/adminListingController');

// 1) Récupérer les annonces d’un marchand (pagination)
router.get(   '/admin/merchants/:id/listings', ctrl.getListingsByMerchant);

// 2) Archiver / restaurer une annonce
router.put(   '/admin/listings/:id/archive',  ctrl.toggleArchiveListing);

// 3) Supprimer une annonce
router.delete('/admin/listings/:id',          ctrl.deleteListing);

module.exports = router;
