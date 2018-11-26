const path = require('path');
const autoprefixer = require('autoprefixer');


const devMode = process.env.NODE_ENV !== 'production';

const coreJS = {
	entry: './src/react/index.js',
	output: {
		path: path.resolve(__dirname, 'dist/global/scripts'),
		filename: 'bundle.js'
	},
	module: {
		rules: [
			{
				test: /\.js$/,
				exclude: /node_modules/,
				use: ['babel-loader']
			},
			{
				test: /\.scss/,
				use: [
					'style-loader',
					{
						loader: 'css-loader',
						options: {
							modules: true,
							sourceMap: devMode,
							localIdentName: '[name]__[local]__[hash:base64:5]'
						}
					},
					{
						loader: 'postcss-loader',
						options: {
							sourceMap: devMode,
							plugins: () => [
								autoprefixer({
									browsers: [
										'ie >= 11',
										'edge >= 12',
										'safari > 9',
										'chrome > 48',
										'firefox > 45'
									]
								})
							]
						}
					},
					{
						loader: 'sass-loader',
						options: {
							sourceMap: devMode
						}
					}
				]
			}
		]
	},
	resolve: {
		extensions: ['.js']
	}
};

module.exports = [
	coreJS
];
