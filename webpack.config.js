const path = require('path');

module.exports = {
	entry: './scripts-src/index.jsx',
	output: {
		path: path.resolve(__dirname, 'scripts'),
		filename: 'bundle.js'
	},
	module: {
		rules: [
			{
				test: /\.(js|jsx)$/,
				exclude: /node_modules/,
				use: ['babel-loader']
			}
		]
	},
	resolve: {
		extensions: ['.js', '.jsx']
	}
};
