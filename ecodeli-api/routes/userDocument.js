const express = require('express');
const router = express.Router();
const auth = require('../middlewares/auth');
const userDocumentController = require('../controllers/userDocumentController');

router.get('/user/documents', auth, userDocumentController.getUserDocuments);
router.delete('/user/delete-document', auth, userDocumentController.deleteDocument);

module.exports = router;
