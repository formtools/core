const { lstatSync, readdirSync } = require('fs');
const path = require('path');

const isDirectory = (source) => lstatSync(source).isDirectory();
const getDirectories = (source) => readdirSync(source).map(name => path.join(source, name)).filter(isDirectory);

module.exports = {
	getDirectories
}
