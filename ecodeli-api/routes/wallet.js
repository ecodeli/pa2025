const router = require('express').Router();
const auth = require('../middlewares/auth');
const ctrl = require('../controllers/walletController');

router.get('/wallet', auth, ctrl.getWallet);
router.get('/wallet/transactions', auth, ctrl.getTransactions);
router.post('/wallet/create-checkout-session', auth, ctrl.createCheckoutSession);
router.post('/wallet/checkout-success', auth, ctrl.checkoutSuccess);
router.post('/wallet/withdraw', auth, ctrl.withdraw);

module.exports = router;
