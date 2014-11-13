var express = require('express');
var path = require('path');
//var logger = require('morgan');
//var bodyParser = require('body-parser');
var ect = require('ect');
var log4js = require('log4js');
var logger = require('./lib/logger');

var routes = require('./routes/index');
var m = require('./routes/m');
var sample = require('./routes/sample');

var app = express();

// view engine setup
app.engine('ect', ect({ watch: true, root: __dirname + '/views', ext: '.ect' }).render);
//app.set('views', path.join(__dirname, 'views'));
app.set('view engine', 'ect');

app.use(log4js.connectLogger(logger.request, {
    'level': log4js.levels.INFO,
    'nolog': [ '\.css', '\.js', '\.gif', '\.png' ],
    'format': ':remote-addr - - ":method :url HTTP/:http-version" :status :content-length ":referrer" ":user-agent"'
}));
//    app.use(app.router);

//app.use(logger('dev'));
//app.use(bodyParser.json());
//app.use(bodyParser.urlencoded({ extended: false }));
app.use(express.static(path.join(__dirname, 'public')));

app.use('/', routes);
app.use('/m', m);
app.use('/m.php', m);
app.use('/sample', sample);
app.use('/sample.html', sample);

// catch 404 and forward to error handler
app.use(function(req, res, next) {
    var err = new Error('Not Found');
    err.status = 404;
    next(err);
});

// error handlers

// development error handler
// will print stacktrace
if (app.get('env') === 'development') {
    app.use(function(err, req, res, next) {
        res.status(err.status || 500);
        res.render('error', {
            message: err.message,
            error: err
        });
    });
}

// production error handler
// no stacktraces leaked to user
app.use(function(err, req, res, next) {
    res.status(err.status || 500);
    res.render('error', {
        message: err.message,
        error: {}
    });
});


module.exports = app;
