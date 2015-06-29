// retrieve the node environment, defaults to development
// this can be set on the command line; precede the node command with
// 'NODE_ENV=test'.  For example, 'NODE_ENV=test node app.js'.
var environment = process.env.NODE_ENV || 'development';

// fetch the configuration specific to the environment
var config = require('./env/' + environment);

// add the environment to config, gives easy access to the rest of the app
config.environment = environment;

console.info('App using configuration: ', config);

// require-ing config gets you this config object
module.exports = config;
