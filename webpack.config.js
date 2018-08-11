const path = require('path');
const autoprefixer = require('autoprefixer');
const helpers = require('./build/helpers');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

const coreJS = {
	entry: './src/scripts/index.jsx',
	output: {
		path: path.resolve(__dirname, 'dist/scripts'),
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


// handles sass->css generation for the default them. Right now the core CSS and other themes are very basic so 
// it's all in CSS, but with 3.2 we'll expand it.
const themeFolders = helpers.getDirectories(path.resolve(__dirname, 'themes'));

// const sass = {
// 	entry: {
// 		main: './themes/default/src/sass/index.scss'
// 	},
// 	output: {
// 		path: path.resolve(__dirname, 'themes/default/dist/css'),
// 		filename: 'styles.css'
// 	},
// 	plugins: [
// 		new MiniCssExtractPlugin({
// 			// filename: "[name].css",
// 			// chunkFilename: "[id].css"
// 		})
// 	],
// 	module: {
// 		rules: [
// 			{
// 				loader: MiniCssExtractPlugin.loader
// 			},
// 			{ loader: 'css-loader' },
// 			{
// 				loader: 'postcss-loader',
// 				options: {
// 					plugins: () => {
// 						autoprefixer({
// 							browsers: [
// 								'last 2 versions' // TODO
// 							]
// 						});
// 					}
// 				}
// 			},
// 			{
// 				loader: 'sass-loader'
// 			}
// 		]
// 	}
// };

module.exports = [
	coreJS
]