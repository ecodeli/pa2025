const express = require('express');
const router  = express.Router();
const adminController = require('../controllers/adminController');

// … routes utilisateur …
router.get(   '/admin/users',              adminController.getAllUsersWithStats);
router.put(   '/admin/users/:id/type',    adminController.changeUserType);
router.put(   '/admin/users/:id/ban',     adminController.toggleBanUser);
router.delete('/admin/users/:id',         adminController.deleteUser);

module.exports = router;