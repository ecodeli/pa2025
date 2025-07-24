const express = require('express');
const router = express.Router();
const multer = require('multer');
const auth = require('../middlewares/auth');
const documentController = require('../controllers/documentController');

const storage = multer.diskStorage({
    destination: (req, file, cb) => {
        cb(null, 'Documents/');
    },
    filename: (req, file, cb) => {
        const uniqueName = Date.now() + '-' + file.originalname;
        cb(null, uniqueName);
    }
});
const upload = multer({ storage: storage });

router.post('/upload-document', auth, upload.fields([
    { name: 'identity', maxCount: 1 },
    { name: 'permis', maxCount: 1 },
    { name: 'address', maxCount: 1 }
]), documentController.uploadDocuments);
router.get('/admin/documents', auth, documentController.getAllDocuments);
router.post('/admin/validate-document', auth, documentController.validateDocument);
router.post('/admin/refuse-document', auth, documentController.refuseDocument);
router.post('/admin/revoke-document', auth, documentController.revokeDocument);


module.exports = router;
