const path = require('path');

module.exports = {
	entry: './scripts-src/index.jsx',
	output: {
		path: path.resolve(__dirname, 'scripts'),
		filename: 'index.js'
	}
};
