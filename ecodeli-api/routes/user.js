const express = require('express');
const router = express.Router();
const auth = require('../middlewares/auth');
const uploadAvatar = require('../middlewares/uploadAvatar');

const userController = require('../controllers/userController');

// Routes publiques
router.post('/register', userController.registerUser);
router.post('/login', userController.loginUser);

// Routes non protégées (liste / CRUD)
router.get('/users', userController.getAllUsers);
router.get('/users/:id', userController.getUserById);
router.put('/users/:id', userController.updateUser);
router.delete('/users/:id', userController.deleteUser);
router.post('/user/avatar', auth, uploadAvatar.single('avatar'), userController.updateAvatar);

router.get("/user", auth, userController.getConnectedUser);
router.put('/user', auth, userController.updateConnectedUser);

router.post("/user/become-courier", auth, userController.becomeCourier);
router.get('/users/:id/average-rating', auth, userController.getUserAverageRating);

// Route protégée par token
router.get('/user', auth, (req, res) => {
  res.json({ message: 'Bienvenue', user: req.user });
});

module.exports = router;
