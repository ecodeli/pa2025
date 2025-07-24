const express = require("express");
const router = express.Router();
const auth = require("../middlewares/auth");
const serviceController = require("../controllers/serviceController");

router.post("/book", auth, serviceController.bookService);

module.exports = router;
