var config = require('./config'),
    express = require('express');

// initialize the web application server
var app = express();

// serve static content from the public directory at /
app.use('/', express.static(__dirname + '/public', {maxAge: 31557600000}));

app.use('/lib', express.static(__dirname + '/node_modules', {maxAge: 31557600000}));

// instruct the server to listen on the port specified in the config
app.listen(config.port);
