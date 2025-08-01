const multer = require('multer');
const path = require('path');

// Config storage
const storage = multer.diskStorage({
    destination: (req, file, cb) => {
        cb(null, 'uploads/photos');
    },
    filename: (req, file, cb) => {
        const uniqueSuffix = Date.now() + '-' + Math.round(Math.random() * 1E9);
        const ext = path.extname(file.originalname);
        cb(null, 'annonce_' + uniqueSuffix + ext);
    }
});

const upload = multer({ storage });

module.exports = upload;
