var express = require('express');
var router = express.Router();

/* GET home page. */
router.get('/', function(req, res) {
    var category = require('../data/category/list.json');

    res.render('index', { category: category });
});

module.exports = router;
