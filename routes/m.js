var express = require('express');
var router = express.Router();

/* GET users listing. */
router.get('/', function(req, res) {
    var type = (req.query.type) ? req.query.type : 'bestsellers';
    var category = (req.query.category) ? req.query.category : 'books:all';
    var categories = category.split(',');
    var tag = (req.query.tag) ? req.query.tag : 'mytk05-22';
    var imgSize = (req.query.img_size) ? req.query.img_size : 160;
    var num = (req.query.num && req.query.num >= 1 && req.query.num <= 10) ? req.query.num : 5;
    var xy = (req.query.xy) ? req.query.xy : 'y';
    var categoryTitle = (req.query.category_title == 1) ? true : false;
    var title = (req.query.title == 1) ? true : false;
    var rank = (req.query.rank == 1) ? true : false;
    var fmt = (req.query.fmt) ? req.query.fmt : 'js';
    var data = [];

    if (categoryTitle) {
        var ctList = require('../data/category/list.json');
    }
    categories.forEach(function(category) {
        var tmp = category.split(':');
        var dat = {};
        if (! tmp[1]) {
            tmp[1] = 'all';
        }
        if (categoryTitle) {
            dat.title = ctList[tmp[0]][tmp[1]];
        }
        var rankData = require('../data/ranking/'+type+'/'+tmp[0]+'/'+tmp[1]+'.json');
        dat.items = rankData;
        data.push(dat);
    });

    res.render('m', {
        param: {
            tag: tag,
            imgSize: imgSize,
            num: num,
            xy: xy,
            categoryTitle: categoryTitle,
            title: title,
            rank: rank,
            fmt: fmt
        },
        data: data
    });
});

module.exports = router;
